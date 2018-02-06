<?php
/**
 * Created by PhpStorm.
 * User: Meklis
 * Date: 05.02.2018
 * Time: 22:12
 */

namespace Meklis\ConfigGenerator\Data;


interface SnmpInterface
{
    function __construct($ip, $community);
    function getDescription();
    function getMagistralPorts();
}