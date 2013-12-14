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
 
use Yosymfony\Toml\Parser;
use Yosymfony\Toml\Lexer;
use Yosymfony\Toml\Token;
use Yosymfony\Toml\Toml;

use Yosymfony\Toml\Exception\ParseException;

/*
 * Tests based on toml-test from BurntSushi
 *
 * @author Victor Puertas <vpgugr@gmail.com>
 
 * @see https://github.com/BurntSushi/toml-test/tree/master/tests/valid
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayEmpty()
    {
        date_default_timezone_set('UTC');
        
        $parser = new Parser();
        
        $array = $parser->parse('thevoid = [[[[[]]]]]');
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('thevoid', $array);
        
        $this->assertTrue(is_array($array['thevoid']));
        $this->assertTrue(is_array($array['thevoid'][0]));
        $this->assertTrue(is_array($array['thevoid'][0][0]));
        $this->assertTrue(is_array($array['thevoid'][0][0][0]));
        $this->assertTrue(is_array($array['thevoid'][0][0][0][0]));
    }
    
    public function testArraysHeterogeneous()
    {
        $parser = new Parser();
        
        $array = $parser->parse('mixed = [[1, 2], ["a", "b"], [1.0, 2.0]]');
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('mixed', $array);
        
        $this->assertTrue(is_array($array['mixed'][0]));
        $this->assertTrue(is_array($array['mixed'][1]));
        $this->assertTrue(is_array($array['mixed'][2]));
        
        $this->assertEquals($array['mixed'][0][0], 1);
        $this->assertEquals($array['mixed'][0][1], 2);
        
        $this->assertEquals($array['mixed'][1][0], 'a');
        $this->assertEquals($array['mixed'][1][1], 'b');
        
        $this->assertEquals($array['mixed'][2][0], 1.0);
        $this->assertEquals($array['mixed'][2][1], 2.0);
    }
    
    public function testArraysNested()
    {
        $parser = new Parser();
        
        $array = $parser->parse('nest = [["a"], ["b"]]');
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('nest', $array);
        
        $this->assertTrue(is_array($array['nest'][0]));
        $this->assertTrue(is_array($array['nest'][1]));
        
        $this->assertEquals($array['nest'][0][0], 'a');
        $this->assertEquals($array['nest'][1][0], 'b');
    }
    
    public function testArrays()
    {   
        $filename = __DIR__.'/Fixtures/arrays.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('ints', $array);
        $this->assertArrayHasKey('floats', $array);
        $this->assertArrayHasKey('strings', $array);
        $this->assertArrayHasKey('dates', $array);
        
        $this->assertEquals($array['ints'][0], 1);
        $this->assertEquals($array['ints'][1], 2);
        $this->assertEquals($array['ints'][2], 3);
        
        $this->assertEquals($array['floats'][0], 1.0);
        $this->assertEquals($array['floats'][1], 2.0);
        $this->assertEquals($array['floats'][2], 3.0);
        
        $this->assertEquals($array['strings'][0], 'a');
        $this->assertEquals($array['strings'][1], 'b');
        $this->assertEquals($array['strings'][2], 'c');

        $this->assertTrue($array['dates'][0] instanceof \Datetime);
        $this->assertTrue($array['dates'][1] instanceof \Datetime);
        $this->assertTrue($array['dates'][2] instanceof \Datetime);
    }
    
    public function testBool()
    {
        $filename = __DIR__.'/Fixtures/bool.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('t', $array);
        $this->assertArrayHasKey('t', $array);
        
        $this->assertEquals($array['t'], true);
        $this->assertEquals($array['f'], false);
    }
    
    public function testCommentsEverywhere()
    {
        $filename = __DIR__.'/Fixtures/commentsEverywhere.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('answer', $array['group']);
        $this->assertArrayHasKey('more', $array['group']);
        
        $this->assertEquals($array['group']['answer'], 42);
        $this->assertEquals($array['group']['more'][0], 42);
        $this->assertEquals($array['group']['more'][1], 42);
    }
    
    public function testDatetime()
    {
        $parser = new Parser();
        
        $array = $parser->parse("bestdayever = 1987-07-05T17:45:00Z");
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('bestdayever', $array);
        
        $this->assertTrue($array['bestdayever'] instanceof \Datetime);
    }
    
    public function testEmpty()
    {
        $parser = new Parser();
        
        $array = $parser->parse("");
        
        $this->assertNull($array);
    }
    
    public function testExample()
    {
        $filename = __DIR__.'/Fixtures/example.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('best-day-ever', $array);
        $this->assertArrayHasKey('emptyName', $array);
        $this->assertArrayHasKey('numtheory', $array);
        
        $this->assertTrue($array['best-day-ever'] instanceof \Datetime);
        $this->assertEquals("", $array['emptyName']);
        $this->assertTrue(is_array($array['numtheory']));
        
        $this->assertEquals($array['numtheory']['boring'], false);
        $this->assertEquals($array['numtheory']['perfection'][0], 6);
        $this->assertEquals($array['numtheory']['perfection'][1], 28);
        $this->assertEquals($array['numtheory']['perfection'][2], 496);
    }
    
    public function testFloat()
    {
        $filename = __DIR__.'/Fixtures/float.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('pi', $array);
        $this->assertArrayHasKey('negpi', $array);
        
        $this->assertEquals($array['pi'], 3.14);
        $this->assertEquals($array['negpi'], -3.14);
    }
    
    public function testImplicitAndExplicitAfter()
    {
        $filename = __DIR__.'/Fixtures/implicitAndExplicitAfter.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('a', $array);
        $this->assertArrayHasKey('b', $array['a']);
        $this->assertArrayHasKey('c', $array['a']['b']);
        $this->assertArrayHasKey('better', $array['a']);
        
        $this->assertEquals($array['a']['b']['c']['answer'], 42);
        $this->assertEquals($array['a']['better'], 43);
    }
    
    public function testImplicitAndExplicitBefore()
    {
        $filename = __DIR__.'/Fixtures/implicitAndExplicitBefore.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('a', $array);
        $this->assertArrayHasKey('b', $array['a']);
        $this->assertArrayHasKey('c', $array['a']['b']);
        $this->assertArrayHasKey('better', $array['a']);
        
        $this->assertEquals($array['a']['b']['c']['answer'], 42);
        $this->assertEquals($array['a']['better'], 43);
    }
    
    public function testImplicitGroups()
    {
        $filename = __DIR__.'/Fixtures/implicitGroups.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertArrayHasKey('a', $array);
        $this->assertArrayHasKey('b', $array['a']);
        $this->assertArrayHasKey('c', $array['a']['b']);
        
        $this->assertEquals($array['a']['b']['c']['answer'], 42);
    }
    
    public function testImplicitInteger()
    {
        $filename = __DIR__.'/Fixtures/integer.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['answer'], 42);
        $this->assertEquals($array['neganswer'], -42);
    }
    
    public function testKeyEqualsNoSpace()
    {
        $parser = new Parser();
        
        $array = $parser->parse('answer=42');
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['answer'], 42);
    }
    
    public function testKeySpecialChars()
    {
        $filename = __DIR__.'/Fixtures/keySpecialChars.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array["~!@#$^&*()_+-`1234567890[]\|/?><.,;:'"], 1);
    }
    
    public function testKeyWithPound()
    {
        $parser = new Parser();
        
        $array = $parser->parse('key#name = 5');
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['key#name'], 5);
    }
    
    public function testKeyGroupEmpty()
    {
        $parser = new Parser();
        
        $array = $parser->parse('[a]');
        
        $this->assertNotNull($array);
        
        $this->assertTrue(is_array($array['a']));
    }
    
    public function testKeygroupSubEmpty()
    {
        $filename = __DIR__.'/Fixtures/keygroupSubEmpty.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertTrue(is_array($array['a']));
        $this->assertTrue(is_array($array['a']['b']));
    }
    
    public function testKeyGroupWhiteSpace()
    {
        $parser = new Parser();
        
        $array = $parser->parse('[valid key]');
        
        $this->assertNotNull($array);
        
        $this->assertTrue(is_array($array['valid key']));
    }
    
    public function testKeyGroupWithPound()
    {
        $filename = __DIR__.'/Fixtures/keyGroupWithPound.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertTrue(is_array($array['key#group']));
        
        $this->assertEquals($array['key#group']['answer'], 42);
    }
    
    public function testLongFloat()
    {
        $filename = __DIR__.'/Fixtures/longFloat.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['longpi'], 3.141592653589793);
        $this->assertEquals($array['neglongpi'], -3.141592653589793);
    }
    
    public function testLongInteger()
    {
        $filename = __DIR__.'/Fixtures/longInteger.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertTrue($array['answer'] > 0);
        $this->assertTrue($array['neganswer'] < 0);
    }
    
    public function testStringEscapes()
    {
        $filename = __DIR__.'/Fixtures/stringEscapes.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['backspace'], "This string has a \b backspace character.");
        $this->assertEquals($array['tab'], "This string has a \t tab character.");
        $this->assertEquals($array['newline'], "This string has a \n new line character.");
        $this->assertEquals($array['formfeed'], "This string has a \f form feed character.");
        $this->assertEquals($array['carriage'], "This string has a \r carriage return character.");
        $this->assertEquals($array['quote'], "This string has a \" quote character.");
        $this->assertEquals($array['slash'], "This string has a / slash character.");
        $this->assertEquals($array['backslash'], "This string has a \\ backslash character.");
    }
    
    public function testStringSimple()
    {
        $parser = new Parser();
        
        $array = $parser->parse('answer = "You are not drinking enough whisky."');
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['answer'], 'You are not drinking enough whisky.');
    }
    
    public function testStringWithPound()
    {
        $filename = __DIR__.'/Fixtures/stringWithPound.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['pound'], 'We see no # comments here.');
        $this->assertEquals($array['poundcomment'], 'But there are # some comments here.');
    }
    
    public function testUnicodeEscape()
    {
        $parser = new Parser();
        
        $array = $parser->parse('answer = "\u03B4"');
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['answer'], json_decode('"\u03B4"'));
    }
    
    public function testUnicodeLitteral()
    {
        $parser = new Parser();
        
        $array = $parser->parse('answer = "δ"');
        
        $this->assertNotNull($array);
        
        $this->assertEquals($array['answer'], 'δ');
    }
}