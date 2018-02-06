<?php
namespace Meklis\ConfigGenerator\Loader;

use Meklis\ConfigGenerator\Exceptions;

class StartConfigLoader implements LoaderInterface {
    protected $globalParams;
    protected $deviceParams;
    protected $params;
    protected $templateModel;
    protected $templateGlobal;
    protected $templateName ;
    public function __construct($template, $modelDescr)
    {
        if(!$this->globalParams = json_decode(file_get_contents(__DIR__ . "/../TemplateConfig/GlobalParams.json"), true)) {
            throw new Exceptions\LoadConfigException("Error loading global config ". __DIR__ . "/../TemplateConfig/GlobalParams.json");
        }
        $this->setModel($modelDescr);
        $this->setTemplate($template);
        $this->loadTemplate();
    }
    protected function setTemplate($block) {
        $this->templateName = $block;
        return true;
    }
    protected function setModel($description)
    {
        if(!$params = json_decode(file_get_contents(__DIR__ . "/../TemplateConfig/DeviceParams.json"), true)) {
            throw new Exceptions\LoadConfigException();
        }
        foreach ($params as $dev) {
            if (preg_match("/{$dev['Pattern']}/", $description)) {
                $this->deviceParams = $dev;
                return $this;
            }
        }
        throw new Exceptions\LoadConfigException("Not found  $description");
    }
    protected function loadTemplate() {
        if(!$this->templateName) {
            throw new Exceptions\InvalidArgumentException("Template not setted! You must call ::setTemplate() before call this");
        }
        if(!$this->deviceParams) {
            throw new Exceptions\InvalidArgumentException("Device not setted! You must call ::setModel() before call this");
        }
        if($conf = file_get_contents(__DIR__ . "/../Templates/{$this->templateName}/global.conf")) {
            $this->templateGlobal = $conf;
        }
        if($conf = file_get_contents(__DIR__ . "/../Templates/{$this->templateName}/{$this->deviceParams['FileName']}")) {
            $this->templateModel = $conf;
        }
        return $this;
    }
    function getDeviceParam() {
        return $this->deviceParams;
    }
    protected function sortParam() {
        $params = [];
        foreach ($this->globalParams as $key=>$value) {
            $params[$key] = $value;
        }
        foreach ($this->deviceParams['Params'] as $key=>$value) {
            $params[$key] = $value;
        }
        foreach ($this->params  as $key=>$value) {
            $params[$key] = $value;
        }
        $params['ALL_PORTS'] = $this->deviceParams['Ports']['All'];
        $params['FIBER_PORTS'] =  $this->deviceParams['Ports']['Fiber'];

        foreach ($params as $key=>$value) {
            if(is_string($value) && preg_match('/{(.*)}/',$value, $matched)) {
                if(isset($params[$matched[1]])) {
                    $params[$key] = $params[$matched[1]];
                } else {
                    throw new Exceptions\InvalidArgumentException("Key $value has been in values, but not found for replace");
                }
            }
        }
        return $params;
    }
    function getParams() {
        return $this->sortParam();
    }
    function getTemplate() {
        $templateGlobal = [];
        $block = "";
        foreach (explode("\n",$this->templateGlobal) as $line) {
            if(preg_match('/#/', $line)) {
                $block = str_replace("#", "", trim($line));
            }
            $templateGlobal[$block][] = $line;
        }

        $templateModel = [];
        $block = "";
        foreach (explode("\n",$this->templateModel) as $line) {
            if(preg_match('/#/', $line)) {
                $block = str_replace("#", "", trim($line));
            }
            $templateModel[$block][] = $line;
            if(isset($templateGlobal[$block])) unset($templateGlobal[$block]);
        }
        $txt = "";
        foreach ($templateGlobal as $templ=>$line) {
            $txt .= join("\n",$line);
        }
        foreach ($templateModel as $templ=>$line) {
            $txt .= join("\n",$line);
        }
        return $txt;
    }
    function setParam($name, $value){
        $this->params[$name] = $value;
        return $this;
    }
}