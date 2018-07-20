<?php

namespace Yosymfony\Toml\tests;


use PHPUnit\Framework\TestCase;
use Yosymfony\Toml\TomlBuilderFromArray;

class TomlBuilderFromArrayInvalidTest extends TestCase
{
    /**
     * @expectedException Yosymfony\Toml\Exception\ParseException
     */
    public function testEmptyString()
    {
        $tbfa = new TomlBuilderFromArray('');
        $this->assertNotNull($tbfa->convert());
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testInvalidType()
    {
        $fp = fopen('php://input', 'r');
        $array = [
            'a' => 123,
            'b' => $fp
        ];
        $tbfa = new TomlBuilderFromArray($array);
        $tbfa->convert();
    }

}