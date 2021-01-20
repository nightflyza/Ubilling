<?php

$this->registerModule($module, 'main', __('Warehouse'), 'Nightfly', array(
    'WAREHOUSE' => __('right to control warehouse'),
    'WAREHOUSEIN' => __('right to control warehouse income operations'),
    'WAREHOUSEOUT' => __('right to control warehouse outcome operations'),
    'WAREHOUSEOUTRESERVE' => __('right to control warehouse reserve outcome operations'),
    'WAREHOUSERESERVE' => __('right to control warehouse reservation operations'),
    'WAREHOUSEDIR' => __('right to control warehouse directories'),
    'WAREHOUSEREPORTS' => __('right to control warehouse reports'),
    'WAREVIEW' => __('right to view materials spent on tasks')
));
?>
