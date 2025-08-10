# Интеграция Google Sheets с Bitrix24 (облачная версия)

## Инструкция по запуску

1. Клонируйте репозиторий:
    git clone https://github.com/dadya12/FirstBit.git
    (или через SSH): git clone git@github.com:dadya12/FirstBit.git

2. Убедитесь, что установлен PHP 7.4+ с расширением CURL.
3. Отредактируйте файл config/config.php, указав свои параметры (если это требуется то см. ниже).
4. Запустите скрипт из консоли: php path/to/sync.php


## Пример конфигурации (config/config.php)

return [
    'google_sheet_id'      => '1BTPLMhgpRAeb2JVhoYp83WWV-OVtk-Fs3v9i6N8E_Sg', // ID вашей Google таблицы
    'google_api_key'       => 'AIzaSyB1xXETcU89xRku-I83acHBsDtCKHKDta4',    // Ваш API ключ Google Sheets
    'google_range'         => "'Лист1'!A2:D999",                            // Диапазон данных для чтения
    'bitrix_webhook_url'   => 'https://b24-26v4n9.bitrix24.kz/rest/1/r5gz5nt3kgcy4h07/', // URL вебхука Bitrix24
    'bitrix_country_field' => 'UF_CRM_1754763891',                          // Символьный код кастомного поля "Страна"
    'log_file'             => __DIR__ . '/../logs/sync.log',                // Файл с логами
    'request_timeout'      => 15,
];


## Как задать ID кастомного поля "Страна" в Bitrix24

Войдите в Bitrix24 CRM.
Перейдите в CRM → Настройки CRM → Настройки форм и отчетов → Пользовательские поля.
В разделе Лид (Пользовательские поля для "Лидов") нажмите Добавить поле.
Укажите название поля — Страна, тип — Строка.
После создания найдите поле в списке и кликните на его название.
Символьный код поля можно увидеть в URL страницы после слова edit/.
https://b24-26v4n9.bitrix24.kz/crm/configs/fields/CRM_LEAD/edit/UF_CRM_1754763891/
Здесь UF_CRM_1754763891 — это и есть код поля.
Вставьте этот код в параметр 'bitrix_country_field' в файле config/config.php.


## Пример логов (logs/sync.log)

[2025-08-10 09:49:28] [INFO] === START sync ===
[2025-08-10 09:49:29] [INFO] Проверка дубликата по email='ivan@example.com' и телефону='+1234567890'
[2025-08-10 09:49:30] [INFO] Строка 2: лид создан, ID=12345, name=Иван Иванов
[2025-08-10 09:49:31] [INFO] Строка 3: найден дубликат, лид не создан (tel=+1234567891, email=jane@example.com)
[2025-08-10 09:49:32] [INFO] Строка 4 пропущена: нет телефона и email
[2025-08-10 09:49:33] [ERROR] Строка 5: ошибка создания лида: {"error":"INVALID_PHONE"}
[2025-08-10 09:49:34] [INFO] === FINISH sync ===


## Пример запроса в Bitrix24 (crm.lead.add)

json:
{
  "fields": {
    "TITLE": "Лид из Google Sheets",
    "NAME": "Алексей Смирнов",
    "PHONE": [
      { "VALUE": "+996555123456", "VALUE_TYPE": "WORK" }
    ],
    "EMAIL": [
      { "VALUE": "a.smirnov@mail.com", "VALUE_TYPE": "WORK" }
    ],
    "UF_CRM_1754763891": "Кыргызстан",
    "SOURCE_ID": "IMPORT"
  },
  "params": { "REGISTER_SONET_EVENT": "Y" }
}


Curl:
curl -X POST \
  'https://b24-26v4n9.bitrix24.kz/rest/1/r5gz5nt3kgcy4h07/crm.lead.add.json' \
  -H 'Content-Type: application/json' \
  -d '{
    "fields": {
      "TITLE": "Лид из Google Sheets",
      "NAME": "Алексей Смирнов",
      "PHONE": [
        { "VALUE": "+996555123456", "VALUE_TYPE": "WORK" }
      ],
      "EMAIL": [
        { "VALUE": "a.smirnov@mail.com", "VALUE_TYPE": "WORK" }
      ],
      "UF_CRM_1754763891": "Кыргызстан",
      "SOURCE_ID": "IMPORT"
    },
    "params": { "REGISTER_SONET_EVENT": "Y" }
  }'


## Пример таблицы Google Sheets

| Name            | Phone           | Email                                                       | County     |
| --------------- | --------------- | ----------------------------------------------------------- | ---------- |
| Алексей Смирнов | +996 555 123456 | [a.smirnov@mail.com](mailto:a.smirnov@mail.com)             | Кыргызстан |
| Фатима Аль-Саид | +971 50 9876543 | [fatima.saeed@emirates.ae](mailto:fatima.saeed@emirates.ae) | ОАЭ        |
| Нурбек Токтосун | +7 777 6543210  | [nurbek.tok@mail.kz](mailto:nurbek.tok@mail.kz)             | Казахстан  |



## Ресурсы

Google Sheets с данными для импорта:
https://docs.google.com/spreadsheets/d/1BTPLMhgpRAeb2JVhoYp83WWV-OVtk-Fs3v9i6N8E_Sg

Bitrix24 портал для проверки:
https://b24-26v4n9.bitrix24.kz/