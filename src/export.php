<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Pobo\Sdk\PoboClient;
use Pobo\Sdk\Enum\Language;
use Pobo\Sdk\Exception\ApiException;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required('POBO_API_TOKEN');

$client = new PoboClient(apiToken: $_ENV['POBO_API_TOKEN']);

$languages = [Language::CS, Language::SK, Language::EN, Language::DE, Language::PL, Language::HU];

function printLocalizedString(string $label, $localizedString, array $languages, int $indent = 2): void
{
    if ($localizedString === null) {
        return;
    }
    $prefix = str_repeat(' ', $indent);
    echo "{$prefix}{$label}:\n";
    echo "{$prefix}  default: {$localizedString->getDefault()}\n";
    foreach ($languages as $lang) {
        $value = $localizedString->get($lang);
        if ($value !== null) {
            echo "{$prefix}  {$lang->value}: {$value}\n";
        }
    }
}

function printContent($content, array $languages, int $indent = 2): void
{
    if ($content === null) {
        return;
    }
    $prefix = str_repeat(' ', $indent);

    $htmlDefault = $content->getHtmlDefault();
    if ($htmlDefault !== null) {
        echo "{$prefix}Content HTML:\n";
        echo "{$prefix}  default: " . substr($htmlDefault, 0, 100) . (strlen($htmlDefault) > 100 ? '...' : '') . "\n";
        foreach ($languages as $lang) {
            $value = $content->getHtml($lang);
            if ($value !== null) {
                echo "{$prefix}  {$lang->value}: " . substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '') . "\n";
            }
        }
    }

    $marketplaceDefault = $content->getMarketplaceDefault();
    if ($marketplaceDefault !== null) {
        echo "{$prefix}Content Marketplace:\n";
        echo "{$prefix}  default: " . substr($marketplaceDefault, 0, 100) . (strlen($marketplaceDefault) > 100 ? '...' : '') . "\n";
        foreach ($languages as $lang) {
            $value = $content->getMarketplace($lang);
            if ($value !== null) {
                echo "{$prefix}  {$lang->value}: " . substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '') . "\n";
            }
        }
    }
}

try {
    echo "=== Export Started ===\n\n";

    echo "--- Categories ---\n\n";

    $response = $client->getCategories(page: 1, perPage: 100);
    echo sprintf("Total: %d categories (page %d of %d)\n\n", $response->total, $response->currentPage, $response->getTotalPages());

    foreach ($response->data as $category) {
        echo sprintf("ID: %s\n", $category->id);
        if ($category->guid !== null) {
            echo sprintf("  GUID: %s\n", $category->guid);
        }
        echo sprintf("  Visible: %s\n", $category->isVisible ? 'Yes' : 'No');
        if ($category->isLoaded !== null) {
            echo sprintf("  Loaded: %s\n", $category->isLoaded ? 'Yes' : 'No');
        }

        printLocalizedString('Name', $category->name, $languages);
        printLocalizedString('URL', $category->url, $languages);
        printLocalizedString('Description', $category->description, $languages);
        printLocalizedString('SEO Title', $category->seoTitle, $languages);
        printLocalizedString('SEO Description', $category->seoDescription, $languages);
        printContent($category->content, $languages);

        if ($category->images !== []) {
            echo sprintf("  Images: %d\n", count($category->images));
            foreach ($category->images as $image) {
                echo sprintf("    - %s\n", $image);
            }
        }

        if ($category->createdAt !== null) {
            echo sprintf("  Created: %s\n", $category->createdAt->format('Y-m-d H:i:s'));
        }
        if ($category->updatedAt !== null) {
            echo sprintf("  Updated: %s\n", $category->updatedAt->format('Y-m-d H:i:s'));
        }

        echo "\n";
    }

    echo "--- Products ---\n\n";

    $response = $client->getProducts(page: 1, perPage: 100);
    echo sprintf("Total: %d products (page %d of %d)\n\n", $response->total, $response->currentPage, $response->getTotalPages());

    foreach ($response->data as $product) {
        echo sprintf("ID: %s\n", $product->id);
        if ($product->guid !== null) {
            echo sprintf("  GUID: %s\n", $product->guid);
        }
        echo sprintf("  Visible: %s\n", $product->isVisible ? 'Yes' : 'No');
        if ($product->isLoaded !== null) {
            echo sprintf("  Loaded: %s\n", $product->isLoaded ? 'Yes' : 'No');
        }

        printLocalizedString('Name', $product->name, $languages);
        printLocalizedString('URL', $product->url, $languages);
        printLocalizedString('Short Description', $product->shortDescription, $languages);
        printLocalizedString('Description', $product->description, $languages);
        printLocalizedString('SEO Title', $product->seoTitle, $languages);
        printLocalizedString('SEO Description', $product->seoDescription, $languages);
        printContent($product->content, $languages);

        if ($product->images !== []) {
            echo sprintf("  Images: %d\n", count($product->images));
            foreach ($product->images as $image) {
                echo sprintf("    - %s\n", $image);
            }
        }

        if ($product->categories !== []) {
            echo "  Categories:\n";
            foreach ($product->categories as $cat) {
                echo sprintf("    - [%s] %s\n", $cat['id'], $cat['name']['default'] ?? 'N/A');
            }
        }

        if ($product->createdAt !== null) {
            echo sprintf("  Created: %s\n", $product->createdAt->format('Y-m-d H:i:s'));
        }
        if ($product->updatedAt !== null) {
            echo sprintf("  Updated: %s\n", $product->updatedAt->format('Y-m-d H:i:s'));
        }

        echo "\n";
    }

    echo "--- Blogs ---\n\n";

    $response = $client->getBlogs(page: 1, perPage: 100);
    echo sprintf("Total: %d blogs (page %d of %d)\n\n", $response->total, $response->currentPage, $response->getTotalPages());

    foreach ($response->data as $blog) {
        if ($blog->id !== null) {
            echo sprintf("ID: %s\n", $blog->id);
        }
        if ($blog->guid !== null) {
            echo sprintf("  GUID: %s\n", $blog->guid);
        }
        if ($blog->category !== null) {
            echo sprintf("  Category: %s\n", $blog->category);
        }
        echo sprintf("  Visible: %s\n", $blog->isVisible ? 'Yes' : 'No');
        if ($blog->isLoaded !== null) {
            echo sprintf("  Loaded: %s\n", $blog->isLoaded ? 'Yes' : 'No');
        }

        printLocalizedString('Name', $blog->name, $languages);
        printLocalizedString('URL', $blog->url, $languages);
        printLocalizedString('Description', $blog->description, $languages);
        printLocalizedString('SEO Title', $blog->seoTitle, $languages);
        printLocalizedString('SEO Description', $blog->seoDescription, $languages);
        printContent($blog->content, $languages);

        if ($blog->images !== []) {
            echo sprintf("  Images: %d\n", count($blog->images));
            foreach ($blog->images as $image) {
                echo sprintf("    - %s\n", $image);
            }
        }

        if ($blog->createdAt !== null) {
            echo sprintf("  Created: %s\n", $blog->createdAt->format('Y-m-d H:i:s'));
        }
        if ($blog->updatedAt !== null) {
            echo sprintf("  Updated: %s\n", $blog->updatedAt->format('Y-m-d H:i:s'));
        }

        echo "\n";
    }

    echo "--- Recently Updated (last 7 days) ---\n\n";

    $lastWeek = new DateTime('-7 days');

    echo "Products:\n";
    $count = 0;
    foreach ($client->iterateProducts(lastUpdateFrom: $lastWeek) as $product) {
        $count++;
        echo sprintf("  - %s: %s (updated: %s)\n",
            $product->id,
            $product->name->getDefault(),
            $product->updatedAt?->format('Y-m-d H:i:s') ?? 'N/A'
        );
    }
    echo sprintf("  Total: %d products\n\n", $count);

    echo "Categories:\n";
    $count = 0;
    foreach ($client->iterateCategories(lastUpdateFrom: $lastWeek) as $category) {
        $count++;
        echo sprintf("  - %s: %s (updated: %s)\n",
            $category->id,
            $category->name->getDefault(),
            $category->updatedAt?->format('Y-m-d H:i:s') ?? 'N/A'
        );
    }
    echo sprintf("  Total: %d categories\n\n", $count);

    echo "Blogs:\n";
    $count = 0;
    foreach ($client->iterateBlogs(lastUpdateFrom: $lastWeek) as $blog) {
        $count++;
        echo sprintf("  - %s: %s (updated: %s)\n",
            $blog->guid ?? $blog->id ?? 'N/A',
            $blog->name?->getDefault() ?? 'N/A',
            $blog->updatedAt?->format('Y-m-d H:i:s') ?? 'N/A'
        );
    }
    echo sprintf("  Total: %d blogs\n", $count);

    echo "\n=== Export Completed ===\n";

} catch (ApiException $e) {
    echo sprintf("API Error (%d): %s\n", $e->httpCode, $e->getMessage());
    exit(1);

} catch (Exception $e) {
    echo sprintf("Error: %s\n", $e->getMessage());
    exit(1);
}
