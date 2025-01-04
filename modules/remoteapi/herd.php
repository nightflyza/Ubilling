<?php

if (ubRouting::get('action') == 'herd') {
    if ($alterconf['PON_ENABLED']) {
        if (ubRouting::checkGet('oltid')) {
            /**
             * 
             *                                |\    /|     
             *                             ___| \,,/_/     
             *                           ---__/ \/    \
             *                          __--/     (D)  \    
             *                          _ -/    (_      \   
             *                         // /       \_ / ==\  
             *   __-------_____--___--/           / \_ O o) 
             *  /                                 /   \==/  
             * /                                 /          
             * ||          )                   \_/\          
             * ||         /              _      /  |         
             * | |      /--______      ___\    /\  :         
             * | /   __-  - _/   ------    |  |   \ \        
             * |   -  -   /                | |     \ )      
             * |  |   -  |                 | )     | |      
             *  | |    | |                 | |    | |       
             *  | |    < |                 | |   |_/        
             *  < |    /__\                <  \             
             *  /__\                       /___\            
             */

            $oltId = ubRouting::get('oltid', 'int');
            $compressorProcess = new StarDust(ONUSigCompressor::PID);
            if ($compressorProcess->notRunning()) {
                $pony = new PONizer();
                $pony->pollOltSignal($oltId);
                die('OK:HERD');
            } else {
                log_register('PON HERD OLT ['.$oltId.'] SKIPPED DUE COMPRESSOR RUNNING');
                die('SKIPPED:HERD');
            }
        } else {
            die('ERROR:NO_OLTID');
        }
    } else {
        die('ERROR:PON_DISABLED');
    }
}
