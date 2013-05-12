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
 
 * @see https://github.com/BurntSushi/toml-test/tree/master/tests/invalid
 */
class ParserInvalidTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testArrayMixedTypesArraysAndInts()
    {
        $parser = new Parser();

        $array = $parser->parse('arrays-and-ints =  [1, ["Arrays are not integers."]]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testArrayMixedTypesIntsAndFloats()
    {
        $parser = new Parser();

        $array = $parser->parse('ints-and-floats = [1, 1.0]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testArrayMixedTypesStringsAndInts()
    {
        $parser = new Parser();

        $array = $parser->parse('strings-and-ints = ["hi", 42]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDatetimeMalformedNoLeads()
    {
        $parser = new Parser();

        $array = $parser->parse('no-leads = 1987-7-05T17:45:00Z');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDatetimeMalformedNoSecs()
    {
        $parser = new Parser();

        $array = $parser->parse('no-secs = 1987-07-05T17:45Z');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDatetimeMalformedNoT()
    {
        $parser = new Parser();

        $array = $parser->parse('no-t = 1987-07-0517:45:00Z');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDatetimeMalformedNoZ()
    {
        $parser = new Parser();

        $array = $parser->parse('no-z = 1987-07-05T17:45:00');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDatetimeMalformedWithMilli()
    {
        $parser = new Parser();

        $array = $parser->parse('with-milli = 1987-07-5T17:45:00.12Z');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDuplicateKeyKeygroup()
    {
        $filename = __DIR__.'/Fixtures/duplicateKeyKeygroup.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDuplicateKeygroup()
    {
        $filename = __DIR__.'/Fixtures/duplicateKeygroup.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDuplicateKeys()
    {
        $filename = __DIR__.'/Fixtures/duplicateKeys.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testEmptyImplicitKeygroup()
    {        
        $parser = new Parser();

        $array = $parser->parse('[naughty..naughty]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testEmptyEmptyKeygroup()
    {        
        $parser = new Parser();

        $array = $parser->parse('[]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testFloatNoLeadingZero()
    {
        $filename = __DIR__.'/Fixtures/floatNoLeadingZero.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testFloatNoTrailingDigits()
    {
        $filename = __DIR__.'/Fixtures/floatNoTrailingDigits.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testKeyTwoEquals()
    {        
        $parser = new Parser();

        $array = $parser->parse('key= = 1');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testKeygroupNestedBracketsClose()
    {
        $filename = __DIR__.'/Fixtures/keygroupNestedBracketsClose.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testKeygroupNestedBracketsOpen()
    {
        $filename = __DIR__.'/Fixtures/keygroupNestedBracketsOpen.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringBadByteEscape()
    {        
        $parser = new Parser();

        $array = $parser->parse('naughty = "\xAg"');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringBadEscape()
    {        
        $parser = new Parser();

        $array = $parser->parse('invalid-escape = "This string has a bad \a escape character."');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringByteEscapes()
    {        
        $parser = new Parser();

        $array = $parser->parse('answer = "\x33"');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testStringNoClose()
    {        
        $parser = new Parser();

        $array = $parser->parse('no-ending-quote = "One time, at band camp');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextAfterArrayEntries()
    {
        $filename = __DIR__.'/Fixtures/textAfterArrayEntries.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextAfterInteger()
    {
        $parser = new Parser();

        $array = $parser->parse('answer = 42 the ultimate answer?');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextAfterKeygroup()
    {
        $parser = new Parser();

        $array = $parser->parse('[error] this shouldn\'t be here');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextAfterString()
    {
        $parser = new Parser();

        $array = $parser->parse('string = "Is there life after strings?" No.');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextBeforeArraySeparator()
    {
        $filename = __DIR__.'/Fixtures/textBeforeArraySeparator.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextInArray()
    {
        $filename = __DIR__.'/Fixtures/textInArray.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
}