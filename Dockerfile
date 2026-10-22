FROM node:20-slim

WORKDIR /app

COPY server/package.json server/package-lock.json ./
RUN npm ci --omit=dev

COPY server/src ./src
COPY server/public ./public

ENV PORT=4000
ENV DB_PATH=/app/data/expenses.db
RUN mkdir -p /app/data

EXPOSE 4000

CMD ["node", "src/index.js"]
