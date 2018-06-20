<?php

//config payment URL here.
$url='https://my-payments.privatbank.ua/mypayments/customauth/identification/fp/static?staticToken=123456789';
print('<script language="javascript">document.location.href="' . $url . '";</script>'); 

?>
