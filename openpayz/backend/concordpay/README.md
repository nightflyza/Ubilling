# Модуль ConcordPay для Ubilling OpenPayz

Данный модуль позволит вам принимать платежи через платёжную систему **ConcordPay**

## Установка

Для работы модуля у вас должен быть установлен и настроен модуль **OpenPayz**.

1. Распакуйте файлы модуля в корневой каталог **Ubilling** с сохранением структуры папок.

2. Переименуйте файл *«{Ubilling_Root}/openpayz/backend/concordpay/config/concordpay-example.ini»* в *«concordpay.ini»*,
и укажите обязательные параметры:
    - *FRONTEND_URL*;
    - *RESPONSE_URL*;
    - *MERCHANT_ID*;
    - *SECRET_KEY*;

4. В том же файле укажите значения остальных параметров, относящихся к настройкам вашего продавца.

5. В файл *«{Ubilling_Root}/userstats/config/opayz.ini»* добавьте строку:
> concordpay="VISA, Mastercard, Google Pay, Apple Pay"

5. В файл *«{Ubilling_Root}/userstats/config/userstats.ini»* добавьте ``concordpay`` в строку с перечнем платёжных систем:
>OPENPAYZ_PAYSYS=...

6. Для изменения языка интерфейса модуля надо зайти в файл
*{Ubilling_Root}/openpayz/backend/concordpay/config/concordpay.ini*, и в разделе локализации раскомментировать нужную,
остальные локализации должны быть закомментированы.

Примечание: примеры выше указанных файлов с внесёнными изменениями, прилагаются.
   - *{Ubilling_Root}/openpayz/backend/concordpay/config/concordpay-example.ini*;
   - *{Ubilling_Root}/userstats/config/opayz-example.ini*;
   - *{Ubilling_Root}/userstats/config/userstats-example.ini.*

Модуль готов к работе!

*Модуль протестирован для работы с Ubilling 1.1.9 rev 8062, OpenPayz и PHP 7.2.*