<?php
if (cfr('UKV')) {
    
    
    $ukv=new UkvSystem();
    
    debarr($ukv);
    
    
} else {
    show_window(__('Error'), __('Access denied'));
}

?>