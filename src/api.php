<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// ========================================
// KONFIGURACE
// ========================================

$apiToken = '....'; // Váš API token z administrace
$baseUrl = 'https://api.pobo.space';

// ========================================
// LOGGER
// ========================================

$logger = new Logger('api');

$dateFormat = "Y-m-d H:i:s";
$output = "[%datetime%] %channel%.%level_name%: %message% %context%\n";
$formatter = new LineFormatter($output, $dateFormat);

$streamHandler = new StreamHandler('/var/www/html/logs/api.log', Logger::INFO);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

// ========================================
// API CLIENT
// ========================================

/**
 * Provede HTTP požadavek na API
 *
 * @param string $method HTTP metoda (GET, POST)
 * @param string $endpoint API endpoint
 * @param array|null $data Data pro POST požadavek
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Response data
 * @throws Exception
 */
function apiRequest(
    string $method,
    string $endpoint,
    ?array $data,
    string $apiToken,
    string $baseUrl,
    Logger $logger
): array {
    $url = $baseUrl . $endpoint;

    $logger->info('API request', [
        'method' => $method,
        'endpoint' => $endpoint,
    ]);

    $ch = curl_init($url);

    $headers = [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
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
        $logger->error('cURL error', ['error' => $error]);
        throw new Exception("cURL error: $error");
    }

    $logger->info('API response', [
        'http_code' => $httpCode,
        'response_length' => strlen($response),
    ]);

    if ($httpCode >= 400) {
        $logger->error('API error', [
            'http_code' => $httpCode,
            'response' => $response,
        ]);
        throw new Exception("API error: HTTP $httpCode - $response");
    }

    return json_decode($response, true) ?? [];
}

/**
 * Import produktů
 *
 * @param array $products Pole produktů k importu
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Response data
 */
function importProducts(array $products, string $apiToken, string $baseUrl, Logger $logger): array
{
    $logger->info('Importing products', ['count' => count($products)]);

    $result = apiRequest('POST', '/api/v2/rest/products', $products, $apiToken, $baseUrl, $logger);

    $logger->info('Products import completed', [
        'imported' => $result['imported'] ?? 0,
        'updated' => $result['updated'] ?? 0,
        'skipped' => $result['skipped'] ?? 0,
        'errors_count' => count($result['errors'] ?? []),
    ]);

    return $result;
}

/**
 * Import kategorií
 *
 * @param array $categories Pole kategorií k importu
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Response data
 */
function importCategories(array $categories, string $apiToken, string $baseUrl, Logger $logger): array
{
    $logger->info('Importing categories', ['count' => count($categories)]);

    $result = apiRequest('POST', '/api/v2/rest/categories', $categories, $apiToken, $baseUrl, $logger);

    $logger->info('Categories import completed', [
        'imported' => $result['imported'] ?? 0,
        'updated' => $result['updated'] ?? 0,
        'skipped' => $result['skipped'] ?? 0,
        'errors_count' => count($result['errors'] ?? []),
    ]);

    return $result;
}

/**
 * Import parametrů
 *
 * @param array $parameters Pole parametrů k importu
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Response data
 */
function importParameters(array $parameters, string $apiToken, string $baseUrl, Logger $logger): array
{
    $logger->info('Importing parameters', ['count' => count($parameters)]);

    $result = apiRequest('POST', '/api/v2/rest/parameters', $parameters, $apiToken, $baseUrl, $logger);

    $logger->info('Parameters import completed', [
        'imported' => $result['imported'] ?? 0,
        'updated' => $result['updated'] ?? 0,
        'values_imported' => $result['values_imported'] ?? 0,
        'values_updated' => $result['values_updated'] ?? 0,
    ]);

    return $result;
}

/**
 * Export produktů
 *
 * @param int $page Číslo stránky
 * @param int $perPage Položek na stránku
 * @param string|null $lastUpdateFrom Filtr podle data aktualizace
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Response data
 */
function getProducts(
    int $page,
    int $perPage,
    ?string $lastUpdateFrom,
    string $apiToken,
    string $baseUrl,
    Logger $logger
): array {
    $params = array_filter([
        'page' => $page,
        'per_page' => $perPage,
        'last_update_time_from' => $lastUpdateFrom,
    ]);

    $endpoint = '/api/v2/rest/products?' . http_build_query($params);

    $logger->info('Fetching products', [
        'page' => $page,
        'per_page' => $perPage,
        'last_update_from' => $lastUpdateFrom,
    ]);

    $result = apiRequest('GET', $endpoint, null, $apiToken, $baseUrl, $logger);

    $logger->info('Products fetched', [
        'count' => count($result['data'] ?? []),
        'total' => $result['meta']['total'] ?? 0,
    ]);

    return $result;
}

/**
 * Export kategorií
 *
 * @param int $page Číslo stránky
 * @param int $perPage Položek na stránku
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Response data
 */
function getCategories(int $page, int $perPage, string $apiToken, string $baseUrl, Logger $logger): array
{
    $params = [
        'page' => $page,
        'per_page' => $perPage,
    ];

    $endpoint = '/api/v2/rest/categories?' . http_build_query($params);

    $logger->info('Fetching categories', [
        'page' => $page,
        'per_page' => $perPage,
    ]);

    $result = apiRequest('GET', $endpoint, null, $apiToken, $baseUrl, $logger);

    $logger->info('Categories fetched', [
        'count' => count($result['data'] ?? []),
        'total' => $result['meta']['total'] ?? 0,
    ]);

    return $result;
}

/**
 * Export všech produktů s paginací
 *
 * @param string|null $lastUpdateFrom Filtr podle data aktualizace
 * @param string $apiToken API token
 * @param string $baseUrl Base URL API
 * @param Logger $logger Logger instance
 * @return array Všechny produkty
 */
function getAllProducts(?string $lastUpdateFrom, string $apiToken, string $baseUrl, Logger $logger): array
{
    $allProducts = [];
    $page = 1;
    $perPage = 100;

    $logger->info('Fetching all products', ['last_update_from' => $lastUpdateFrom]);

    do {
        $result = getProducts($page, $perPage, $lastUpdateFrom, $apiToken, $baseUrl, $logger);
        $products = $result['data'] ?? [];
        $allProducts = array_merge($allProducts, $products);
        $page++;
    } while (count($products) === $perPage);

    $logger->info('All products fetched', ['total' => count($allProducts)]);

    return $allProducts;
}

// ========================================
// DEMO - UKÁZKOVÉ POUŽITÍ
// ========================================

try {
    $logger->info('=== API Demo Started ===');

    // 1. Import parametrů (nejdříve, protože produkty na ně odkazují)
    $logger->info('--- Step 1: Import Parameters ---');

    $parameters = [
        [
            'id' => 1,
            'name' => 'Barva',
            'values' => [
                ['id' => 1, 'value' => 'Červená'],
                ['id' => 2, 'value' => 'Modrá'],
                ['id' => 3, 'value' => 'Zelená'],
            ],
        ],
        [
            'id' => 2,
            'name' => 'Velikost',
            'values' => [
                ['id' => 4, 'value' => 'S'],
                ['id' => 5, 'value' => 'M'],
                ['id' => 6, 'value' => 'L'],
            ],
        ],
    ];

    $result = importParameters($parameters, $apiToken, $baseUrl, $logger);
    $logger->info('Parameters result', $result);

    // 2. Import kategorií
    $logger->info('--- Step 2: Import Categories ---');

    $categories = [
        [
            'id' => 'CAT-001',
            'is_visible' => true,
            'name' => [
                'default' => 'Elektronika',
                'sk' => 'Elektronika',
                'en' => 'Electronics',
            ],
            'url' => [
                'default' => 'https://example.com/elektronika',
                'sk' => 'https://example.com/sk/elektronika',
                'en' => 'https://example.com/en/electronics',
            ],
            'description' => [
                'default' => '<p>Kategorie elektroniky</p>',
            ],
        ],
        [
            'id' => 'CAT-002',
            'is_visible' => true,
            'name' => [
                'default' => 'Oblečení',
                'sk' => 'Oblečenie',
                'en' => 'Clothing',
            ],
            'url' => [
                'default' => 'https://example.com/obleceni',
                'sk' => 'https://example.com/sk/oblecenie',
                'en' => 'https://example.com/en/clothing',
            ],
        ],
    ];

    $result = importCategories($categories, $apiToken, $baseUrl, $logger);
    $logger->info('Categories result', $result);

    // 3. Import produktů
    $logger->info('--- Step 3: Import Products ---');

    $products = [
        [
            'id' => 'PROD-001',
            'is_visible' => true,
            'name' => [
                'default' => 'iPhone 15 Pro',
                'sk' => 'iPhone 15 Pro',
                'en' => 'iPhone 15 Pro',
            ],
            'url' => [
                'default' => 'https://example.com/iphone-15-pro',
                'sk' => 'https://example.com/sk/iphone-15-pro',
                'en' => 'https://example.com/en/iphone-15-pro',
            ],
            'short_description' => [
                'default' => 'Nejnovější iPhone s čipem A17 Pro',
            ],
            'description' => [
                'default' => '<p>iPhone 15 Pro je nejpokročilejší smartphone od Apple.</p>',
            ],
            'images' => [
                'https://example.com/images/iphone-15-pro-1.jpg',
                'https://example.com/images/iphone-15-pro-2.jpg',
            ],
            'categories_ids' => ['CAT-001'],
            'parameters_ids' => [1, 2], // Barva: Červená, Modrá
        ],
        [
            'id' => 'PROD-002',
            'is_visible' => true,
            'name' => [
                'default' => 'Tričko Basic',
                'sk' => 'Tričko Basic',
                'en' => 'Basic T-Shirt',
            ],
            'url' => [
                'default' => 'https://example.com/tricko-basic',
                'sk' => 'https://example.com/sk/tricko-basic',
                'en' => 'https://example.com/en/basic-t-shirt',
            ],
            'categories_ids' => ['CAT-002'],
            'parameters_ids' => [1, 4, 5, 6], // Barva + Velikosti S, M, L
        ],
    ];

    $result = importProducts($products, $apiToken, $baseUrl, $logger);
    $logger->info('Products result', $result);

    // 4. Export produktů
    $logger->info('--- Step 4: Export Products ---');

    $exportedProducts = getProducts(1, 50, null, $apiToken, $baseUrl, $logger);
    $logger->info('Exported products', [
        'page' => $exportedProducts['meta']['current_page'] ?? 1,
        'total' => $exportedProducts['meta']['total'] ?? 0,
    ]);

    // 5. Export kategorií
    $logger->info('--- Step 5: Export Categories ---');

    $exportedCategories = getCategories(1, 50, $apiToken, $baseUrl, $logger);
    $logger->info('Exported categories', [
        'page' => $exportedCategories['meta']['current_page'] ?? 1,
        'total' => $exportedCategories['meta']['total'] ?? 0,
    ]);

    // 6. Export produktů aktualizovaných od včerejška
    $logger->info('--- Step 6: Export Updated Products ---');

    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
    $updatedProducts = getAllProducts($yesterday, $apiToken, $baseUrl, $logger);
    $logger->info('Updated products since yesterday', [
        'count' => count($updatedProducts),
    ]);

    $logger->info('=== API Demo Completed Successfully ===');

} catch (Exception $e) {
    $logger->error('API Demo failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
