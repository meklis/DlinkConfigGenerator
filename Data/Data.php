<?php
/**
 * Created by PhpStorm.
 * User: Meklis
 * Date: 05.02.2018
 * Time: 22:15
 */

namespace Meklis\ConfigGenerator\Data;


use Meklis\ConfigGenerator\Exceptions\LoadConfigException;
use Meklis\ConfigGenerator\Exceptions\NotFoundException;
use Meklis\ConfigGenerator\Exceptions\RemoteDeviceException;

class Data implements DataInterface, DataDBInterface
{
    protected $sql;
    protected $ip;
    protected $snmp;
    protected $uplinkPort = false;
    protected $switch = [
        'swid' => 0,
        'Ip' => '',
        'login' => '',
        'pass' => '',
        'community' => '',
        'house' => 0,
    ];

    function __construct($host)
    {
        $this->ip = $host;
        $this->sql = dbConn('cr1');
        $switch = \msdb::getSwAccess($this->ip);
        if (!$switch) {
            throw new NotFoundException("Switch not found in database, please check IP");
        }
        $this->switch = $switch;
    }

    function getInetVlan()
    {
        $vlans = \msdb::getVlanOnSw($this->ip);
        if (!isset($vlans[0]['Vid'])) throw new NotFoundException("Vlans for this switch not found in database");
        return $vlans[0]['Vid'];
    }
    function getSwitchVlan()
    {
       $data = $this->sql->query("SELECT DISTINCT vlan FROM mrtg.ORIGIN_DIRECT WHERE INET_ATON('{$this->ip}') BETWEEN INET_ATON(network) and INET_ATON(broadcast) LIMIT 1 ")->fetch_assoc()['vlan'];
       if(!$data) {
           throw new NotFoundException("Not found switches vlan for ip {$this->ip}");
       }
       return $data;
    }

    function getTrusteds()
    {
        $data = $this->sql->query("SELECT c.value FROM (
                SELECT * FROM troya.`ConfigGenerator`
                UNION SELECT * FROM saltovka.ConfigGenerator
                UNION SELECT * FROM rogan.ConfigGenerator 
                UNION SELECT * FROM obolon.ConfigGenerator) c 
                JOIN mrtg.switches sw on sw.dir = c.dir 
                WHERE sw.ip = '{$this->ip}'
                and `key` = 'TRUSTEDS' LIMIT 1")->fetch_assoc()['value'];
        if (!$resp = json_decode($data, true)) {
            throw new LoadConfigException("Error decode json from database");
        }
        return $resp;
    }

    function getMagistralPorts()
    {
        if ($this->uplinkPort) return $this->uplinkPort;
        $walk = new \GetUplinkPort();
        if (!$walk->open($this->ip, $this->switch['community'], $this->switch['login'], $this->switch['pass'])) {
            throw  new RemoteDeviceException("Switch not respond by snmp");
        };
        if (!$port = $walk->getData()) {
            throw  new RemoteDeviceException("Switch not returned uplink port");
        }
        $this->uplinkPort = $port;
        return $port;
    }

    function getDescription()
    {
        $walk = @snmpwalk($this->ip, $this->switch['community'], "1.3.6.1.2.1.1.1");
        if (!$walk) {
            throw new RemoteDeviceException("Switch not responced with community {$this->switch['community']}");
        }
        return $walk[0];
    }

    function getSyslog()
    {
        $data = $this->sql->query("SELECT c.value FROM (
                SELECT * FROM troya.`ConfigGenerator`
                UNION SELECT * FROM saltovka.ConfigGenerator
                UNION SELECT * FROM rogan.ConfigGenerator 
                UNION SELECT * FROM obolon.ConfigGenerator) c 
                JOIN mrtg.switches sw on sw.dir = c.dir 
                WHERE sw.ip = '{$this->ip}'
                and `key` = 'SYSLOG' LIMIT 1")->fetch_assoc()['value'];
        if (!$resp = json_decode($data, true)) {
            throw new LoadConfigException("Error decode json from database");
        }
        return $resp;
    }

    function getDhcpRelay()
    {
        $vlan = $this->getInetVlan();
        $mikro = $this->sql->query("SELECT sw.ip, sw.login, sw.`password`
        FROM mrtg.switches sw 
        JOIN mrtg.ORIGIN_DIRECT d on d.router = sw.ip 
        WHERE d.vlan =  $vlan and model like '%mikrotik%' LIMIT 1")->fetch_assoc();
        if (!$connect = \devStd::newMikro($mikro['ip'], $mikro['login'], $mikro['password'])) {
            throw new RemoteDeviceException("Error connecting to mikrotik {$mikro['ip']}");
        }
        $command = $connect->comm("/ip/dhcp-server/print");
        $interface = "";
        foreach ($command as $com) {
            if (@$com['disabled'] === 'false') {
                $interface = @$com['interface'];
            }
        }
        if (!$interface) {
            throw  new NotFoundException("DHCP-relay server on mikrotik {$mikro['ip']} not found");
        }
        $gateway = $this->sql->query("SELECT gateway FROM mrtg.ORIGIN_DIRECT WHERE vlan_name = '{$interface}' and router = '{$mikro['ip']}' LIMIT 1")->fetch_assoc()['gateway'];
        if (!$gateway) {
            throw  new NotFoundException("Gateway not found in CR1 database. Search by interface: {$interface} and router: {$mikro['ip']}");
        }
        return $gateway;
    }

    function getBindings()
    {
      $binds = [];
      $data = \msdb::getBindBySw($this->ip, true);
      foreach ($data as $bind)  {
          $block = "ADD_ACL_PROFILES";
          if($bind['ip'] == '1.1.1.1' || $bind['ip'] == '3.3.3.3') continue;
          if($bind['ip'] == '2.2.2.2') $block = "ADD_MULT_PROFILES";
          $binds[] = [
            'block'=>$block,
            'PORT'=>$bind['port'],
            'IP'=>$bind['ip'],
            'RULE'=>2,
          ];
      }
      return $binds;
    }
}