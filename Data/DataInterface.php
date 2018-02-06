<?php
/**
 * Created by PhpStorm.
 * User: Meklis
 * Date: 05.02.2018
 * Time: 22:04
 */

namespace Meklis\ConfigGenerator\Data;


interface DataInterface
{
    function getInetVlan();
    function getSwitchVlan();
    function getTrusteds();
    function getMagistralPorts();
    function getDescription();
    function getDhcpRelay();
    function getSyslog();
    function getBindings();
}