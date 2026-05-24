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

## Unit tests

Every test method in `src/tests/` must follow these rules:

- **PHPDoc v češtině** se třemi částmi:
  1. Co test ověřuje (první věta)
  2. `Vstup:` — data, mocky nebo požadavek
  3. `Důvod:` — proč by se systém měl chovat daným způsobem
- **Explicit assertions** — each test must call at least one `self::assert*()` (or `self::fail()` combined with an assert in a `catch` block)
- Do not use `expectNotToPerformAssertions()`
- Prefer explicit `assert*` over standalone `expectException()`
- **Mock vs stub** (PHPUnit 11+):
  - `self::createStub()` — závislost jen vrací data, bez ověření volání
  - `self::createMock()` — vždy s `expects()`, ověřuje interakci
  - nepoužívat `createMock()` bez `expects()` (PHPUnit notices)
- **Mockování `final` tříd** — unit testy používají `dg/bypass-finals`; aktivace je globální v `phpunit.xml.dist` přes `DG\BypassFinals\PHPUnitExtension`, v jednotlivých testech není potřeba volat `BypassFinals::enable()`

Run tests inside the container:

```shell
docker compose exec orders-api-example composer test
```
