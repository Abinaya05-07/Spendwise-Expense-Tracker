# The Ledger — Personal Expense Tracker

A full-stack expense tracker with per-user accounts, a JWT-secured REST API,
a SQLite database, and a dependency-free frontend served by the same
Express app (one deployable service).

## Stack
- **Backend**: Node.js, Express, SQLite (`better-sqlite3`), JWT auth, bcrypt password hashing
- **Frontend**: Vanilla HTML/CSS/JS single-page app (no build step, no framework)
- **Database**: SQLite file, created automatically on first run

## Project structure
```
expense-tracker/
├── Dockerfile
├── .gitignore
└── server/
    ├── package.json
    ├── .env.example
    ├── src/
    │   ├── index.js          # Express app entry point
    │   ├── db.js              # SQLite connection + schema
    │   ├── middleware/auth.js # JWT verification
    │   └── routes/
    │       ├── auth.js        # register / login / me
    │       └── expenses.js    # CRUD + summary, all scoped per user
    └── public/                # static frontend (served by Express)
        ├── index.html
        ├── styles.css
        └── app.js
```

## Run it locally

```bash
cd server
npm install
cp .env.example .env
```

Open `.env` and set `JWT_SECRET` to a long random string. Then:

```bash
npm start
```

Visit `http://localhost:4000`. Create an account, sign in, and start adding entries.
The SQLite file is created automatically at `server/data/expenses.db`.

## API reference

All `/api/expenses/*` routes require `Authorization: Bearer <token>`.

| Method | Path                  | Description                          |
|--------|-----------------------|---------------------------------------|
| POST   | /api/auth/register    | `{ name, email, password }` → token   |
| POST   | /api/auth/login       | `{ email, password }` → token         |
| GET    | /api/auth/me          | current user                          |
| GET    | /api/expenses         | list, filters: `type`, `category`, `search`, `from`, `to` |
| POST   | /api/expenses         | `{ description, amount, type, category, date }` |
| PUT    | /api/expenses/:id     | partial update                        |
| DELETE | /api/expenses/:id     | remove an entry                       |
| GET    | /api/expenses/summary | totals + spend by category            |

## Deploying

This is a single Node service with a local SQLite file, so it deploys cleanly
to any platform that gives you a persistent disk (SQLite needs the file to
survive restarts/deploys).

### Option A: Render / Railway / Fly.io (recommended, easiest)
1. Push this repo to GitHub.
2. Create a new **Web Service** from the repo.
3. Build command: `cd server && npm install`
4. Start command: `cd server && npm start`
5. Add environment variables: `JWT_SECRET` (long random string), `DB_PATH=/data/expenses.db`
6. Attach a **persistent volume/disk** mounted at `/data` (Render: "Disks"; Railway: "Volumes"; Fly: `fly volumes create`) — without this, your database resets on every deploy.

### Option B: Docker (any VPS, Fly.io, Railway, etc.)
```bash
docker build -t ledger .
docker run -d -p 4000:4000 \
  -e JWT_SECRET="your-long-random-secret" \
  -v ledger_data:/app/data \
  --name ledger \
  ledger
```
The `-v ledger_data:/app/data` volume keeps your SQLite file across container restarts.

### Option C: A VPS directly (e.g. DigitalOcean, Hetzner)
1. Install Node 20+.
2. Copy the `server/` folder over, `npm install --omit=dev`, set `.env`.
3. Run with a process manager so it restarts on crash/reboot:
   ```bash
   npm install -g pm2
   pm2 start src/index.js --name ledger
   pm2 save && pm2 startup
   ```
4. Put nginx or Caddy in front for HTTPS.

## Notes on scaling past SQLite
SQLite is fine for a personal tool or small number of users on a single
server. If you outgrow it, swap `better-sqlite3` for `pg` and point it at
Postgres (Render, Railway, Supabase, and Neon all offer free/cheap managed
Postgres) — the query shapes in `src/routes/*.js` translate directly, you'd
mainly change the `db.js` connection and placeholder syntax (`?` → `$1, $2...`).

## Security notes
- Passwords are hashed with bcrypt, never stored in plaintext.
- JWTs expire after 7 days by default (`JWT_EXPIRES_IN` in `.env`).
- Every expense query is scoped to `user_id`, so one account can never read or modify another's entries.
- Set a strong, random `JWT_SECRET` in production — don't reuse the example value.
