<?php
$this->registerModule($module, 'main', __('BGP Sessions monitoring'), 'Nightfly', 
    array('BGPMON' => __('right to view BGP sessions monitoring report'),
          'BGPMONRENEW' => __('right to renew BGP peers report'),
          'BGPMONEDIT' => __('right to edit BGP peers names')
));
