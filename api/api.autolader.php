<?php

//Register and load file classes 
spl_autoload_register(function ($className) {

    static $api_directory = 'api' . DIRECTORY_SEPARATOR;
    static $libs_directory = 'libs' . DIRECTORY_SEPARATOR;
    static $venor_directory = 'vendor' . DIRECTORY_SEPARATOR;

    $classFileName = strtolower($className);

    if (file_exists($api_directory . $libs_directory)) {
        include $api_directory . $libs_directory . 'api.' . $classFileName . '.php';
    } elseif (file_exists($api_directory . $venor_directory)) {
        include $api_directory . $venor_directory . $className . $classFileName . '.php';
    }
});

?>
