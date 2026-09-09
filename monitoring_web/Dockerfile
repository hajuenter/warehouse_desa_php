FROM node:22.16.0-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

ARG VITE_API_URL
ARG VITE_POLL_INTERVAL
ENV VITE_API_URL=$VITE_API_URL
ENV VITE_POLL_INTERVAL=$VITE_POLL_INTERVAL

RUN npm run build

FROM nginx:alpine

COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80