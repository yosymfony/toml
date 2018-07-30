<?php

namespace Yosymfony\Toml;

use Yosymfony\Toml\Exception\ParseException;

class TomlBuilderFromArray
{
    private $src;
    private $tb;
    private $keyStack = [];

    public function __construct($src)
    {
        if (is_object($src)) {
            $src = $this->obj2Arr($src);
        }
        if (!is_array($src)) {
            throw new ParseException('input must be an array');
        }

        $this->tb = new TomlBuilder();
        $this->src = $src;
    }

    public function convert()
    {
        if (!is_array($this->src)) {
            throw new ParseException('input must be an array');
        }

        $this->arrange($this->src);
        $this->dumpItem($this->src);
        return $this->tb->getTomlString();
    }

    /**
     * Move simple elements to the front part of array recursively.
     * @param $array
     */
    private function arrange(&$array)
    {
        $is_array = [];
        $isnot_array = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $this->arrange($value);
                $is_array[$key] = $value;
            } else {
                $isnot_array[$key] = $value;
            }
        }
        $array = array_merge($isnot_array, $is_array);
    }

    private function dumpItem($item, $key = '')
    {
        switch (true) {
            case is_array($item) || is_object($item):
                if ($this->is_assoc($item)) {
                    if (!is_numeric($key) && !empty($key)) {
                        $added = true;
                        array_push($this->keyStack, $key);
                        $this->tb->addTable($this->getKeys());
                    }
                    foreach ($item as $k => $v) {
                        $this->dumpItem($v, $k);
                    }
                    if (isset($added) && $added) {
                        array_pop($this->keyStack);
                    }
                } else {
                    if ($this->is_1_dim($item) && !empty($key)) {
                        $this->tb->addValue($key, $item);
                    } else {
                        foreach ($item as $k => $v) {
                            if (!is_numeric($key) && !empty($key)) {
                                array_push($this->keyStack, $key);
                            }
                            $this->tb->addArrayOfTable($this->getKeys());
                            $this->dumpItem($v, $k);
                            if (!is_numeric($key) && !empty($key)) {
                                array_pop($this->keyStack);
                            }
                        }
                    }
                }
                break;
            default:
                $this->tb->addValue($key, $item);
                break;
        }
    }

    private function is_assoc($array)
    {
        if (is_object($array)) {
            return true;
        }
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    private function getKeys()
    {
        return implode('.', $this->keyStack);
    }

    private function is_1_dim($arr)
    {
        if (is_object($arr)) {
            $arr = $this->obj2Arr($arr);
        }
        return count($arr) == count($arr, 1);
    }

    private function obj2Arr($obj)
    {
        return json_decode(json_encode($obj), true);
    }

}