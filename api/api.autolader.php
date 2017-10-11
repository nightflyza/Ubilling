<?php

//Register and load file classes 
spl_autoload_register(function ($className) {

    $api_directory = 'api' . DIRECTORY_SEPARATOR;
    $libs_directory = 'libs' . DIRECTORY_SEPARATOR;
    $venor_directory = 'vendor' . DIRECTORY_SEPARATOR;

    // Defined path
    $classFileName = strtolower($className);

    $apiClassFileName = $api_directory . $libs_directory . 'api.' . $classFileName . '.php';
    $vendorClassFileName = $api_directory . $venor_directory . strtolower($className) . DIRECTORY_SEPARATOR . $classFileName . '.php';

    if (file_exists($apiClassFileName)) {
        include $apiClassFileName;
    } elseif (file_exists($vendorClassFileName)) {
        include $vendorClassFileName;
    }
});

?>
