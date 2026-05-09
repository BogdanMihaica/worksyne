# Worksyne Docker Setup

This folder runs Traefik, the Vue client behind Nginx, the Laravel API behind Nginx/PHP-FPM, and MySQL together.

## Local Domains

Add these entries to your hosts file if they do not already resolve locally:

```txt
127.0.0.1 worksyne.local.test
127.0.0.1 api.worksyne.local.test
```

## Start

```sh
cd _deploy
docker compose up --build
```

- Client: https://worksyne.local.test
- API: https://api.worksyne.local.test
- Traefik dashboard: http://localhost:8080

HTTP requests are redirected to HTTPS automatically.

The local certificate is generated in `_deploy/traefik/certs`. Because it is self-signed, your browser will warn until you trust `_deploy/traefik/certs/worksyne.local.test.crt` in your OS/browser certificate store.

MySQL:

- Host from containers: `mysql`
- Host from your machine: `127.0.0.1`
- Port: `3306`
- Database: `worksyne`
- User: `worksyne`
- Password: `worksyne`
- Root password: `root`

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
