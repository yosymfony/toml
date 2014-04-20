<?php

/*
 * This file is part of the Yosymfony\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Yosymfony\Toml\Tests;

use Yosymfony\Toml\Toml;
use Yosymfony\Toml\Exception\ParseException;

class TomlTest extends \PHPUnit_Framework_TestCase
{
    public function testFile()
    {
        $filename = __DIR__.'/fixtures/valid/toml.toml';
        
        $array = Toml::parse($filename);
        
        $this->assertNotNull($array);
    }
    
    public function testString()
    {
        $array = Toml::parse('data = "question"');
        
        $this->assertNotNull($array);
    }
    
    public function testStringEmpty()
    {
        $array = Toml::parse('');
    }
}