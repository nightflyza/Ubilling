<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $cbrowser = new UBCodeBrowser();
    deb($cbrowser->renderFuncsList());
}
