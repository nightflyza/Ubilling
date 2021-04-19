<?php
$this->registerModule($module,
                      'main',
                      __('External contragents finances'), 'bobr-kun',
                      array('EXTCONTRAS'  => __('right to use external contragents finances with readonly access'),
                            'EXTCONTRASRW' => __('right of full access to external contragents finances')
                      ));
?>
