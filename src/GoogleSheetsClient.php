<?php
declare(strict_types=1);

class GoogleSheetsClient
{
    private string $sheetId;
    private string $apiKey;
    private int $timeout;

    public function __construct(string $sheetId, string $apiKey, int $timeout = 10)
    {
        $this->sheetId = $sheetId;
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Вернуть значения как массив строк
     *
     * @param string $range URL-часть диапазона, например "'Лист1'!A2:D"
     * @return array<int, array<int, string>>
     * @throws RuntimeException
     */
    public function getValues(string $range): array
    {
        $encodedRange = urlencode($range);
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
            $this->sheetId,
            $encodedRange,
            $this->apiKey
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $res = curl_exec($ch);
        if ($res === false) {
            throw new RuntimeException('GoogleSheets cURL error: ' . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 400) {
            throw new RuntimeException("GoogleSheets API returned HTTP $status: $res");
        }

        $json = json_decode($res, true);
        if (!is_array($json) || !isset($json['values'])) {
            return [];
        }

        return $json['values'];
    }
}
