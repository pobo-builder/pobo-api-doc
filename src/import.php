<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Pobo\Sdk\PoboClient;
use Pobo\Sdk\DTO\Blog;
use Pobo\Sdk\DTO\Product;
use Pobo\Sdk\DTO\Category;
use Pobo\Sdk\DTO\Parameter;
use Pobo\Sdk\DTO\ParameterValue;
use Pobo\Sdk\DTO\LocalizedString;
use Pobo\Sdk\Enum\Language;
use Pobo\Sdk\Exception\ApiException;
use Pobo\Sdk\Exception\ValidationException;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required('POBO_API_TOKEN');

$client = new PoboClient(apiToken: $_ENV['POBO_API_TOKEN']);

try {
    echo "=== Import Started ===\n\n";

    echo "--- Step 1: Import Parameters ---\n";

    $parameters = [
        new Parameter(
            id: 1,
            name: 'Barva',
            values: [
                new ParameterValue(id: 1, value: 'Červená'),
                new ParameterValue(id: 2, value: 'Modrá'),
                new ParameterValue(id: 3, value: 'Zelená'),
            ],
        ),
        new Parameter(
            id: 2,
            name: 'Velikost',
            values: [
                new ParameterValue(id: 4, value: 'S'),
                new ParameterValue(id: 5, value: 'M'),
                new ParameterValue(id: 6, value: 'L'),
            ],
        ),
    ];

    $result = $client->importParameters($parameters);
    echo sprintf(
        "Parameters: imported=%d, updated=%d, values_imported=%d, values_updated=%d\n\n",
        $result->imported,
        $result->updated,
        $result->valuesImported ?? 0,
        $result->valuesUpdated ?? 0
    );

    echo "--- Step 2: Import Categories ---\n";

    $categories = [
        new Category(
            id: 'CAT-001',
            isVisible: true,
            name: LocalizedString::create('Elektronika')
                ->withTranslation(Language::SK, 'Elektronika')
                ->withTranslation(Language::EN, 'Electronics'),
            url: LocalizedString::create('https://example.com/elektronika')
                ->withTranslation(Language::SK, 'https://example.com/sk/elektronika')
                ->withTranslation(Language::EN, 'https://example.com/en/electronics'),
            description: LocalizedString::create('<p>Kategorie elektroniky</p>')
                ->withTranslation(Language::SK, '<p>Kategória elektroniky</p>')
                ->withTranslation(Language::EN, '<p>Electronics category</p>'),
            images: ['https://picsum.photos/seed/electronics/800/600'],
        ),
        new Category(
            id: 'CAT-002',
            isVisible: true,
            name: LocalizedString::create('Oblečení')
                ->withTranslation(Language::SK, 'Oblečenie')
                ->withTranslation(Language::EN, 'Clothing'),
            url: LocalizedString::create('https://example.com/obleceni')
                ->withTranslation(Language::SK, 'https://example.com/sk/oblecenie')
                ->withTranslation(Language::EN, 'https://example.com/en/clothing'),
            description: LocalizedString::create('<p>Kategorie oblečení</p>')
                ->withTranslation(Language::SK, '<p>Kategória oblečenia</p>')
                ->withTranslation(Language::EN, '<p>Clothing category</p>'),
            images: ['https://picsum.photos/seed/clothing-cat/800/600'],
        ),
    ];

    $result = $client->importCategories($categories);
    echo sprintf(
        "Categories: imported=%d, updated=%d, skipped=%d, errors=%d\n\n",
        $result->imported,
        $result->updated,
        $result->skipped,
        count($result->errors)
    );

    echo "--- Step 3: Import Products ---\n";

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
            shortDescription: LocalizedString::create('Nejnovější iPhone s čipem A17 Pro')
                ->withTranslation(Language::SK, 'Najnovší iPhone s čipom A17 Pro')
                ->withTranslation(Language::EN, 'Latest iPhone with A17 Pro chip'),
            description: LocalizedString::create('<p>iPhone 15 Pro je nejpokročilejší smartphone od Apple.</p>')
                ->withTranslation(Language::SK, '<p>iPhone 15 Pro je najpokročilejší smartphone od Apple.</p>')
                ->withTranslation(Language::EN, '<p>iPhone 15 Pro is the most advanced smartphone from Apple.</p>'),
            images: [
                'https://picsum.photos/seed/iphone1/800/600',
                'https://picsum.photos/seed/iphone2/800/600',
            ],
            categoriesIds: ['CAT-001'],
            parametersIds: [1, 2],
        ),
        new Product(
            id: 'PROD-002',
            isVisible: true,
            name: LocalizedString::create('Tričko Basic')
                ->withTranslation(Language::SK, 'Tričko Basic')
                ->withTranslation(Language::EN, 'Basic T-Shirt'),
            url: LocalizedString::create('https://example.com/tricko-basic')
                ->withTranslation(Language::SK, 'https://example.com/sk/tricko-basic')
                ->withTranslation(Language::EN, 'https://example.com/en/basic-t-shirt'),
            shortDescription: LocalizedString::create('Pohodlné bavlněné tričko')
                ->withTranslation(Language::SK, 'Pohodlné bavlnené tričko')
                ->withTranslation(Language::EN, 'Comfortable cotton t-shirt'),
            description: LocalizedString::create('<p>Pohodlné bavlněné tričko pro každodenní nošení.</p>')
                ->withTranslation(Language::SK, '<p>Pohodlné bavlnené tričko na každodenné nosenie.</p>')
                ->withTranslation(Language::EN, '<p>Comfortable cotton t-shirt for everyday wear.</p>'),
            images: [
                'https://picsum.photos/seed/tshirt1/800/600',
                'https://picsum.photos/seed/tshirt2/800/600',
            ],
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

    if ($result->hasErrors() === true) {
        echo "\nImport errors:\n";
        foreach ($result->errors as $error) {
            echo sprintf("  - [%d] %s: %s\n", $error['index'], $error['id'], implode(', ', $error['errors']));
        }
    }

    echo "\n--- Step 4: Import Blogs ---\n";

    $blogs = [
        new Blog(
            guid: '550e8400-e29b-41d4-a716-446655440001',
            category: 'news',
            isVisible: true,
            name: LocalizedString::create('Jak vybrat správný smartphone')
                ->withTranslation(Language::SK, 'Ako vybrať správny smartphone')
                ->withTranslation(Language::EN, 'How to choose the right smartphone'),
            url: LocalizedString::create('https://example.com/blog/jak-vybrat-smartphone')
                ->withTranslation(Language::SK, 'https://example.com/sk/blog/ako-vybrat-smartphone')
                ->withTranslation(Language::EN, 'https://example.com/en/blog/how-to-choose-smartphone'),
            description: LocalizedString::create('<p>Průvodce výběrem smartphonu pro rok 2024.</p>')
                ->withTranslation(Language::SK, '<p>Sprievodca výberom smartphonu pre rok 2024.</p>')
                ->withTranslation(Language::EN, '<p>A guide to choosing a smartphone for 2024.</p>'),
            seoTitle: LocalizedString::create('Jak vybrat smartphone | Blog')
                ->withTranslation(Language::SK, 'Ako vybrať smartphone | Blog')
                ->withTranslation(Language::EN, 'How to choose smartphone | Blog'),
            seoDescription: LocalizedString::create('Kompletní průvodce výběrem smartphonu.')
                ->withTranslation(Language::SK, 'Kompletný sprievodca výberom smartphonu.')
                ->withTranslation(Language::EN, 'Complete guide to choosing a smartphone.'),
            images: ['https://picsum.photos/seed/smartphone/800/600'],
        ),
        new Blog(
            guid: '550e8400-e29b-41d4-a716-446655440002',
            category: 'tips',
            isVisible: true,
            name: LocalizedString::create('5 tipů pro péči o oblečení')
                ->withTranslation(Language::SK, '5 tipov pre starostlivosť o oblečenie')
                ->withTranslation(Language::EN, '5 tips for clothing care'),
            url: LocalizedString::create('https://example.com/blog/tipy-pece-obleceni')
                ->withTranslation(Language::SK, 'https://example.com/sk/blog/tipy-starostlivost-oblecenie')
                ->withTranslation(Language::EN, 'https://example.com/en/blog/clothing-care-tips'),
            description: LocalizedString::create('<p>Naučte se správně pečovat o své oblečení.</p>')
                ->withTranslation(Language::SK, '<p>Naučte sa správne starať o svoje oblečenie.</p>')
                ->withTranslation(Language::EN, '<p>Learn how to properly care for your clothes.</p>'),
            images: ['https://picsum.photos/seed/clothing/800/600'],
        ),
    ];

    $result = $client->importBlogs($blogs);
    echo sprintf(
        "Blogs: imported=%d, updated=%d, skipped=%d, errors=%d\n",
        $result->imported,
        $result->updated,
        $result->skipped,
        count($result->errors)
    );

    if ($result->hasErrors() === true) {
        echo "\nBlog import errors:\n";
        foreach ($result->errors as $error) {
            echo sprintf("  - [%d] %s: %s\n", $error['index'], $error['guid'] ?? $error['id'] ?? 'unknown', implode(', ', $error['errors']));
        }
    }

    echo "\n=== Import Completed Successfully ===\n";

} catch (ValidationException $e) {
    echo sprintf("Validation Error: %s\n", $e->getMessage());
    print_r($e->errors);
    exit(1);

} catch (ApiException $e) {
    echo sprintf("API Error (%d): %s\n", $e->httpCode, $e->getMessage());
    exit(1);

} catch (Exception $e) {
    echo sprintf("Error: %s\n", $e->getMessage());
    exit(1);
}
