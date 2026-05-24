# Orders API

Ukázkový REST API projekt pro příjem objednávek od partnerských obchodů. Repozitář slouží jako ukázka a způsob práce s kódem, architekturou a nástroji, se kterými běžně pracuji.

## O projektu

Aplikace přijímá data o objednávkách od externích partnerů a ukládá je do PostgreSQL. Scope je záměrně úzký: dva endpointy, jeden modul (`Orders`) a sdílené jádro (`Core`). Cílem není kompletní produkční systém, ale přehledná ukázka návrhu API, vrstvení aplikace a práce s kvalitou kódu.

## Technologie

Backend běží na **PHP 8.5** se **Symfony 8**, perzistenci zajišťuje **Doctrine** nad **PostgreSQL 16** a celé prostředí je zabalené ve **FrankenPHP** v Dockeru. Kvalitu kódu hlídá **PHPStan** (level 8) a formátování **PHP CS Fixer** v Allman stylu.

## Časová náročnost

Rozpad práce na projektu. (total: 7.75h)

| Položka | Čas |
|--------|-----|
| Příprava architektury projektu, návrh DB, endpointů a DTOs | 0.75h |
| Implementace / Validace                                    | 3.75h |
| Dokumentace                                                | 0.25h |
| Testy                                                      | 2.25h |
| Finální validace & dokumentace                             | 0.75h |

Do zadaného časového limitu se nepodařilo dokončit sjednocené rozjetí kontejnerů včetně databáze (dedikovaný docker compose) - pouzivam vlastni sdileny backend (https://github.com/rdurica/dev-stack).

## Architektura

- Projekt používá **zjednodušenou hexagonální architekturu**. Doménová logika je oddělená od HTTP vrstvy a perzistence probíhá přes repository.
- Pro request/response jsou použité DTOs
- Pro práci s hodnotami (ošetřený vstup) jsou použity immutable value objekty (podobně jako v DDD)
- Ukládáme raw data (žádný FK na tabulku partnerů a podobně)
- **Validace ve dvou krocích** — na vstupu DTO projde jen základní kontrola přes Symfony Validator (formát, povinná pole, délky). Složitější doménová pravidla, která nejde jednoduše vyjádřit anotacemi, řeší value objekty a handler. Handler propaguje doménové výjimky, controller je mapuje na API výjimky, které `ApiExceptionListener` vrátí jako RFC 7807 odpověď.


```
HTTP request
    → Controller (HTTP, mapování výjimek)
    → RequestDtoFactory + Validator (deserializace, validace vstupu)
    → Handler (orchestrace, business logika, idempotence)
    → Value object (doménové typy)
    → Factory → Entity
    → Repository (Doctrine, persist v transakci)

ApiExceptionListener — cross-cutting, chyby → RFC 7807
```

### Vrstvy

| Vrstva | Odpovědnost |
|--------|-------------|
| **Controller** | Přijme request, zavolá `RequestDtoFactory`, předá DTO handleru, namapuje doménové výjimky na API výjimky, vrátí JSON. Pouze `__invoke`. |
| **RequestDtoFactory / Validator** | Deserializace JSON na DTO a validace vstupu přes Symfony Validator (`Core/Http`, `Core/Validator`). |
| **DTO** | Request/Response objekty — struktura dat z HTTP, bez business logiky. |
| **Handler** | Orchestrátor use case. Mapuje DTO na value objects, aplikuje business pravidla a idempotenci, volá factory a repository; uložení obalí do krátké transakce. Při porušení doménových pravidel propaguje doménové výjimky. Pouze `__invoke`. |
| **Value object** | Doménové typy a pravidla (`PartnerId`, `Price`, `OrderItem`, …) — validace a chování na úrovni hodnot. |
| **Factory** | Sestaví Doctrine entitu z value objects (`OrderEntityFactory`). |
| **Entity** | Doménová entita s chováním (`hasSameItemsAs`, `hasSameExpectedDeliveryDateAs`, …). |
| **Repository** | Přístup k datům (Doctrine). |
| **Listener** | `ApiExceptionListener` — zachytí API výjimky a vrátí odpověď ve formátu RFC 7807. |

### Moduly

Symfony aplikace je v `src/src/` — vnější `src/` je kořen PHP projektu (composer, config, migrace, …). Toto rozdělení je záměr vlastní Docker šablony, kterou používám napříč projekty (https://github.com/rdurica/php_starter_kit).

```
src/src/
├── Core/          # Sdílené stavební bloky
│   ├── Controller/
│   ├── Http/
│   ├── Validator/
│   ├── Listener/
│   ├── Transaction/
│   ├── Exception/
│   └── Dto/
└── Orders/        # Doménový modul objednávek
    ├── Controller/
    ├── Handler/
    ├── Repository/
    ├── Entity/
    ├── Dto/
    ├── Value/
    ├── Factory/
    └── Exception/
```

### Návrh architektury

Request projde controllerem, který přes `RequestDtoFactory` sestaví validované DTO. Handler z DTO vytvoří value objects, aplikuje business pravidla a idempotenci, entitu sestaví factory (nebo upraví existující) a změny uloží přes repository v krátké transakci. HTTP serializace a RFC 7807 chyby řeší infrastruktura v `Core` — handler nezná detaily JSON ani Doctrine mappingu.

### Design rozhodnutí a kompromisy

- **Handler místo Service** — u úzkého scope stačí, ve větším systému by se logika extrahovala do doménových služeb a handler by to jen obhospodařoval. Jsem zvyklý na „Facade“, ale takto to osobně preferuji více.
- **PartnerId jako string** — bez samostatné tabulky `Partner` a FK, ve větším systému by partner měl vlastní entitu.
- **Race condition u idempotence** — kontrola existence objednávky probíhá mimo transakci (úmyslný kompromis výkon vs. složitost).
- **Validace a propagace API výjimek** — převzato z dřívějšího projektu a upraveno pro tento účel.
- **`Price` bez měny** — samotný value object ceny bez `currency` nedává ve větším systému smysl. V demo scope jsem to nechal zjednodušené.
- **Fixní délky polí** — název produktu a další stringy mají pevné limity v DB/DTO; hodnoty jsem zvolil podle uvážení, v produkci by šly na diskusi.
- **`id` + `UUID` v tabulkách** — `id` je kratší a vhodnější pro FK uvnitř systému, `UUID` pro případ, že by se identifikátor posílal ven (frontend, externí API).
- **Duplicitní řádky stejného produktu** — jeden produkt může přijít rozpadlý na víc záznamů (např. 1× 10 ks jako 2× 5 ks). Backend je neslučuje a bere data „as is“ — záměr, ne chyba.
- **Repository bez rozhraní** — handler je napojený přímo na konkrétní repository třídu. Asi by stálo za to zavést interface (port) nad repository pro snazší mockování v unit testech handleru.

## API

Pro rychlé vyzkoušení endpointů slouží Postman kolekce [`docs/postman_collection.json`](docs/postman_collection.json) — importuj ji do Postmanu, base URL je `https://localhost`.

Chyby jsou vraceny ve formátu [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807) (`application/problem+json`).

### `POST /v1/orders` — vytvoření objednávky

Vytvoří novou objednávku včetně položek. Volání je **idempotentní**: pokud objednávka se stejným `partnerId` + `orderId` už existuje a všechny položky se shodují, vrátí se úspěch. Pokud se objednávka shoduje, ale alespoň jedna položka se liší, vrátí se chyba `OrderAlreadyExists`.

**Request:**

```json
{
  "partnerId": "partner-001",
  "orderId": "ORD-2026-001",
  "expectedDeliveryDate": "2026-06-15T14:00:00+02:00",
  "products": [
    {
      "id": "SKU-123",
      "title": "Produkt A",
      "price": "199.90",
      "quantity": 2
    }
  ]
}
```

**Response** `201 Created`:

```json
{
  "success": true,
  "message": "Order was created successfully."
}
```

### `PATCH /v1/orders` — změna data doručení

Aktualizuje `expectedDeliveryDate` u existující objednávky. Volání je **idempotentní** — opakovaný update na stejné datum vrátí úspěch.

**Request:**

```json
{
  "partnerId": "partner-001",
  "orderId": "ORD-2026-001",
  "expectedDeliveryDate": "2026-06-20T10:00:00+02:00"
}
```

**Response** `200 OK`:

```json
{
  "success": true,
  "message": "Order delivery date was updated successfully."
}
```

## Licence

MIT
