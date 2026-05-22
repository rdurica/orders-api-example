# Agent Context for Orders Api Example

## Stack

- **Backend**: PHP 8.5+, FrankenPHP (Caddy-powered PHP server)
- **Supported Frameworks**: Laravel, Symfony, Nette
- **Database**: PostgreSQL
- **Infrastructure**: Docker (FrankenPHP)

## Dev Environment

All development runs inside Docker containers via `docker compose`.

| Command               | Description                                          |
| --------------------- | ---------------------------------------------------- |
| `make init`           | First-time setup: network, images, containers        |
| `make up`             | Start containers detached                            |
| `make down`           | Stop containers                                      |
| `make logs`           | Stream logs                                          |
| `make php`            | Shell into FrankenPHP container                      |

**All `bin/console`, and `npm` commands must run inside containers:**

```shell
docker compose exec orders-api-example bin/console <cmd>
docker compose exec orders-api-example npm <cmd>
```

## Key Commands

```shell
# Setup (inside container)
composer install

# Symfony
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
vendor/bin/phpunit
```
