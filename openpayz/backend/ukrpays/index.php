<?php

/**
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты 
 * при помощи платежного сервиса Ukrpays
 */
//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// подгружаем конфиг
$conf = parse_ini_file("config/ukrpays.ini");

// выбираем нужные опции мерчанта
$serviceId = $conf['SERVICE_ID'];
$baseUrl = $conf['URL'];
$serviceName = $conf['SERVICE'];
$ispName = $conf['ISP_NAME'];
$ispUrl = $conf['ISP_URL'];
$ispLogo = $conf['ISP_LOGO'];
$availableAmounts = $conf['AMOUNTS'];
$selectLabel = $conf['SELECT_TEXT'];
$locale = $conf['LANG'];
$currency = $conf['CURRENCY'];

/**
 * Returns all user RealNames
 *
 * @return array
 */
function ups_UserGetAllRealnames() {
    $query = "SELECT * from `realname`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['realname'];
        }
    }
    return($result);
}

/**
 * Returns basic payment form
 * 
 * @param string $customer_id
 * @param string $availableAmounts
 * @param string $currency
 * @param string $baseUrl
 * @param string $ispUrl
 * @param string $serviceId
 * @param string $locale
 * 
 * @return string
 */
function paymentForm($customer_id, $availableAmounts, $currency, $baseUrl, $ispUrl, $serviceId, $locale) {
    $allCustomers = op_CustomersGetAll();
    if (isset($allCustomers[$customer_id])) {
        $userLogin = $allCustomers[$customer_id];
        $allRealNames = ups_UserGetAllRealnames();
        $userRealName = @$allRealNames[$userLogin];
        $customer_id = trim($customer_id);
        $availableAmounts = explode(',', $availableAmounts);
        $selector = '';
        if (!empty($availableAmounts)) {
            $i = 0;
            foreach ($availableAmounts as $eachamount) {
                $eachamount = trim($eachamount);
                //выставляем первую цену отмеченной
                if ($i == 0) {
                    $selected = 'CHECKED';
                } else {
                    $selected = '';
                }

                $selector.='<input type="radio" name="amount" value="' . $eachamount . '" ' . $selected . ' id="am_' . $i . '">';
                $selector.='<label for="am_' . $i . '">' . $eachamount . ' ' . $currency . '</label> <br>';
                $i++;
            }
        }

        $form = '
            <form action="' . $baseUrl . '" method="post">
            <input type="hidden" name="service_id" value="' . $serviceId . '"/>
            <input type="hidden" name="order" value="' . $customer_id . '"/>
            <input type="hidden" name="sus_url" value="' . $ispUrl . '"/>
            <input type="hidden" name="fio" value="' . $userRealName . '"/>
             ' . $selector . '
            <input type="hidden" name="charset" value="UTF-8"/>
            <input type="hidden" name="lang" value="' . $locale . '"/>
            <br>
            <input type="submit">
            </form>
        ';
        return($form);
    } else {
        die('EX_WRONG_CUSTOMER');
    }
}

$payment_form = paymentForm($customer_id, $availableAmounts, $currency, $baseUrl, $ispUrl, $serviceId, $locale);

//показываем все что нужно в темплейт
include("template.html");
?>
