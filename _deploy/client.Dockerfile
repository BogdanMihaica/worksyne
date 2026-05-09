FROM node:24-alpine AS build

WORKDIR /app

ARG VITE_API_URL=https://api.worksyne.local.test
ENV VITE_API_URL=${VITE_API_URL}

COPY client/package.json client/package-lock.json ./
RUN npm ci

COPY client/ ./
RUN npm run build

FROM nginx:1.27-alpine

COPY _deploy/nginx/client.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
