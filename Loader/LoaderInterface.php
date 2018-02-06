<?php
/**
 * Created by PhpStorm.
 * User: Meklis
 * Date: 05.02.2018
 * Time: 20:50
 */

namespace Meklis\ConfigGenerator\Loader;


interface LoaderInterface
{
    function getParams();
    function getTemplate();
    function setParam($name, $value);
    function getDeviceParam();
}