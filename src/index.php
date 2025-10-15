<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

$webhookSecret = '....';

$logger = new Logger('webhook');

$dateFormat = "Y-m-d H:i:s";
$output = "[%datetime%] %channel%.%level_name%: %message% %context%\n";
$formatter = new LineFormatter($output, $dateFormat);

$streamHandler = new StreamHandler('/var/www/html/logs/webhook.log', Logger::INFO);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

/**
 * @param string $payload
 * @param string $signature
 * @param string $secret
 * @return bool
 */
function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
{
    $calculatedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($calculatedSignature, $signature);
}

/**
 * @param array $data
 * @param Logger $logger
 * @return void
 */
function processProductUpdate(array $data, Logger $logger): void
{
    $logger->info('Processing product update', [
        'eshop_id' => $data['eshop_id'],
        'timestamp' => $data['timestamp']
    ]);
    $logger->info('Product update completed successfully');
}

/**
 * @param array $data
 * @param Logger $logger
 * @return void
 */
function processCategoryUpdate(array $data, Logger $logger): void
{
    $logger->info('Processing category update', [
        'eshop_id' => $data['eshop_id'],
        'timestamp' => $data['timestamp']
    ]);
    $logger->info('Category update completed successfully');
}


try {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

    $logger->info('Webhook received', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    if (verifyWebhookSignature($payload, $signature, $webhookSecret) === false) {
        http_response_code(401);
        $logger->error('Invalid signature', [
            'received_signature' => $signature,
            'payload_length' => strlen($payload)
        ]);
        die('Invalid signature');
    }

    $logger->info('Signature verified successfully');

    /** @var array<string, mixed>|null $data */
    $data = json_decode($payload, true);

    if ($data === null) {
        http_response_code(400);
        $logger->error('Invalid JSON payload', ['payload' => $payload]);
        die('Invalid JSON');
    }

    $event = $data['event'] ?? 'unknown';
    $eshopId = $data['eshop_id'] ?? null;
    $timestamp = $data['timestamp'] ?? null;

    $logger->info('Webhook parsed', [
        'event' => $event,
        'eshop_id' => $eshopId,
        'timestamp' => $timestamp
    ]);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'Webhook received']);


    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    switch ($event) {
        case 'Products.update':
            processProductUpdate($data, $logger);
            break;

        case 'Categories.update':
            processCategoryUpdate($data, $logger);
            break;

        default:
            $logger->warning('Unknown event type', ['event' => $event]);
    }

    $logger->info('Webhook processing completed');

} catch (Exception $e) {
    $logger->error('Webhook processing failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}