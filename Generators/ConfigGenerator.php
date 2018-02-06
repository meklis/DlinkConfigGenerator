<?php
/**
 * Created by PhpStorm.
 * User: Meklis
 * Date: 06.02.2018
 * Time: 12:22
 */

namespace Meklis\ConfigGenerator\Generators;


use Meklis\ConfigGenerator\Data\DataInterface;
use Meklis\ConfigGenerator\Loader;

class ConfigGenerator
{
    /**
     * @var DataInterface
     */
    protected $data;
    function __construct(DataInterface $data)
    {
        $this->data = $data;
    }
    function getStartConfig() {
        $loader = new Loader\StartConfigLoader("START_CONFIG", $this->data->getDescription());
        $parser = new Loader\ConfigParser();
        $loader->setParam("ABON_PORTS", $this->getAbonPorts($this->data, $loader));
        $loader->setParam("MAGISTRAL_PORTS", $this->getMagistralPorts($this->data, $loader));
        $loader->setParam("DHCP_SERVER_IP", $this->data->getDhcpRelay());
        $loader->setParam("TRUSTEDS", $this->data->getTrusteds());
        $loader->setParam("VLAN_INET", $this->data->getInetVlan());
        $loader->setParam("VLAN_INET", $this->data->getInetVlan());
        $loader->setParam("PROFILE_IDS", $this->getProfileIds($loader));
        $loader->setParam("SYSLOG", $this->data->getSyslog());
        $loader->setParam("PROFILE_5_DENY", $this->getAbonPortsFor5Profile($this->data, $loader));
        $loader->setParam("VLAN_SWITCH", $this->data->getSwitchVlan());
        $parser->setLoader($loader);
        return $parser->getCommands();
    }
    function getDbProfiles() {
        $addACL = [];
        $binds = $this->data->getBindings();
        $loader = new Loader\StartConfigLoader("MANAGE_PROFILES", $this->data->getDescription());
        foreach ($binds as $bind) {
            $parser = new Loader\ConfigParser();
            $parser->setLoader($loader);
            $loader->setParam("RULE", 2);
            $loader->setParam("IP", $bind['IP']);
            $loader->setParam("PORT", $bind['PORT']);
            $addACL = array_merge($addACL, $parser->getCommands($bind['block']));
        }
        return $addACL;
    }
    protected function getAbonPortsFor5Profile(DataInterface $data, Loader\LoaderInterface $loader) {
        $data = $this->getAbonPorts($data,$loader);
        $ret = [];
        foreach ($data as $dat) {
            $ret[] = [
                'PORT'=>$dat,
            ];
        }
        return $ret;
    }
    protected function getAbonPorts(DataInterface $data, Loader\LoaderInterface $loader) {
        $ab_ports = [];
        $uplinkPort = $data->getMagistralPorts();
        for($port =1; $port <= $loader->getDeviceParam()['Ports']['All']; $port++) {
            if(in_array($port, $loader->getDeviceParam()['Ports']['Fiber'])) continue;
            if($port == $uplinkPort) continue;
            $ab_ports[] =$port;
        }
        return $ab_ports;
    }
    protected function getMagistralPorts(DataInterface $data, Loader\LoaderInterface $loader) {
        $magistral = $loader->getDeviceParam()['Ports']['Fiber'];
        $magistral[] = $data->getMagistralPorts();
        return array_unique($magistral);
    }
    protected function getProfileIds( Loader\LoaderInterface $loader) {
        $mag = [];
        for($i=1;$i<=8;$i++) {
            $mag[] = [
              "ALL_PORTS" => $loader->getParams()['ALL_PORTS'],
              "PROFILE_IDS" => $i,
            ];
        }
        return $mag;
    }
}