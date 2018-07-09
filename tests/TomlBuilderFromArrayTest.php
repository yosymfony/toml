<?php

namespace Yosymfony\Toml\tests;


use PHPUnit\Framework\TestCase;
use Yosymfony\Toml\Toml;
use Yosymfony\Toml\TomlBuilderFromArray;

class TomlBuilderFromArrayTest extends TestCase
{
    public function testExample()
    {
        $config_file = __DIR__ . '/fixtures/array.php';
        $config = include $config_file;

        $tbfa = new TomlBuilderFromArray($config);
        $result = $tbfa->convert();

        $this->assertNotNull($result);
    }

    public function testMultidimensionalArray()
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 999
                    ]
                ]
            ],
            'b' => '0'
        ];
        $tbfa = new TomlBuilderFromArray($array);
        $this->assertSame(Toml::parse($tbfa->convert())['a']['b']['c']['d'], 999);
    }

    public function testObject()
    {
        $config_file = __DIR__ . '/fixtures/array.php';
        $config = include $config_file;
        $object = (object)$config;
        $tbfa = new TomlBuilderFromArray($object);
        $this->assertNotNull($tbfa->convert());
    }

    public function testEmptyArray()
    {
        $tbfa = new TomlBuilderFromArray([]);
        $this->assertEmpty($tbfa->convert());
    }

    public function testEmptyObject()
    {
        $tbfa = new TomlBuilderFromArray((object)[]);
        $this->assertEmpty($tbfa->convert());
    }

}