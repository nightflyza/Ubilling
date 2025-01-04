<?php
if (ubRouting::get('action') == 'onusigcompressor') {
    if ($alterconf['PON_ENABLED']) {
        $onuSigCompressor=new ONUSigCompressor();
        $onuSigCompressor->run();
        die('ONUSIGCOMPRESSOR:OK');
    } else {
        die('ERROR:PON_DISABLED');
    }
}
