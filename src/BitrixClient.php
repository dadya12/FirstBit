<?php
declare(strict_types=1);

class BitrixClient
{
    private string $webhookUrl;
    private string $countryField;
    private int $timeout;
    private Logger $logger;

    public function __construct(string $webhookUrl, string $countryField, Logger $logger, int $timeout = 15)
    {
        $this->webhookUrl = rtrim($webhookUrl, '/') . '/';
        $this->countryField = $countryField;
        $this->logger = $logger;
        $this->timeout = $timeout;
    }

    private function post(string $method, array $data): array
    {
        $url = $this->webhookUrl . $method . '.json';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $res = curl_exec($ch);
        if ($res === false) {
            throw new RuntimeException('Bitrix cURL error: ' . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($json === null) {
            throw new RuntimeException("Bitrix returned non-JSON response HTTP $status: $res");
        }
        return $json;
    }

    public function isDuplicateLead(string $phone, string $email): bool
    {
        $phone = Utils::normalizePhone($phone);
        $email = Utils::normalizeEmail($email);

        $this->logger->info("Проверка дубликата по email='{$email}' и телефону='{$phone}'");

        if ($email !== '') {
            $filter = ['EMAIL' => $email];
            $resp = $this->post('crm.lead.list', [
                'filter' => $filter,
                'select' => ['ID'],
                'limit' => 1,
            ]);
            if (($resp['total'] ?? 0) > 0) {
                return true;
            }
        }

        if ($phone !== '') {
            $filter = ['PHONE' => $phone];
            $resp = $this->post('crm.lead.list', [
                'filter' => $filter,
                'select' => ['ID'],
                'limit' => 1,
            ]);
            if (($resp['total'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    public function createLead(string $name, string $phone, string $email, string $country): array
    {
        $fields = [
            'TITLE' => "Lead from Google Sheets: {$name}",
            'NAME' => $name,
            'PHONE' => [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']],
            'EMAIL' => [['VALUE' => $email, 'VALUE_TYPE' => 'WORK']],
            $this->countryField => $country,
            'SOURCE_ID' => 'IMPORT',
        ];

        $post = [
            'fields' => $fields,
            'params' => ['REGISTER_SONET_EVENT' => 'Y'],
        ];

        return $this->post('crm.lead.add', $post);
    }
}
