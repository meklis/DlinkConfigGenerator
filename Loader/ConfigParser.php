<?php
/**
 * Created by PhpStorm.
 * User: Meklis
 * Date: 05.02.2018
 * Time: 16:57
 */

namespace Meklis\ConfigGenerator\Loader;

use Meklis\ConfigGenerator\Exceptions;

class ConfigParser
{
    protected $linesTemplate = [];
    protected $loader = null;
    function setLoader(LoaderInterface $loader) {
        $this->loader = $loader;
        $template = $loader->getTemplate();
        if(!$template) {
            throw new Exceptions\LoadConfigException("Template must be loaded first to send to ConfigParser");
        }
        $lines = explode("\n",$loader->getTemplate());
        $block = "";
        foreach ($lines as $line) {
            if(preg_match('/#/', $line)) {
                $block = str_replace("#", "", trim($line));
                continue;
            } elseif (!trim($line)  || preg_match('/^;.*/',$line)) {

                continue;
            }
            $this->linesTemplate[$block][] = $line;
        }
    }
    protected function formatPorts($params) {
        foreach ($params as $key=>$val) {
            if(preg_match('/.*_PORTS/',$key)) {
                $params[$key] = $this->aggregatePorts($val);
            }
            if($key == 'ALL_PORTS') $params[$key] = "1-".$val;
        }
        return $params;
    }
    protected function readBlock($block) {
        $params = $this->formatPorts($this->loader->getParams());
        if(isset($this->linesTemplate[$block])) {
            $lines = $this->linesTemplate[$block];
        } else {
            throw  new Exceptions\InvalidArgumentException("Choosed block ($block) not exists");
        }
        $returned = [];
        foreach ($lines as $line) {
            $required = true;
            if (preg_match('/\@.*/', $line)) {
                $required = false;
                $line = str_replace('@', "", $line);
            }
            if (preg_match('/^\[(.*)\].*/', $line, $match)) {
                if (!isset($params[$match[1]])) {
                    throw new Exceptions\InvalidArgumentException("Key {$block} : {$match[1]}  not found in params");
                } else {
                    foreach ($params[$match[1]] as $param) {
                        $newLine = $line;
                        foreach ($param as $k => $v) {
                            $newLine = str_replace('{'.$k.'}', $v, $newLine);
                        }
                        $returned[] = [
                            'line' => trim(str_replace("[{$match[1]}]", "", $newLine)),
                            'required' => $required,
                            'block' => $block,
                        ];
                    }
                }
            } elseif (preg_match_all('/{(.*?)}/', $line, $m)) {
                foreach ($m[1] as $match) {
                    if (isset($params[$match])) {
                        $line = str_replace('{'.$match.'}', $params[$match], $line);
                    } else {
                        throw new Exceptions\InvalidArgumentException("Key {$block} : {$match}  not found in params");
                    }
                }
                $returned[] = [
                    'line' => $line,
                    'required' => $required,
                    'block' => $block,
                ];
            } else {
                $returned[]  = [
                    'line' => $line,
                    'required' => $required,
                    'block' => $block,
                ];
            }
        }
        return $returned;
    }
    function getCommands($block = '') {
        if($block) {
            return $this->readBlock($block);
        } else {
            $lines = [];
            foreach ($this->linesTemplate as $block=>$_) {
                $lines = array_merge($lines, $this->readBlock($block));
            }
            return $lines;
        }
    }
    protected function aggregatePorts($ports) {
        if(count($ports) == 1) return $ports[0];
        return join(",",$ports);
    }
}