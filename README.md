# Pobo API Documentation

Official documentation and examples for Pobo REST API V2 and Webhooks.

## Documentation

1. [Data Import (PHP SDK)](doc/IMPORT.md) - Import products, categories, parameters, and blogs
2. [Data Export (PHP SDK)](doc/EXPORT.md) - Export products, categories, and blogs with content
3. [Webhook Processing (PHP SDK)](doc/WEBHOOK.md) - Receive and verify webhook notifications

## Quick Start

### Requirements

- PHP 8.3+
- Docker & Docker Compose

### Installation

```bash
# Clone repository
git clone https://github.com/pobo-builder/pobo-api-doc.git
cd pobo-api-doc

# Copy environment file
cp .env.example .env

# Edit .env and add your API token
nano .env

# Start Docker
docker compose up -d

# Install dependencies
docker compose exec pobo-webhook-php composer install
```

### Configuration

Edit `.env` file:

```env
POBO_API_TOKEN=your_api_token_here
WEBHOOK_SECRET=your_webhook_secret_here
```

### Running Examples

```bash
# Run import example
make run-import

# Run export example
make run-export
```

## SDK

This project uses the official [Pobo PHP SDK](https://github.com/pobo-builder/php-sdk):

```bash
composer require pobo-builder/php-sdk
```

## API Endpoints

| Endpoint                  | Import (POST) | Export (GET) |
|---------------------------|---------------|--------------|
| `/api/v2/rest/parameters` | ✅             | ❌            |
| `/api/v2/rest/categories` | ✅             | ✅            |
| `/api/v2/rest/products`   | ✅             | ✅            |
| `/api/v2/rest/blogs`      | ✅             | ✅            |

## Support

- **Email:** tomas@pobo.cz
- **SDK:** https://github.com/pobo-builder/php-sdk
- **Packagist:** https://packagist.org/packages/pobo-builder/php-sdk
