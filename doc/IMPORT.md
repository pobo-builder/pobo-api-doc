# Data Import (PHP SDK)

This guide explains how to import products, categories, parameters, and blogs using the official PHP SDK.

---

## Quick Start

### 1. SDK Installation

```bash
composer require pobo-builder/php-sdk
```

### 2. Getting API Token

1. Log in to [administration](http://client.pobo.space)
2. Go to [Settings → REST API](http://client.pobo.space/merchant/setting/rest-api)
3. Click **Generate Token**
4. Copy the generated **API token**

### 3. Client Initialization

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pobo\Sdk\PoboClient;

$client = new PoboClient(
    apiToken: 'your_api_token_here',
);
```

---

## Import Order

Import must be performed in this order (due to dependencies):

```
1. Parameters (no dependencies)
2. Categories (no dependencies)
3. Products (depends on categories and parameters)
4. Blogs (no dependencies)
```

---

## Import Parameters

Parameters are product properties (e.g., Color, Size). Each parameter contains values.

```php
<?php

use Pobo\Sdk\PoboClient;
use Pobo\Sdk\DTO\Parameter;
use Pobo\Sdk\DTO\ParameterValue;

$client = new PoboClient(apiToken: 'your_api_token');

$parameters = [
    new Parameter(
        id: 1,
        name: 'Color',
        values: [
            new ParameterValue(id: 1, value: 'Red'),
            new ParameterValue(id: 2, value: 'Blue'),
            new ParameterValue(id: 3, value: 'Green'),
        ],
    ),
    new Parameter(
        id: 2,
        name: 'Size',
        values: [
            new ParameterValue(id: 4, value: 'S'),
            new ParameterValue(id: 5, value: 'M'),
            new ParameterValue(id: 6, value: 'L'),
        ],
    ),
];

$result = $client->importParameters($parameters);

echo sprintf(
    "Parameters: imported=%d, updated=%d, values_imported=%d\n",
    $result->imported,
    $result->updated,
    $result->valuesImported ?? 0
);
```

### Parameter Fields

| Field            | Type    | Required | Description                        |
|------------------|---------|----------|------------------------------------|
| `id`             | integer | Yes      | Unique parameter ID                |
| `name`           | string  | Yes      | Parameter name (max 255 chars)     |
| `values`         | array   | Yes      | Array of parameter values (min 1)  |
| `values.*.id`    | integer | Yes      | Unique value ID                    |
| `values.*.value` | string  | Yes      | Parameter value (max 255 chars)    |

**Note:** Parameters do not support multi-language.

---

## Import Categories

```php
<?php

use Pobo\Sdk\PoboClient;
use Pobo\Sdk\DTO\Category;
use Pobo\Sdk\DTO\LocalizedString;
use Pobo\Sdk\Enum\Language;

$client = new PoboClient(apiToken: 'your_api_token');

$categories = [
    new Category(
        id: 'CAT-001',
        isVisible: true,
        name: LocalizedString::create('Electronics')
            ->withTranslation(Language::SK, 'Elektronika')
            ->withTranslation(Language::EN, 'Electronics'),
        url: LocalizedString::create('https://example.com/electronics')
            ->withTranslation(Language::SK, 'https://example.com/sk/elektronika')
            ->withTranslation(Language::EN, 'https://example.com/en/electronics'),
        description: LocalizedString::create('<p>Electronics category</p>'),
        images: ['https://example.com/images/electronics.jpg'],
    ),
    new Category(
        id: 'CAT-002',
        isVisible: true,
        name: LocalizedString::create('Clothing')
            ->withTranslation(Language::SK, 'Oblečenie')
            ->withTranslation(Language::EN, 'Clothing'),
        url: LocalizedString::create('https://example.com/clothing')
            ->withTranslation(Language::SK, 'https://example.com/sk/oblecenie')
            ->withTranslation(Language::EN, 'https://example.com/en/clothing'),
    ),
];

$result = $client->importCategories($categories);

echo sprintf(
    "Categories: imported=%d, updated=%d, skipped=%d\n",
    $result->imported,
    $result->updated,
    $result->skipped
);
```

### Category Fields

| Field             | Type    | Required | Description                           |
|-------------------|---------|----------|---------------------------------------|
| `id`              | string  | Yes      | Unique category ID (max 255 chars)    |
| `isVisible`       | boolean | Yes      | Whether category is visible           |
| `name`            | object  | Yes      | Category name in different languages  |
| `url`             | object  | Yes      | Category URL in different languages   |
| `description`     | object  | No       | Description with HTML (max 65000 chars)|
| `seoTitle`        | object  | No       | SEO title (max 255 chars)             |
| `seoDescription`  | object  | No       | SEO description (max 500 chars)       |
| `images`          | array   | No       | Array of image URLs (first = preview) |

---

## Import Products

```php
<?php

use Pobo\Sdk\PoboClient;
use Pobo\Sdk\DTO\Product;
use Pobo\Sdk\DTO\LocalizedString;
use Pobo\Sdk\Enum\Language;

$client = new PoboClient(apiToken: 'your_api_token');

$products = [
    new Product(
        id: 'PROD-001',
        isVisible: true,
        name: LocalizedString::create('iPhone 15 Pro')
            ->withTranslation(Language::SK, 'iPhone 15 Pro')
            ->withTranslation(Language::EN, 'iPhone 15 Pro'),
        url: LocalizedString::create('https://example.com/iphone-15-pro')
            ->withTranslation(Language::SK, 'https://example.com/sk/iphone-15-pro')
            ->withTranslation(Language::EN, 'https://example.com/en/iphone-15-pro'),
        shortDescription: LocalizedString::create('Latest iPhone with A17 Pro chip')
            ->withTranslation(Language::SK, 'Najnovší iPhone s čipom A17 Pro')
            ->withTranslation(Language::EN, 'Latest iPhone with A17 Pro chip'),
        description: LocalizedString::create('<p>iPhone 15 Pro is the most advanced smartphone.</p>'),
        images: [
            'https://example.com/images/iphone-1.jpg',
            'https://example.com/images/iphone-2.jpg',
        ],
        categoriesIds: ['CAT-001'],
        parametersIds: [1, 2],
    ),
    new Product(
        id: 'PROD-002',
        isVisible: true,
        name: LocalizedString::create('Basic T-Shirt')
            ->withTranslation(Language::SK, 'Tričko Basic')
            ->withTranslation(Language::EN, 'Basic T-Shirt'),
        url: LocalizedString::create('https://example.com/basic-t-shirt')
            ->withTranslation(Language::SK, 'https://example.com/sk/tricko-basic')
            ->withTranslation(Language::EN, 'https://example.com/en/basic-t-shirt'),
        categoriesIds: ['CAT-002'],
        parametersIds: [1, 4, 5, 6],
    ),
];

$result = $client->importProducts($products);

echo sprintf(
    "Products: imported=%d, updated=%d, skipped=%d, errors=%d\n",
    $result->imported,
    $result->updated,
    $result->skipped,
    count($result->errors)
);

// Error handling
if ($result->hasErrors() === true) {
    foreach ($result->errors as $error) {
        echo sprintf(
            "Error for product %s: %s\n",
            $error['id'],
            implode(', ', $error['errors'])
        );
    }
}
```

### Product Fields

| Field              | Type    | Required | Description                            |
|--------------------|---------|----------|----------------------------------------|
| `id`               | string  | Yes      | Unique product ID (max 255 chars)      |
| `isVisible`        | boolean | Yes      | Whether product is visible             |
| `name`             | object  | Yes      | Product name in different languages    |
| `url`              | object  | Yes      | Product URL (must start with `https://`)|
| `shortDescription` | object  | No       | Short description (max 65000 chars)    |
| `description`      | object  | No       | Long description with HTML (max 65000 chars)|
| `seoTitle`         | object  | No       | SEO title (max 255 chars)              |
| `seoDescription`   | object  | No       | SEO description (max 500 chars)        |
| `images`           | array   | No       | Array of image URLs (first = preview)  |
| `categoriesIds`    | array   | No       | Array of category IDs                  |
| `parametersIds`    | array   | No       | Array of parameter value IDs           |

---

## Import Blogs

```php
<?php

use Pobo\Sdk\PoboClient;
use Pobo\Sdk\DTO\Blog;
use Pobo\Sdk\DTO\LocalizedString;
use Pobo\Sdk\Enum\Language;

$client = new PoboClient(apiToken: 'your_api_token');

$blogs = [
    new Blog(
        guid: '550e8400-e29b-41d4-a716-446655440001',
        category: 'news',
        isVisible: true,
        name: LocalizedString::create('How to choose the right smartphone')
            ->withTranslation(Language::SK, 'Ako vybrať správny smartphone')
            ->withTranslation(Language::EN, 'How to choose the right smartphone'),
        url: LocalizedString::create('https://example.com/blog/how-to-choose-smartphone')
            ->withTranslation(Language::SK, 'https://example.com/sk/blog/ako-vybrat-smartphone')
            ->withTranslation(Language::EN, 'https://example.com/en/blog/how-to-choose-smartphone'),
        description: LocalizedString::create('<p>A guide to choosing a smartphone.</p>')
            ->withTranslation(Language::SK, '<p>Sprievodca výberom smartphonu.</p>')
            ->withTranslation(Language::EN, '<p>A guide to choosing a smartphone.</p>'),
        seoTitle: LocalizedString::create('How to choose smartphone | Blog')
            ->withTranslation(Language::SK, 'Ako vybrať smartphone | Blog')
            ->withTranslation(Language::EN, 'How to choose smartphone | Blog'),
        seoDescription: LocalizedString::create('Complete guide to choosing a smartphone.')
            ->withTranslation(Language::SK, 'Kompletný sprievodca výberom smartphonu.')
            ->withTranslation(Language::EN, 'Complete guide to choosing a smartphone.'),
        images: ['https://example.com/images/blog-smartphone.jpg'],
    ),
];

$result = $client->importBlogs($blogs);

echo sprintf(
    "Blogs: imported=%d, updated=%d, skipped=%d\n",
    $result->imported,
    $result->updated,
    $result->skipped
);
```

### Blog Fields

| Field             | Type    | Required | Description                            |
|-------------------|---------|----------|----------------------------------------|
| `guid`            | string  | Yes      | Unique blog GUID (UUID format)         |
| `category`        | string  | No       | Blog category (e.g., 'news', 'tips')   |
| `isVisible`       | boolean | Yes      | Whether blog is visible                |
| `name`            | object  | Yes      | Blog name in different languages       |
| `url`             | object  | Yes      | Blog URL in different languages        |
| `description`     | object  | No       | Description with HTML (max 65000 chars)|
| `seoTitle`        | object  | No       | SEO title (max 255 chars)              |
| `seoDescription`  | object  | No       | SEO description (max 500 chars)        |
| `images`          | array   | No       | Array of image URLs (first = preview)  |

---

## LocalizedString - Multi-language Texts

SDK uses `LocalizedString` for working with multi-language values:

```php
<?php

use Pobo\Sdk\DTO\LocalizedString;
use Pobo\Sdk\Enum\Language;

// Create with default value
$name = LocalizedString::create('Default name');

// Add translations (fluent interface)
$name = $name
    ->withTranslation(Language::CS, 'Czech name')
    ->withTranslation(Language::SK, 'Slovak name')
    ->withTranslation(Language::EN, 'English name');

// Get values
$name->getDefault();        // 'Default name'
$name->get(Language::CS);   // 'Czech name'
$name->get(Language::DE);   // null (not set)

// Convert to array
$name->toArray();
// ['default' => 'Default name', 'cs' => 'Czech name', 'sk' => 'Slovak name', 'en' => 'English name']
```

### Supported Languages

| Code      | Language             |
|-----------|----------------------|
| `default` | Default (required)   |
| `cs`      | Czech                |
| `sk`      | Slovak               |
| `en`      | English              |
| `de`      | German               |
| `pl`      | Polish               |
| `hu`      | Hungarian            |

**Rules:**

- `default` is always required
- If you use any other language, you must fill in `name` and `url` for it
- Other fields (`description`, `seo_title`, `seo_description`) can be `null`

---

## Error Handling

```php
<?php

use Pobo\Sdk\PoboClient;
use Pobo\Sdk\Exception\ApiException;
use Pobo\Sdk\Exception\ValidationException;

$client = new PoboClient(apiToken: 'your_api_token');

try {
    $result = $client->importProducts($products);
} catch (ValidationException $e) {
    // Local validation error (e.g., too many items)
    echo sprintf("Validation error: %s\n", $e->getMessage());
    print_r($e->errors);
} catch (ApiException $e) {
    // API error (4xx, 5xx)
    echo sprintf("API error (%d): %s\n", $e->httpCode, $e->getMessage());
    print_r($e->responseBody);
}
```

### Error Responses

| HTTP Code | Description          | Solution                                    |
|-----------|----------------------|---------------------------------------------|
| 401       | Unauthorized         | Check API token                             |
| 422       | Validation error     | Check data (max 100 items)                  |
| 500       | Server error         | Contact support                             |

---

## Limits

| Limit                              | Value        |
|------------------------------------|--------------|
| Max items per request (import)     | 100          |
| Max product/category ID length     | 255 chars    |
| Max name length                    | 250 chars    |
| Max URL length                     | 255 chars    |
| Max description length             | 65,000 chars |
| Max SEO description length         | 500 chars    |
| Max image URL length               | 650 chars    |

---

## Complete Example

See file `src/import.php` for a complete import example.

```bash
php src/import.php
```

---

## Support

Need help? Contact us:

- **Email:** tomas@pobo.cz
- **SDK:** https://github.com/pobo-builder/php-sdk
- **Packagist:** https://packagist.org/packages/pobo-builder/php-sdk
