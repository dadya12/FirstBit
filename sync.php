<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Utils.php';
require_once __DIR__ . '/src/GoogleSheetsClient.php';
require_once __DIR__ . '/src/BitrixClient.php';

$config = require __DIR__ . '/config/config.php';

$logger = new Logger($config['log_file']);

$logger->info('=== START sync ===');

$gs = new GoogleSheetsClient(
    $config['google_sheet_id'],
    $config['google_api_key'],
    (int)($config['request_timeout'] ?? 10)
);

$bx = new BitrixClient(
    $config['bitrix_webhook_url'],
    $config['bitrix_country_field'],
    $logger,
    (int)($config['request_timeout'] ?? 15)
);

try {
    $rows = $gs->getValues($config['google_range']);
} catch (Exception $e) {
    $logger->error('GoogleSheets error: ' . $e->getMessage());
    exit(1);
}

if (empty($rows)) {
    $logger->info('Нет данных в таблице — выходим.');
    exit(0);
}

foreach ($rows as $i => $row) {
    [$name, $phoneRaw, $emailRaw, $country] = array_pad($row, 4, '');

    $line = $i + 2;
    $phone = Utils::normalizePhone((string)$phoneRaw);
    $email = Utils::normalizeEmail((string)$emailRaw);

    if ($phone === '' && $email === '') {
        $logger->info("Строка {$line} пропущена: нет телефона и email");
        continue;
    }

    try {
        if ($bx->isDuplicateLead($phone, $email)) {
            $logger->info("Строка {$line}: найден дубликат, лид не создан (tel={$phone}, email={$email})");
            continue;
        }

        $resp = $bx->createLead($name, $phone, $email, $country);

        if (isset($resp['result']) && is_numeric($resp['result'])) {
            $leadId = $resp['result'];
            $logger->info("Строка {$line}: лид создан, ID={$leadId}, name={$name}");
        } else {
            $logger->error("Строка {$line}: ошибка создания лида: " . json_encode($resp));
        }
    } catch (Exception $e) {
        $logger->error("Строка {$line}: исключение: " . $e->getMessage());
    }
}

$logger->info('=== FINISH sync ===');
