# Webhook Processing Guide (PHP SDK)

This guide explains how to receive and verify webhooks from our platform using the official PHP SDK.

---

## What is a Webhook?

A webhook is an HTTP POST notification that we send when an event occurs (e.g., products or categories update).
Webhook **does not contain data**, it only informs you that something has changed.

---

## Quick Start

### 1. SDK Installation

```bash
composer require pobo-builder/php-sdk
```

### 2. Setting up Webhook in Administration

1. Log in to [administration](http://client.pobo.space)
2. Go to [Settings → Webhook API](http://client.pobo.space/merchant/setting/webhook)
3. Click **Regenerate**
4. Configure webhook:
    - **URL**: `https://your-domain.com/webhook.php`
    - **Event**: `Products.update` or `Categories.update`
5. Copy the **Webhook Secret**

### 3. Implementation

```php
<?php
// webhook.php

require_once __DIR__ . '/vendor/autoload.php';

use Pobo\Sdk\WebhookHandler;
use Pobo\Sdk\Enum\WebhookEvent;
use Pobo\Sdk\Exception\WebhookException;

$webhookSecret = 'your_webhook_secret_from_administration';

$handler = new WebhookHandler(webhookSecret: $webhookSecret);

try {
    // Automatic signature verification and parsing
    $payload = $handler->handleFromGlobals();

    // Immediate response (within 10 seconds!)
    http_response_code(200);
    echo json_encode(['status' => 'ok']);

    // Process the event
    match ($payload->event) {
        WebhookEvent::PRODUCTS_UPDATE => syncProducts(),
        WebhookEvent::CATEGORIES_UPDATE => syncCategories(),
    };

} catch (WebhookException $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## Webhook Structure

### HTTP Request

```http
POST /webhook.php HTTP/1.1
Host: your-domain.com
Content-Type: application/json
X-Webhook-Signature: a3f2b1c8d9e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4e3f2a1
X-Webhook-Event: Products.update
```

### JSON Payload

```json
{
  "event": "Products.update",
  "timestamp": "2025-10-15T14:30:00Z",
  "eshop_id": 123
}
```

### Fields

| Field       | Type               | Description                                        |
|-------------|--------------------|----------------------------------------------------|
| `event`     | string             | Event type (`Products.update`, `Categories.update`) |
| `timestamp` | string (ISO 8601)  | Time when the event occurred                       |
| `eshop_id`  | integer            | Your e-shop ID                                     |

---

## WebhookHandler

SDK provides `WebhookHandler` for automatic verification and parsing of webhooks:

```php
<?php

use Pobo\Sdk\WebhookHandler;
use Pobo\Sdk\Exception\WebhookException;

$handler = new WebhookHandler(webhookSecret: 'your_secret');

// Option 1: Automatic processing from PHP globals
try {
    $payload = $handler->handleFromGlobals();
} catch (WebhookException $e) {
    // Invalid signature, missing data, unknown event...
}

// Option 2: Manual processing
try {
    $payload = $handler->handle(
        payload: file_get_contents('php://input'),
        signature: $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? ''
    );
} catch (WebhookException $e) {
    // Handle error
}
```

### WebhookPayload

```php
$payload->event;     // WebhookEvent enum (PRODUCTS_UPDATE, CATEGORIES_UPDATE)
$payload->timestamp; // DateTimeInterface
$payload->eshopId;   // int
```

---

## Complete Example

```php
<?php
// webhook.php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Pobo\Sdk\WebhookHandler;
use Pobo\Sdk\PoboClient;
use Pobo\Sdk\Enum\WebhookEvent;
use Pobo\Sdk\Exception\WebhookException;

// Configuration
$webhookSecret = 'your_webhook_secret';
$apiToken = 'your_api_token';

// Initialization
$handler = new WebhookHandler(webhookSecret: $webhookSecret);
$client = new PoboClient(apiToken: $apiToken);

/**
 * Sync products
 */
function syncProducts(PoboClient $client): void
{
    foreach ($client->iterateProducts() as $product) {
        // Update product in local database
        updateLocalProduct($product);
    }
}

/**
 * Sync categories
 */
function syncCategories(PoboClient $client): void
{
    foreach ($client->iterateCategories() as $category) {
        // Update category in local database
        updateLocalCategory($category);
    }
}

// Process webhook
try {
    $payload = $handler->handleFromGlobals();

    // Immediate response
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'event' => $payload->event->value]);

    // Close connection for long processing
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // Process based on event type
    match ($payload->event) {
        WebhookEvent::PRODUCTS_UPDATE => syncProducts($client),
        WebhookEvent::CATEGORIES_UPDATE => syncCategories($client),
    };

} catch (WebhookException $e) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## Manual Signature Verification

If you need to verify the signature manually:

```php
<?php

use Pobo\Sdk\WebhookHandler;

$handler = new WebhookHandler(webhookSecret: 'your_secret');

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Only verify signature (returns bool)
$isValid = $handler->verifySignature($payload, $signature);

if ($isValid === false) {
    http_response_code(401);
    die('Invalid signature');
}

// Signature is OK, process the data
$data = json_decode($payload, true);
```

---

## Important Notes

### Always respond quickly (within 10 seconds)

Webhook **MUST** receive a 200 response within 10 seconds, otherwise it will be retried.

```php
// CORRECT - response BEFORE processing
http_response_code(200);
echo json_encode(['status' => 'ok']);

// Close connection
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Only then long processing
syncProducts($client);
```

### Keep the secret confidential

```php
// WRONG - secret directly in code
$secret = 'abc123...';

// CORRECT - in environment variable
$secret = getenv('WEBHOOK_SECRET');

// OR in config file outside Git
$config = require '/etc/app/config.php';
$secret = $config['webhook_secret'];
```

---

## Exception Types

| Exception          | When it occurs                              |
|--------------------|---------------------------------------------|
| `WebhookException` | Invalid signature, missing data, unknown event |

```php
use Pobo\Sdk\Exception\WebhookException;

try {
    $payload = $handler->handleFromGlobals();
} catch (WebhookException $e) {
    // $e->getMessage() contains error description:
    // - "Missing webhook signature header"
    // - "Invalid webhook signature"
    // - "Invalid webhook payload - could not parse JSON"
    // - "Unknown webhook event: XYZ"
}
```

---

## Testing

### 1. Test from Administration

In administration click on **Test webhook**.

### 2. Manual Test Using cURL

```bash
# Generate test signature
SECRET="your_secret"
PAYLOAD='{"event":"Products.update","timestamp":"2025-10-15T14:30:00Z","eshop_id":123}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Send test webhook
curl -X POST https://your-domain.com/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

---

## Supported Events

| Event               | When triggered                              |
|---------------------|---------------------------------------------|
| `Products.update`   | When user clicks "Export products"          |
| `Categories.update` | When user clicks "Export categories"        |

---

## Possible Errors

### `401 Invalid signature`

**Cause:** Incorrect webhook secret or corrupted payload

**Solution:**
- Check that you have the correct secret from administration
- Make sure the webhook URL is correct

### `Timeout`

**Cause:** Your endpoint did not respond within 10 seconds

**Solution:**
- Respond immediately and process only after `fastcgi_finish_request()`

---

## Support

Need help? Contact us:

- **Email:** tomas@pobo.cz
- **SDK:** https://github.com/pobo-builder/php-sdk
- **Packagist:** https://packagist.org/packages/pobo-builder/php-sdk
