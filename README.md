# byfareska/crud-example

An example application of [byfareska/crud](https://github.com/byfareska/crud): a Symfony 8.2 application with two
panels, own dashboard widgets and a sign-in page.

The resources are in `src/Crud`, by driver and each in two sizes:

- `Doctrine/Minimal` - suppliers: an entity and its columns;
- `Doctrine/Maximal` - a product catalogue with everything;
- `Dto/Minimal` - the bundles of the kernel: one list DTO and its use case;
- `Dto/Maximal` - tasks: use cases, kept in a JSON file.

## Running

Everything runs in Docker (`docker/`: PHP 8.5 with intl, pdo_sqlite; Node 24 for the assets).

```sh
docker compose build
docker compose run --rm php composer install
docker compose run --rm node npm install
docker compose run --rm node npm run build
docker compose run --rm php bin/console doctrine:migrations:migrate -n
docker compose run --rm php bin/console doctrine:fixtures:load -n
docker compose up -d php
```

Then open <http://localhost:8080> and sign in as **admin / admin** (both panels) or **user / user** (only "Panel").
`HTTP_PORT`, `UID` and `GID` (the user the containers run as, 1000 by default) are read from the shell.

The theme is `CRUD_THEME` in `.env`: `daisyui` (the default; pick a colour theme in the top bar), `bootstrap5` or
`tailwind`. To see another one, put `CRUD_THEME=bootstrap5` in `.env.local` (all three are built by `npm run build`);
`CRUD_PANEL_THEME=tailwind` or `CRUD_ADMIN_THEME=tailwind` draws only that panel in it (a theme per panel).

While working on styles or controllers: `docker compose run --rm --service-ports node npx vite` (dev server on 5173).

## Tests

```sh
docker compose run --rm php bin/phpunit # pages in every theme, both drivers (minimal and maximal), dashboard, palette
```
