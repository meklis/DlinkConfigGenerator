<?php
spl_autoload_register(function ($className) {
    $className = str_replace("Meklis\\ConfigGenerator\\", "", $className);
    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
    if (file_exists($path)) {
        require_once $path;
    } else {
        echo "Попытка подключить $path\n\n";
    }
});