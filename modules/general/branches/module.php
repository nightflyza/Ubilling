<?php

$this->registerModule($module, 'main', __('Branches'), 'Nightfly', 
          array('BRANCHES' => __('right to use branches module'),
                'BRANCHESREG' => __('right to register branches users'),
                'BRANCHESFINREP' => __('right to use branches financial report'),
                'BRANCHESSIGREP' => __('right to use branches signup report'),
                'BRANCHESCONF' => __('right to control branches configuration')
));
?>
