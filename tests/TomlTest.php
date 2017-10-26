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

    public function testParseMustReturnEmptyArrayWhenStringEmpty()
    {
        $array = Toml::parse('');

        $this->assertEquals([], $array);
    }

    public function testParseFileMustParseFile()
    {
        $filename = __DIR__.'/fixtures/simple.toml';

        $array = Toml::parseFile($filename);

        $this->assertEquals([
            'name' => 'Victor',
        ], $array);
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
