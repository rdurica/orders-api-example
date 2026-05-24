# Orders API

Ukázkový REST API projekt pro příjem objednávek od partnerských obchodů. Repozitář slouží jako ukazka a způsob práce s kódem, architekturou a nástroji, se kterými běžně pracuji.

## O projektu

Aplikace přijímá data o objednávkách od externích partnerů a ukládá je do PostgreSQL. Scope je záměrně úzký: dva endpointy, jeden modul (`Orders`) a sdílené jádro (`Core`). Cílem není kompletní produkční systém, ale přehledná ukázka návrhu API, vrstvení aplikace a práce s kvalitou kódu.

## Technologie

Backend běží na **PHP 8.5** se **Symfony 8**, perzistenci zajišťuje **Doctrine** nad **PostgreSQL 16** a celé prostředí je zabalené ve **FrankenPHP** v Dockeru. Kvalitu kódu hlídá **PHPStan** (level 8) a formátování **PHP CS Fixer** v Allman stylu.

## Casova narocnost
Celkovy rozpad prace na projektu. (total: 7)

| Polozka | Cas   |
|--------|-------|
| Priprava architektury projektu, navrh DB, endpointu a DTOs | 0.75h |
| Implementace / Validace | 3.75h |
| Dokumentace | 0.25h |
| Testy | 2.25h |

## Todo
 - Doimplementovat logiku zatim mam jen kostru
 - Unit testy
 - Rozepsat navrh architektury, myslenkove pochody pri navrhu, strukturu (neanotujem metody ktore jsou jasne ani tridy), kompromisy a co by realne se udelalo jinak (Price ValueObject, Partner separe tabulka a FK a podobne...)
 - Doupravit readme a dokumentaci
 - Funkcni testy**
 - Udelat compose i z db aby slo spustit 1 prikazem**


** Nice to have

## Architektura

Projekt používá **zjednodušenou hexagonální architekturu**. Doménová logika je oddělená od HTTP vrstvy a perzistence probíhá přes repository.

```
HTTP request
    → Controller (tenká vrstva, pouze mapování)
    → Handler (transakce, orchestrace)
    → Service (business logika)
    → Repository (Doctrine)
```

### Vrstvy

| Vrstva | Odpovědnost |
|--------|-------------|
| **Controller** | Přijme request, vytvoří DTO, předá handleru, vrátí JSON odpověď. Pouze `__invoke`. |
| **Handler** | Obalí operaci do transakce (`TransactionManager`), volá service. Pouze `__invoke`. |
| **Service** | Business pravidla, idempotence, validace doménových pravidel. |
| **Repository** | Přístup k datům (Doctrine). |
| **DTO** | Request/Response objekty s validací přes Symfony Validator. |

### Moduly

```
src/
├── Core/          # Sdílené stavební bloky (transakce, výjimky, HTTP helpery)
└── Orders/        # Doménový modul objednávek
    ├── Controller/
    ├── Handler/
    ├── Service/
    ├── Repository/
    ├── Entity/
    ├── Dto/
    └── Exception/
```

### Návrh architektury

<!-- TODO: doplnit -->

### Kompromisy a co bych řešil jinak ve větším systému

<!-- TODO: doplnit -->



## API

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

**Možné chyby:** `OrderAlreadyExists`, `InvalidData`, `Unexpected`

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

**Možné chyby:** `OrderNotFound`, `InvalidDate`, `Unexpected`

## Notes
Validaci a propagace API vyjimek jsem se inspiroval u jedneho meho starsiho projektu a jen upravil pro tento ucel. Nevymyslal jsem to nanovo pro tento ukol.

## Licence

MIT
