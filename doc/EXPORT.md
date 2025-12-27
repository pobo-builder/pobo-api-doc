# Data Export (PHP SDK)

This guide explains how to export (read) products, categories, and blogs using the official PHP SDK.

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

## Export Products

### Getting One Page

```php
<?php

use Pobo\Sdk\PoboClient;

$client = new PoboClient(apiToken: 'your_api_token');

// Get one page of products
$response = $client->getProducts(page: 1, perPage: 50);

echo sprintf(
    "Page %d of %d (total %d products)\n",
    $response->currentPage,
    $response->getTotalPages(),
    $response->total
);

foreach ($response->data as $product) {
    echo sprintf("- %s: %s\n", $product->id, $product->name->getDefault());
}
```

### Iterating Through All Products

```php
<?php

// Automatic pagination - iterates through all products
foreach ($client->iterateProducts() as $product) {
    echo sprintf("%s: %s\n", $product->id, $product->name->getDefault());
}
```

### Filtering

```php
<?php

// Filter by update date
$since = new DateTime('-7 days');
$response = $client->getProducts(lastUpdateFrom: $since);

// Filter only edited products
$response = $client->getProducts(isEdited: true);

// Combine filters with iteration
$since = new DateTime('-1 day');
foreach ($client->iterateProducts(lastUpdateFrom: $since) as $product) {
    processProduct($product);
}
```

### Query Parameters

| Parameter          | Type     | Description                                          |
|--------------------|----------|------------------------------------------------------|
| `page`             | integer  | Page number (default: 1)                             |
| `perPage`          | integer  | Items per page (max 100, default: 100)               |
| `isEdited`         | boolean  | Filter by edit status                                |
| `lastUpdateFrom`   | DateTime | Filter by update date                                |

### Product Structure

```php
$product->id;               // string - Product ID
$product->guid;             // ?string - Product GUID
$product->isVisible;        // bool - visibility
$product->isLoaded;         // ?bool - whether product is loaded
$product->name;             // LocalizedString - name
$product->url;              // LocalizedString - URL
$product->shortDescription; // ?LocalizedString - short description
$product->description;      // ?LocalizedString - long description
$product->seoTitle;         // ?LocalizedString - SEO title
$product->seoDescription;   // ?LocalizedString - SEO description
$product->content;          // ?Content - generated content (HTML/Marketplace)
$product->images;           // array - image URLs
$product->categories;       // array - categories
$product->createdAt;        // ?DateTimeInterface
$product->updatedAt;        // ?DateTimeInterface
```

---

## Export Categories

### Getting One Page

```php
<?php

use Pobo\Sdk\PoboClient;

$client = new PoboClient(apiToken: 'your_api_token');

// Get categories
$response = $client->getCategories(page: 1, perPage: 100);

echo sprintf(
    "Page %d of %d (total %d categories)\n",
    $response->currentPage,
    $response->getTotalPages(),
    $response->total
);

foreach ($response->data as $category) {
    echo sprintf("- %s: %s\n", $category->id, $category->name->getDefault());
}
```

### Iterating Through All Categories

```php
<?php

// Automatic pagination
foreach ($client->iterateCategories() as $category) {
    processCategory($category);
}
```

### Category Structure

```php
$category->id;             // string - Category ID
$category->guid;           // ?string - Category GUID
$category->isVisible;      // bool - visibility
$category->isLoaded;       // ?bool - whether category is loaded
$category->name;           // LocalizedString - name
$category->url;            // LocalizedString - URL
$category->description;    // ?LocalizedString - description
$category->seoTitle;       // ?LocalizedString - SEO title
$category->seoDescription; // ?LocalizedString - SEO description
$category->content;        // ?Content - generated content (HTML/Marketplace)
$category->images;         // array - image URLs
$category->createdAt;      // ?DateTimeInterface
$category->updatedAt;      // ?DateTimeInterface
```

---

## Export Blogs

### Getting One Page

```php
<?php

use Pobo\Sdk\PoboClient;

$client = new PoboClient(apiToken: 'your_api_token');

// Get blogs
$response = $client->getBlogs(page: 1, perPage: 100);

echo sprintf(
    "Page %d of %d (total %d blogs)\n",
    $response->currentPage,
    $response->getTotalPages(),
    $response->total
);

foreach ($response->data as $blog) {
    echo sprintf("- %s: %s\n", $blog->guid, $blog->name->getDefault());
}
```

### Iterating Through All Blogs

```php
<?php

// Automatic pagination
foreach ($client->iterateBlogs() as $blog) {
    processBlog($blog);
}
```

### Blog Structure

```php
$blog->id;             // ?int - internal blog ID
$blog->guid;           // ?string - Blog GUID
$blog->category;       // ?string - blog category
$blog->isVisible;      // bool - visibility
$blog->isLoaded;       // ?bool - whether blog is loaded
$blog->name;           // ?LocalizedString - name
$blog->url;            // ?LocalizedString - URL
$blog->description;    // ?LocalizedString - description
$blog->seoTitle;       // ?LocalizedString - SEO title
$blog->seoDescription; // ?LocalizedString - SEO description
$blog->content;        // ?Content - generated content (HTML/Marketplace)
$blog->images;         // array - image URLs
$blog->createdAt;      // ?DateTimeInterface
$blog->updatedAt;      // ?DateTimeInterface
```

---

## Content (HTML/Marketplace)

Products, categories, and blogs contain a `content` field with generated HTML content:

```php
<?php

use Pobo\Sdk\Enum\Language;

foreach ($client->iterateProducts() as $product) {
    if ($product->content !== null) {
        // HTML content for web
        $htmlCs = $product->content->getHtml(Language::CS);
        $htmlSk = $product->content->getHtml(Language::SK);
        $htmlEn = $product->content->getHtml(Language::EN);

        // Content for marketplace
        $marketplaceCs = $product->content->getMarketplace(Language::CS);
        $marketplaceSk = $product->content->getMarketplace(Language::SK);

        // Default content
        $htmlDefault = $product->content->getHtmlDefault();
        $marketplaceDefault = $product->content->getMarketplaceDefault();
    }
}

// Same for categories
foreach ($client->iterateCategories() as $category) {
    if ($category->content !== null) {
        echo $category->content->getHtml(Language::CS);
    }
}

// Same for blogs
foreach ($client->iterateBlogs() as $blog) {
    if ($blog->content !== null) {
        echo $blog->content->getHtml(Language::CS);
    }
}
```

---

## PaginatedResponse

Methods `getProducts()`, `getCategories()`, and `getBlogs()` return `PaginatedResponse`:

```php
$response->data;           // array - array of products/categories/blogs
$response->total;          // int - total number of items
$response->perPage;        // int - items per page
$response->currentPage;    // int - current page
$response->lastPage;       // int - last page

// Helper methods
$response->getTotalPages();  // int - total number of pages
$response->hasMorePages();   // bool - are there more pages?
```

---

## LocalizedString - Reading Multi-language Values

```php
<?php

use Pobo\Sdk\Enum\Language;

// Get default value
$product->name->getDefault();  // 'iPhone 15 Pro'

// Get specific translation
$product->name->get(Language::SK);  // 'iPhone 15 Pro' or null
$product->name->get(Language::EN);  // 'iPhone 15 Pro'

// Convert to array
$product->name->toArray();
// ['default' => '...', 'cs' => '...', 'sk' => '...', 'en' => '...']
```

---

## Error Handling

```php
<?php

use Pobo\Sdk\PoboClient;
use Pobo\Sdk\Exception\ApiException;

$client = new PoboClient(apiToken: 'your_api_token');

try {
    $response = $client->getProducts();
} catch (ApiException $e) {
    echo sprintf("API error (%d): %s\n", $e->httpCode, $e->getMessage());
    print_r($e->responseBody);
}
```

### Error Responses

| HTTP Code | Description          | Solution                                    |
|-----------|----------------------|---------------------------------------------|
| 401       | Unauthorized         | Check API token                             |
| 500       | Server error         | Contact support                             |

---

## Limits

| Limit                              | Value        |
|------------------------------------|--------------|
| Max items per page (export)        | 100          |

---

## Complete Example

See file `src/export.php` for a complete export example.

```bash
php src/export.php
```

---

## Support

Need help? Contact us:

- **Email:** tomas@pobo.cz
- **SDK:** https://github.com/pobo-builder/php-sdk
- **Packagist:** https://packagist.org/packages/pobo-builder/php-sdk
