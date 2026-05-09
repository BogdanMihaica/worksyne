# Worksyne Docker Setup

This folder runs Traefik, the Vue client through Vite, the Laravel API behind Nginx/PHP-FPM, and MySQL together.

## Local Domains

Add these entries to your hosts file if they do not already resolve locally:

```txt
127.0.0.1 worksyne.local.test
127.0.0.1 api.worksyne.local.test
```

## Start

```sh
cd _deploy
cp .env.example .env
docker compose up --build
```

Edit `.env` if you need different local domains, ports, or database credentials.

- Client: `${CLIENT_URL}`
- API: `${API_URL}`
- Traefik dashboard: `http://localhost:${TRAEFIK_DASHBOARD_PORT}`

HTTP requests are redirected to HTTPS automatically.

The local certificate is generated in `_deploy/traefik/certs`. Because it is self-signed, your browser will warn until you trust `_deploy/traefik/certs/worksyne.local.test.crt` in your OS/browser certificate store.

## Hot Reload

The client source is mounted into the `client` container and served by Vite. Changes under `client/src` should update the browser without rebuilding.

The API source is mounted into the `api` container. PHP code changes under `api` are picked up without rebuilding. Rebuild only when changing Dockerfiles, PHP extensions, Composer dependencies, or npm dependencies.

MySQL:

- Host from containers: `mysql`
- Host from your machine: `127.0.0.1`
- Port: `${MYSQL_PORT}`
- Database: `${DB_DATABASE}`
- User: `${DB_USERNAME}`
- Password: `${DB_PASSWORD}`
- Root password: `${MYSQL_ROOT_PASSWORD}`

## Laravel Commands

Run migrations after the containers are up:

```sh
docker compose exec api php artisan migrate
```

Run Laravel tests:

```sh
docker compose exec api php artisan test
```

## Stop

```sh
docker compose down
```

Remove the database volume too:

```sh
docker compose down -v
```
