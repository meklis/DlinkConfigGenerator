<?php

require_once __DIR__ . "/../../load.php";

use \Meklis\ConfigGenerator\Generators\ConfigGenerator;
use \Meklis\ConfigGenerator\Data\Data;
use \Meklis\ConfigGenerator\Loader\ConfigParser;
use \Meklis\ConfigGenerator\Loader\StartConfigLoader;


//Получение стартового конфига
$data = new Data("10.50.124.132");
$data = new ConfigGenerator($data);


print_r($data->getStartConfig());

