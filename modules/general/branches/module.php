<?php

$this->registerModule($module, 'main', __('Branches'), 'Nightfly', 
        array('BRANCHES' => __('right to use branches module'),
              'BRANCHESREG' => __('right to register branches users'),
              'BRANCHESTARIFFS' => __('right to manage branches users tariffs'),
              'BRANCHESCONF' => __('right to control branches configuration')
             ));
?>
