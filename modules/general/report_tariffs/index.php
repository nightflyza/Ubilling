<?php
if (cfr('REPORTTARIFFS')) {
    show_window(__('Popularity of tariffs among users'),web_TariffShowReport());
    show_window(__('Planned tariff changes'),web_TariffShowMoveReport());
    
} else {
      show_error(__('You cant control this module'));
}

?>
