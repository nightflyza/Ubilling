<?php

$this->registerModule($module, 'main', __('PseudoCRM'), 'Nightfly',
        array(
            'PSEUDOCRM' => __('right to view PseudoCRM'),
            'PSEUDOCRMLEADS' => __('right to manage PseudoCRM leads'),
            'PSEUDOCRMACTS' => __('right to manage PseudoCRM leads activities'),
            'PSEUDOCRMACTMGR' => __('right to manage all of PseudoCRM leads activities'),
        ));
