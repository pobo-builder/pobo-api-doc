<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Pobo\Sdk\WebhookHandler;
use Pobo\Sdk\Enum\WebhookEvent;
use Pobo\Sdk\Exception\WebhookException;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required('POBO_WEBHOOK_SECRET');

$logger = new Logger('webhook');
$formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context%\n", "Y-m-d H:i:s");
$streamHandler = new StreamHandler('/var/www/html/logs/webhook.log', Logger::INFO);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

$handler = new WebhookHandler(webhookSecret: $_ENV['POBO_WEBHOOK_SECRET']);

function processProductUpdate(int $eshopId, \DateTimeInterface $timestamp, Logger $logger): void
{
    $logger->info('Processing product update', [
        'eshop_id' => $eshopId,
        'timestamp' => $timestamp->format('Y-m-d H:i:s'),
    ]);

    $logger->info('Product update completed successfully');
}

function processCategoryUpdate(int $eshopId, \DateTimeInterface $timestamp, Logger $logger): void
{
    $logger->info('Processing category update', [
        'eshop_id' => $eshopId,
        'timestamp' => $timestamp->format('Y-m-d H:i:s'),
    ]);

    $logger->info('Category update completed successfully');
}

try {
    $logger->info('Webhook received', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    ]);

    $payload = $handler->handleFromGlobals();

    $logger->info('Webhook verified and parsed', [
        'event' => $payload->event->value,
        'eshop_id' => $payload->eshopId,
        'timestamp' => $payload->timestamp->format('Y-m-d H:i:s'),
    ]);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'Webhook received']);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    match ($payload->event) {
        WebhookEvent::PRODUCTS_UPDATE => processProductUpdate($payload->eshopId, $payload->timestamp, $logger),
        WebhookEvent::CATEGORIES_UPDATE => processCategoryUpdate($payload->eshopId, $payload->timestamp, $logger),
    };

    $logger->info('Webhook processing completed');

} catch (WebhookException $e) {
    $logger->error('Webhook validation failed', [
        'error' => $e->getMessage(),
    ]);

    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);

} catch (Exception $e) {
    $logger->error('Webhook processing failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
