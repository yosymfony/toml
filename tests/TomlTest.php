<?php

/*
 * This file is part of the Yosymfony\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yosymfony\Toml\tests;

use PHPUnit\Framework\TestCase;
use Yosymfony\Toml\Toml;

class TomlTest extends TestCase
{
    public function testParseMustParseAString()
    {
        $array = Toml::parse('data = "question"');

        $this->assertEquals([
            'data' => 'question',
        ], $array);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\ParseException
     * @expectedExceptionMessage Syntax error: unexpected token "T_EOS" at line 1 with value "". Expected "T_EQUAL".
     */
    public function testParseShouldThrowParseException()
    {
        Toml::parse('invalid_toml_string');
    }

    public function testParseMustReturnEmptyArrayWhenStringEmpty()
    {
        $array = Toml::parse('');

        $this->assertNull($array);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\ParseException
     */
    public function testParseFileShouldThrowParseException()
    {
        Toml::parseFile(__DIR__.'/fixtures/invalid.toml');
    }

    public function testParseFileMustParseFile()
    {
        $filename = __DIR__.'/fixtures/simple.toml';

        $array = Toml::parseFile($filename);

        $this->assertEquals([
            'name' => 'Víctor',
        ], $array);
    }

    public function testParseMustReturnAnObjectWhenArgumentResultAsObjectIsTrue()
    {
        $actual = Toml::parse('name = "Víctor"', true);
        $expected = new \stdClass();
        $expected->name = 'Víctor';

        $this->assertEquals($expected, $actual);
    }

    public function testParseFileMustReturnAnObjectWhenArgumentResultAsObjectIsTrue()
    {
        $filename = __DIR__.'/fixtures/simple.toml';

        $actual = Toml::parseFile($filename, true);
        $expected = new \stdClass();
        $expected->name = 'Víctor';

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\ParseException
     */
    public function testParseFileMustFailWhenFilenameDoesNotExists()
    {
        $filename = __DIR__.'/fixtures/does-not-exists.toml';

        Toml::parseFile($filename);
    }
}
