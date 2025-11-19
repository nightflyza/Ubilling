<div class="receipt_container">
<input id="qr_content_{QR_INDEX}" type="hidden" value="{QR_CODE_CONTENT}" />

<table class="pagebreak_before" border="0" cellpadding="0" cellspacing="0" style="width: 19.5cm; font-size: 11pt;">
    <tr>
        <td rowspan="11" style="width: 25%">
            <span name="qr{QR_INDEX}" style="text-align: center">{QR_CODE_EMBEDDED}</span>
        </td>
        <tr>
            <!--<td class="brdr-bottom-right">Дата счета:</td><td class="brdr-bottom-right">{CURDATE}</td>-->
            <td class="brdr-bottom-right">Оплатить до:</td><td class="brdr-bottom" colspan="3">05.{PAYTILLMONTHYEAR}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Оплата за период:</td><td class="brdr-bottom-right" colspan="3">{PAYFORPERIODSTR}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Оплата услуги:</td><td class="brdr-bottom" colspan="3">{SERVICENAME}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Получатель:</td><td class="brdr-bottom" colspan="3">ООО "Рога&Копыта"&nbsp&nbsp&nbsp&nbspИНН&nbsp456789213&nbsp&nbsp&nbsp&nbspР/С&nbsp204087899521005<br />+38(50)125-26-15&nbsp&nbspг. Аркхэм, ул. Вязов, 51, офис №1</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Банк&nbspполучателя:</td><td class="brdr-bottom" colspan="3">ОАО "НяшМяшТяжБанк" г. Аркхэм <br />МФО&nbsp301018&nbsp&nbsp&nbsp&nbspБИК&nbsp0123544</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Номер&nbspдоговора:</td><td class="brdr-bottom" colspan="3">{CONTRACT}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Абонент:</td><td class="brdr-bottom" colspan="3">{REALNAME}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Адрес:</td><td class="brdr-bottom" colspan="3">{STREET} {BUILD}{APT}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right">Тариф:</td><td class="brdr-bottom-right">{TARIFF} за {TARIFFPRICEDECIMALS} денег/мес.</td><td class="brdr-bottom-right">Сумма к оплате:</td><td class="brdr-bottom">{SUMMDECIMALS} денег</td>
        </tr>
        <tr>
            <td class="brdr-bottom" colspan="2" style="padding-top: 10px;">Кассир:</td><td class="brdr-bottom" colspan="2" style="padding-top: 10px;">Плательщик:</td>
        </tr>
    </tr>
</table>

<p style="border-bottom: 1px dashed #000; margin: 5px 0"></p>

<table border="0" cellpadding="0" cellspacing="0" style="width: 19.5cm; font-size: 11pt;">
    <tr>
        <td rowspan="11" style="width: 25%">
            <!--<span name="qr{QR_INDEX}" style="text-align: center"></span>-->
        </td>
        <tr>
            <!--<td class="brdr-bottom-right-left-top">Дата счета:</td><td class="brdr-bottom-right-top">{CURDATE}</td>-->
            <td class="brdr-bottom-right-top">Оплатить до:</td><td class="brdr-bottom-right-top" colspan="3">05.{PAYTILLMONTHYEAR}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Оплата за период:</td><td class="brdr-bottom-right" colspan="3">{PAYFORPERIODSTR}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Оплата услуги:</td><td class="brdr-bottom-right" colspan="3">{SERVICENAME}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Получатель:</td><td class="brdr-bottom-right" colspan="3">ООО "Рога&Копыта"&nbsp&nbsp&nbsp&nbspИНН&nbsp456789213&nbsp&nbsp&nbsp&nbspР/С&nbsp204087899521005<br />+38(50)125-26-15&nbsp&nbspг. Аркхэм, ул. Вязов, 51, офис №1</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Банк&nbspполучателя:</td><td class="brdr-bottom-right" colspan="3">ОАО "НяшМяшТяжБанк" г. Аркхэм <br />МФО&nbsp301018&nbsp&nbsp&nbsp&nbspБИК&nbsp0123544</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Номер&nbspдоговора:</td><td class="brdr-bottom-right" colspan="3">{CONTRACT}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Абонент:</td><td class="brdr-bottom-right" colspan="3">{REALNAME}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Адрес:</td><td class="brdr-bottom-right" colspan="3">{STREET} {BUILD}{APT}</td>
        </tr>
        <tr>
            <td class="brdr-bottom-right-left">Тариф:</td><td class="brdr-bottom-right">{TARIFF} за {TARIFFPRICEDECIMALS} денег/мес.</td><td class="brdr-bottom-right">Сумма к оплате:</td><td class="brdr-bottom-right">{SUMMDECIMALS} денег</td>
        </tr>
        <tr>
            <td class="brdr-bottom-left" colspan="2" style="padding-top: 20px;">Кассир:</td><td class="brdr-bottom-right" colspan="2" style="padding-top: 20px;">Плательщик:</td>
        </tr>
    </tr>
</table>
</div>
<p class="pagebreak_footer" style="margin: 70px 0 5px 100px;">Адрес: {STREET} {BUILD}{APT}&nbsp&nbsp&nbsp&nbspОплата услуги:&nbsp{SERVICENAME}</p>

<p class="footer_dashed_line" style="border-bottom: 4px dashed #000; margin: 20px 0 70px 0"></p>
</div>
