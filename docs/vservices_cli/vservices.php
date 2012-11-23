<?php
include("api/apiloader.php");

/* debug flags:
 * 0 - silent
 * 1 - with debug output
 * 2 - don`t touch any cash, just testing run
 */

$debug=1;
$log_payment=true;

zb_VservicesProcessAll($debug,$log_payment);

?>
