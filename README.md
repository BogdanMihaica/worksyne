# Worksyne

Project layout:

- `client`: Vue application scaffolded with the latest `create-vue`.
- `api`: Laravel application scaffolded with the latest `laravel/laravel`.
- `_deploy`: Docker Compose setup for the client, API, and MySQL.

Run everything with Traefik on port `80`:

```sh
cd _deploy
docker compose up --build
```

Local URLs:

- `https://worksyne.local.test`
- `https://api.worksyne.local.test`
