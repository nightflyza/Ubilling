<?php
$this->registerModule($module, 'main', __('Manage contrahens'), 'Nightfly', array(
    'AGENTS' => __('right to view contrahens'),
    'AGENTSMGMT'=>__('right to control contrahens'),
    'AGENTSGEO'=>__('right to control contrahens geo assigns'),
    'AGENTSOVR'=>__('right to control contrahens assign overrides'),
));
