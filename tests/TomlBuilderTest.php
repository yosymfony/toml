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

use Yosymfony\Toml\TomlBuilder;
use Yosymfony\Toml\Toml;
use Yosymfony\Toml\Exception\DumpException;

class TomlBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testExample()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addComment('Toml file')->
            addGroup('data.string')->
            addValue('name', "Toml", 'This is your name')->
            addValue('newline', "This string has a \n new line character.")->
            addValue('winPath', "C:\\Users\\nodejs\\templates")->
            addValue('unicode', 'unicode character: ' . json_decode('"\u03B4"'))->
            
            addGroup('data.bool')->
            addValue('t', true)->
            addValue('f', false)->
            
            addGroup('data.integer')->
            addValue('positive', 25, 'Comment inline.')->
            addValue('negative', -25)->
            
            addGroup('data.float')->
            addValue('positive', 25.25)->
            addValue('negative', -25.25)->
            
            addGroup('data.datetime')->
            addValue('datetime', new \Datetime())->
            
            addComment('Related to arrays')->
            addGroup('data.array')->
            addValue('simple', array(1,2,3))->
            addValue('multiple', array( array(1,2), array('abc', 'def'), array(1.1, 1.2), array(true, false), array( new \Datetime()) ))->
            
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testArrayEmpty()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addComment('Toml file')->
            addValue('thevoid', array())->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testImplicitAndExplicitAfter()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('a.b.c')->
            addValue('answer', 42)->
            addGroup('a')->
            addValue('better', 43)->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testImplicitAndExplicitBefore()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('a')->
            addValue('better', 43)->
            addGroup('a.b.c')->
            addValue('answer', 42)->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testKeyGroupEmpty()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('a')->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testKeyGroupSubEmpty()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('a')->
            addGroup('a.b')->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testKeygroupWhitespace()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('valid key')->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testStringEscapesDoubleQuote()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue('backspace', "This string has a \b backspace character.")->
            addValue('tab', "This string has a \t tab character.")->
            addValue('newline', "This string has a \n new line character.")->
            addValue('formfeed', "This string has a \f form feed character.")->
            addValue('carriage', "This string has a \r carriage return character.")->
            addValue('quote', "This string has a \" quote character.")->
            addValue('slash', "This string has a \/ slash character.")->
            addValue('backslash', "This string has a \\ backslash character.")->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testKeySpecialChars() {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue("~!@#$^&*()_+-`1234567890[]\|/?><.,;:'", 1)->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
    public function testStringEscapesSingleQuote() {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue('backspace', 'This string has a \b backspace character.')->
            addValue('tab', 'This string has a \t tab character.')->
            addValue('newline', 'This string has a \n new line character.')->
            addValue('formfeed', 'This string has a \f form feed character.')->
            addValue('carriage', 'This string has a \r carriage return character.')->
            addValue('quote', 'This string has a \" quote character.')->
            addValue('slash', 'This string has a \/ slash character.')->
            addValue('backslash', 'This string has a \\ backslash character.')->
            getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
    
}