# REST API V2 - Import a export dat (PHP)

Tento návod vysvětluje, jak používat REST API V2 pro import a export produktů, kategorií a parametrů.

---

## Co je REST API V2?

REST API V2 umožňuje:

- **Import** produktů, kategorií a parametrů do systému
- **Export** (čtení) produktů a kategorií ze systému
- **Multilang support** - všechna data podporují více jazykových verzí

---

## Rychlý start

1. Přihlaste se do [administrace](http://client.pobo.space)
2. Jděte do [Nastavení → REST API](http://client.pobo.space/merchant/setting/rest-api)
3. Klikněte **Vygenerovat token**
4. Zkopírujte vygenerovaný **API token**
5. Použijte token v hlavičce `Authorization: Bearer {token}`

---

## Autentizace

Všechny požadavky musí obsahovat API token v hlavičce:

```http
Authorization: Bearer váš_api_token_zde
Content-Type: application/json
Accept: application/json
```

### Příklad v PHP

```php
<?php

$apiToken = 'váš_api_token_zde';
$baseUrl = 'https://api.pobo.space';

$headers = [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json',
    'Accept: application/json',
];
```

---

## Endpointy

| Metoda | Endpoint                  | Popis                 |
|--------|---------------------------|-----------------------|
| `GET`  | `/api/v2/rest/products`   | Seznam produktů       |
| `POST` | `/api/v2/rest/products`   | Bulk import produktů  |
| `GET`  | `/api/v2/rest/categories` | Seznam kategorií      |
| `POST` | `/api/v2/rest/categories` | Bulk import kategorií |
| `POST` | `/api/v2/rest/parameters` | Bulk import parametrů |

---

## Podporované jazyky

API podporuje následující jazykové kódy:

| Kód       | Jazyk                   |
|-----------|-------------------------|
| `default` | Výchozí jazyk (povinný) |
| `cs`      | Čeština                 |
| `sk`      | Slovenština             |
| `en`      | Angličtina              |
| `de`      | Němčina                 |
| `pl`      | Polština                |
| `hu`      | Maďarština              |

**Pravidla:**

- `default` je vždy povinný
- Pokud použijete jakýkoli další jazyk, musíte pro něj vyplnit `name` a `url`
- Ostatní pole (`description`, `seo_title`, `seo_description`) mohou být `null`
- Nepodporované jazyky jsou ignorovány

---

## Import produktů

### Endpoint

```http
POST /api/v2/rest/products
```

### Struktura požadavku

```json
[
  {
    "id": "PROD-001",
    "is_visible": true,
    "name": {
      "default": "Název produktu",
      "sk": "Názov produktu",
      "en": "Product name"
    },
    "url": {
      "default": "https://example.com/produkt",
      "sk": "https://example.com/sk/produkt",
      "en": "https://example.com/en/product"
    },
    "short_description": {
      "default": "Krátký popis produktu",
      "sk": "Krátky popis produktu",
      "en": "Short product description"
    },
    "description": {
      "default": "<p>Dlouhý popis produktu s HTML</p>",
      "sk": "<p>Dlhý popis produktu s HTML</p>",
      "en": "<p>Long product description with HTML</p>"
    },
    "seo_title": {
      "default": "SEO titulek",
      "sk": "SEO titulok",
      "en": "SEO title"
    },
    "seo_description": {
      "default": "SEO popis pro vyhledávače",
      "sk": "SEO popis pre vyhľadávače",
      "en": "SEO description for search engines"
    },
    "images": [
      "https://example.com/images/product1.jpg",
      "https://example.com/images/product2.jpg"
    ],
    "categories_ids": [
      "CAT-001",
      "CAT-002"
    ],
    "parameters_ids": [
      1,
      2,
      3
    ]
  }
]
```

### Pole produktu

| Pole                | Typ     | Povinné | Popis                                 |
|---------------------|---------|---------|---------------------------------------|
| `id`                | string  | Ano     | Unikátní ID produktu (max 255 znaků)  |
| `is_visible`        | boolean | Ano     | Zda je produkt viditelný              |
| `name`              | object  | Ano     | Název produktu v různých jazycích     |
| `name.default`      | string  | Ano     | Výchozí název (max 250 znaků)         |
| `url`               | object  | Ano     | URL produktu v různých jazycích       |
| `url.default`       | string  | Ano     | Výchozí URL (musí začínat `https://`) |
| `short_description` | object  | Ne      | Krátký popis (max 65000 znaků)        |
| `description`       | object  | Ne      | Dlouhý popis s HTML (max 65000 znaků) |
| `seo_title`         | object  | Ne      | SEO titulek (max 255 znaků)           |
| `seo_description`   | object  | Ne      | SEO popis (max 500 znaků)             |
| `images`            | array   | Ne      | Pole URL obrázků (první = náhled)     |
| `categories_ids`    | array   | Ne      | Pole ID kategorií                     |
| `parameters_ids`    | array   | Ne      | Pole ID hodnot parametrů              |

### Odpověď

```json
{
  "success": true,
  "imported": 5,
  "updated": 2,
  "skipped": 1,
  "errors": [
    {
      "index": 7,
      "id": "PROD-008",
      "errors": [
        "The name.sk field is required when using language sk."
      ]
    }
  ]
}
```

### PHP příklad

```php
<?php

function importProducts(array $products): array
{
    global $apiToken, $baseUrl;

    $ch = curl_init($baseUrl . '/api/v2/rest/products');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($products),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API error: HTTP $httpCode - $response");
    }

    return json_decode($response, true);
}

// Použití
$products = [
    [
        'id' => 'PROD-001',
        'is_visible' => true,
        'name' => [
            'default' => 'Testovací produkt',
            'sk' => 'Testovací produkt SK',
        ],
        'url' => [
            'default' => 'https://example.com/produkt',
            'sk' => 'https://example.com/sk/produkt',
        ],
    ],
];

$result = importProducts($products);
echo "Importováno: {$result['imported']}, Aktualizováno: {$result['updated']}";
```

---

## Import kategorií

### Endpoint

```http
POST /api/v2/rest/categories
```

### Struktura požadavku

```json
[
  {
    "id": "CAT-001",
    "is_visible": true,
    "name": {
      "default": "Elektronika",
      "sk": "Elektronika",
      "en": "Electronics"
    },
    "url": {
      "default": "https://example.com/elektronika",
      "sk": "https://example.com/sk/elektronika",
      "en": "https://example.com/en/electronics"
    },
    "description": {
      "default": "<p>Kategorie elektroniky</p>",
      "sk": "<p>Kategória elektroniky</p>",
      "en": "<p>Electronics category</p>"
    },
    "seo_title": {
      "default": "Elektronika | Obchod",
      "sk": "Elektronika | Obchod",
      "en": "Electronics | Shop"
    },
    "seo_description": {
      "default": "Nejlepší elektronika",
      "sk": "Najlepšia elektronika",
      "en": "Best electronics"
    },
    "images": [
      "https://example.com/images/category1.jpg"
    ]
  }
]
```

### Pole kategorie

| Pole              | Typ     | Povinné | Popis                                 |
|-------------------|---------|---------|---------------------------------------|
| `id`              | string  | Ano     | Unikátní ID kategorie (max 255 znaků) |
| `is_visible`      | boolean | Ano     | Zda je kategorie viditelná            |
| `name`            | object  | Ano     | Název kategorie v různých jazycích    |
| `name.default`    | string  | Ano     | Výchozí název (max 250 znaků)         |
| `url`             | object  | Ano     | URL kategorie v různých jazycích      |
| `url.default`     | string  | Ano     | Výchozí URL (musí začínat `https://`) |
| `description`     | object  | Ne      | Popis s HTML (max 65000 znaků)        |
| `seo_title`       | object  | Ne      | SEO titulek (max 255 znaků)           |
| `seo_description` | object  | Ne      | SEO popis (max 500 znaků)             |
| `images`          | array   | Ne      | Pole URL obrázků (první = náhled)     |

### PHP příklad

```php
<?php

function importCategories(array $categories): array
{
    global $apiToken, $baseUrl;

    $ch = curl_init($baseUrl . '/api/v2/rest/categories');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($categories),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API error: HTTP $httpCode - $response");
    }

    return json_decode($response, true);
}

// Použití
$categories = [
    [
        'id' => 'CAT-001',
        'is_visible' => true,
        'name' => [
            'default' => 'Elektronika',
        ],
        'url' => [
            'default' => 'https://example.com/elektronika',
        ],
    ],
];

$result = importCategories($categories);
echo "Importováno: {$result['imported']}, Aktualizováno: {$result['updated']}";
```

---

## Import parametrů

### Endpoint

```http
POST /api/v2/rest/parameters
```

### Struktura požadavku

```json
[
  {
    "id": 1,
    "name": "Barva",
    "values": [
      {
        "id": 1,
        "value": "Červená"
      },
      {
        "id": 2,
        "value": "Modrá"
      },
      {
        "id": 3,
        "value": "Zelená"
      }
    ]
  },
  {
    "id": 2,
    "name": "Velikost",
    "values": [
      {
        "id": 4,
        "value": "S"
      },
      {
        "id": 5,
        "value": "M"
      },
      {
        "id": 6,
        "value": "L"
      }
    ]
  }
]
```

### Pole parametru

| Pole             | Typ     | Povinné | Popis                             |
|------------------|---------|---------|-----------------------------------|
| `id`             | integer | Ano     | Unikátní ID parametru             |
| `name`           | string  | Ano     | Název parametru (max 255 znaků)   |
| `values`         | array   | Ano     | Pole hodnot parametru (min 1)     |
| `values.*.id`    | integer | Ano     | Unikátní ID hodnoty               |
| `values.*.value` | string  | Ano     | Hodnota parametru (max 255 znaků) |

**Poznámka:** Parametry nepodporují vícejazyčnost - používají stejnou strukturu jako API V1.

### Odpověď

```json
{
  "success": true,
  "imported": 2,
  "updated": 0,
  "skipped": 0,
  "values_imported": 6,
  "values_updated": 0,
  "errors": []
}
```

### PHP příklad

```php
<?php

function importParameters(array $parameters): array
{
    global $apiToken, $baseUrl;

    $ch = curl_init($baseUrl . '/api/v2/rest/parameters');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($parameters),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API error: HTTP $httpCode - $response");
    }

    return json_decode($response, true);
}

// Použití
$parameters = [
    [
        'id' => 1,
        'name' => 'Barva',
        'values' => [
            ['id' => 1, 'value' => 'Červená'],
            ['id' => 2, 'value' => 'Modrá'],
        ],
    ],
];

$result = importParameters($parameters);
echo "Parametry: {$result['imported']}, Hodnoty: {$result['values_imported']}";
```

---

## Export produktů

### Endpoint

```http
GET /api/v2/rest/products
```

### Query parametry

| Parametr                | Typ     | Popis                                                |
|-------------------------|---------|------------------------------------------------------|
| `page`                  | integer | Číslo stránky (výchozí: 1)                           |
| `per_page`              | integer | Položek na stránku (max 100, výchozí: 100)           |
| `is_edited`             | boolean | Filtr podle stavu editace                            |
| `last_update_time_from` | string  | Filtr podle data aktualizace (formát: `Y-m-d H:i:s`) |

### Příklad požadavku

```http
GET /api/v2/rest/products?page=1&per_page=50&last_update_time_from=2024-01-01%2000:00:00
```

### Odpověď

```json
{
  "data": [
    {
      "id": "PROD-001",
      "guid": "550e8400-e29b-41d4-a716-446655440000",
      "is_visible": true,
      "is_loaded": false,
      "name": {
        "default": "Název produktu",
        "sk": "Názov produktu"
      },
      "short_description": {
        "default": "Krátký popis",
        "sk": "Krátky popis"
      },
      "description": {
        "default": "<p>Dlouhý popis</p>",
        "sk": "<p>Dlhý popis</p>"
      },
      "url": {
        "default": "https://example.com/produkt",
        "sk": "https://example.com/sk/produkt"
      },
      "seo_title": {
        "default": "SEO titulek",
        "sk": "SEO titulok"
      },
      "seo_description": {
        "default": "SEO popis",
        "sk": "SEO popis"
      },
      "categories": [
        {
          "id": "CAT-001",
          "name": {
            "default": "Elektronika"
          }
        }
      ],
      "images": [
        "https://example.com/images/product1.jpg"
      ],
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-16T14:20:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 150
  }
}
```

### PHP příklad

```php
<?php

function getProducts(int $page = 1, int $perPage = 100, ?string $lastUpdateFrom = null): array
{
    global $apiToken, $baseUrl;

    $params = [
        'page' => $page,
        'per_page' => $perPage,
    ];

    if ($lastUpdateFrom) {
        $params['last_update_time_from'] = $lastUpdateFrom;
    }

    $url = $baseUrl . '/api/v2/rest/products?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API error: HTTP $httpCode - $response");
    }

    return json_decode($response, true);
}

// Použití - získej všechny produkty aktualizované od 1.1.2024
$result = getProducts(1, 100, '2024-01-01 00:00:00');

foreach ($result['data'] as $product) {
    echo "Produkt: {$product['name']['default']} (ID: {$product['id']})\n";
}

echo "Celkem: {$result['meta']['total']} produktů";
```

---

## Export kategorií

### Endpoint

```http
GET /api/v2/rest/categories
```

### Query parametry

| Parametr                | Typ     | Popis                                                |
|-------------------------|---------|------------------------------------------------------|
| `page`                  | integer | Číslo stránky (výchozí: 1)                           |
| `per_page`              | integer | Položek na stránku (max 100, výchozí: 100)           |
| `is_edited`             | boolean | Filtr podle stavu editace                            |
| `last_update_time_from` | string  | Filtr podle data aktualizace (formát: `Y-m-d H:i:s`) |

### Odpověď

```json
{
  "data": [
    {
      "id": "CAT-001",
      "guid": "550e8400-e29b-41d4-a716-446655440000",
      "is_visible": true,
      "is_loaded": false,
      "name": {
        "default": "Elektronika",
        "sk": "Elektronika"
      },
      "description": {
        "default": "<p>Kategorie elektroniky</p>",
        "sk": "<p>Kategória elektroniky</p>"
      },
      "url": {
        "default": "https://example.com/elektronika",
        "sk": "https://example.com/sk/elektronika"
      },
      "seo_title": {
        "default": "Elektronika | Obchod",
        "sk": "Elektronika | Obchod"
      },
      "seo_description": {
        "default": "Nejlepší elektronika",
        "sk": "Najlepšia elektronika"
      },
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-16T14:20:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 100,
    "total": 50
  }
}
```

### PHP příklad

```php
<?php

function getCategories(int $page = 1, int $perPage = 100): array
{
    global $apiToken, $baseUrl;

    $url = $baseUrl . '/api/v2/rest/categories?' . http_build_query([
        'page' => $page,
        'per_page' => $perPage,
    ]);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API error: HTTP $httpCode - $response");
    }

    return json_decode($response, true);
}

// Použití
$result = getCategories(1, 50);

foreach ($result['data'] as $category) {
    echo "Kategorie: {$category['name']['default']} (ID: {$category['id']})\n";
}
```

---

## Kompletní příklad synchronizace

```php
<?php

/**
 * Kompletní třída pro synchronizaci s REST API V2
 */
class PoboApiClient
{
    private string $apiToken;
    private string $baseUrl;

    public function __construct(string $apiToken, string $baseUrl = 'https://api.pobo.space')
    {
        $this->apiToken = $apiToken;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Provede HTTP požadavek na API
     */
    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        if ($httpCode >= 400) {
            throw new Exception("API error: HTTP $httpCode - $response");
        }

        return json_decode($response, true);
    }

    /**
     * Import produktů
     */
    public function importProducts(array $products): array
    {
        return $this->request('POST', '/api/v2/rest/products', $products);
    }

    /**
     * Import kategorií
     */
    public function importCategories(array $categories): array
    {
        return $this->request('POST', '/api/v2/rest/categories', $categories);
    }

    /**
     * Import parametrů
     */
    public function importParameters(array $parameters): array
    {
        return $this->request('POST', '/api/v2/rest/parameters', $parameters);
    }

    /**
     * Export produktů
     */
    public function getProducts(int $page = 1, int $perPage = 100, ?string $lastUpdateFrom = null): array
    {
        $query = http_build_query(array_filter([
            'page' => $page,
            'per_page' => $perPage,
            'last_update_time_from' => $lastUpdateFrom,
        ]));

        return $this->request('GET', '/api/v2/rest/products?' . $query);
    }

    /**
     * Export kategorií
     */
    public function getCategories(int $page = 1, int $perPage = 100): array
    {
        $query = http_build_query([
            'page' => $page,
            'per_page' => $perPage,
        ]);

        return $this->request('GET', '/api/v2/rest/categories?' . $query);
    }

    /**
     * Export všech produktů (stránkování)
     */
    public function getAllProducts(?string $lastUpdateFrom = null): array
    {
        $allProducts = [];
        $page = 1;

        do {
            $result = $this->getProducts($page, 100, $lastUpdateFrom);
            $allProducts = array_merge($allProducts, $result['data']);
            $page++;
        } while (count($result['data']) === 100);

        return $allProducts;
    }
}

// ========================================
// POUŽITÍ
// ========================================

$client = new PoboApiClient('váš_api_token_zde');

// 1. Import parametrů (nejdříve, protože produkty na ně odkazují)
$parameters = [
    [
        'id' => 1,
        'name' => 'Barva',
        'values' => [
            ['id' => 1, 'value' => 'Červená'],
            ['id' => 2, 'value' => 'Modrá'],
        ],
    ],
];

$result = $client->importParameters($parameters);
echo "Parametry importovány: {$result['imported']}\n";

// 2. Import kategorií
$categories = [
    [
        'id' => 'CAT-001',
        'is_visible' => true,
        'name' => ['default' => 'Elektronika', 'sk' => 'Elektronika'],
        'url' => ['default' => 'https://example.com/elektronika', 'sk' => 'https://example.com/sk/elektronika'],
    ],
];

$result = $client->importCategories($categories);
echo "Kategorie importovány: {$result['imported']}\n";

// 3. Import produktů
$products = [
    [
        'id' => 'PROD-001',
        'is_visible' => true,
        'name' => ['default' => 'iPhone 15', 'sk' => 'iPhone 15'],
        'url' => ['default' => 'https://example.com/iphone-15', 'sk' => 'https://example.com/sk/iphone-15'],
        'categories_ids' => ['CAT-001'],
        'parameters_ids' => [1],
    ],
];

$result = $client->importProducts($products);
echo "Produkty importovány: {$result['imported']}\n";

// 4. Export všech produktů aktualizovaných od včerejška
$yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
$products = $client->getAllProducts($yesterday);
echo "Nalezeno " . count($products) . " aktualizovaných produktů\n";
```

---

## Limity

| Limit                             | Hodnota      |
|-----------------------------------|--------------|
| Max položek na požadavek (import) | 100          |
| Max položek na stránku (export)   | 100          |
| Max délka názvu                   | 250 znaků    |
| Max délka URL                     | 255 znaků    |
| Max délka popisu                  | 65 000 znaků |
| Max délka SEO popisu              | 500 znaků    |
| Max délka URL obrázku             | 650 znaků    |

---

## Chybové odpovědi

### 401 Unauthorized

```json
{
  "error": "Authorization token required"
}
```

**Řešení:** Zkontrolujte, že posíláte správný API token v hlavičce `Authorization`.

### 422 Validation Error

```json
{
  "success": false,
  "errors": {
    "bulk": [
      "Maximum 100 items allowed for bulk import"
    ]
  }
}
```

**Řešení:** Snižte počet položek v požadavku na max 100.

### 500 Server Error

```json
{
  "success": false,
  "message": "Import failed: Database error"
}
```

**Řešení:** Kontaktujte podporu s detaily požadavku.

---

## Doporučené postupy

### 1. Importujte ve správném pořadí

```
1. Parametry (nemají závislosti)
2. Kategorie (nemají závislosti)
3. Produkty (závisí na kategoriích a parametrech)
```

### 2. Používejte stránkování pro export

```php
// ŠPATNĚ - může způsobit timeout
$allProducts = $client->getProducts(1, 10000);

// SPRÁVNĚ - stránkování po 100
$page = 1;
do {
    $result = $client->getProducts($page, 100);
    processProducts($result['data']);
    $page++;
} while (count($result['data']) === 100);
```

### 3. Zpracovávejte chyby jednotlivých položek

```php
$result = $client->importProducts($products);

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $error) {
        echo "Chyba u produktu {$error['id']}: " . implode(', ', $error['errors']) . "\n";
    }
}
```

### 4. Používejte inkrementální synchronizaci

```php
// Uložte si čas poslední synchronizace
$lastSync = getLastSyncTime(); // např. z databáze

// Získejte pouze změněné produkty
$products = $client->getProducts(1, 100, $lastSync);

// Aktualizujte čas synchronizace
saveLastSyncTime(date('Y-m-d H:i:s'));
```

---

## Podpora

Potřebujete pomoc? Kontaktujte nás:

- **Email:** tomas@pobo.cz
- **Dokumentace:** https://docs.pobo.space

---

**Hotovo!** Nyní byste měli být schopni integrovat REST API V2 do vašeho systému.
