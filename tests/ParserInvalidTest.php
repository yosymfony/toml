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
    public function testDuplicateKeyTable()
    {
        $filename = __DIR__.'/fixtures/invalid/duplicateKeyTable.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDuplicateTable()
    {
        $filename = __DIR__.'/fixtures/invalid/duplicateTable.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testDuplicateKeys()
    {
        $filename = __DIR__.'/fixtures/invalid/duplicateKeys.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testEmptyImplicitTable()
    {        
        $parser = new Parser();

        $array = $parser->parse('[naughty..naughty]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testEmptyTable()
    {        
        $parser = new Parser();

        $array = $parser->parse('[]');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testFloatNoLeadingZero()
    {
        $filename = __DIR__.'/fixtures/invalid/floatNoLeadingZero.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testFloatNoTrailingDigits()
    {
        $filename = __DIR__.'/fixtures/invalid/floatNoTrailingDigits.toml';
        
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
        $filename = __DIR__.'/fixtures/invalid/textAfterArrayEntries.toml';
        
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
    public function testTextAfterTable()
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
        $filename = __DIR__.'/fixtures/invalid/textBeforeArraySeparator.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTextInArray()
    {
        $filename = __DIR__.'/fixtures/invalid/textInArray.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTableArrayImplicit()
    {
        $filename = __DIR__.'/fixtures/invalid/tableArrayImplicit.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTableArrayMalformedBracket()
    {
        $filename = __DIR__.'/fixtures/invalid/tableArrayMalformedBracket.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTableArrayMalformedEmpty()
    {
        $filename = __DIR__.'/fixtures/invalid/tableArrayMalformedEmpty.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTableNestedBracketsClose()
    {
        $filename = __DIR__.'/fixtures/invalid/tableNestedBracketsClose.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTableNestedBracketsOpen()
    {
        $filename = __DIR__.'/fixtures/invalid/tableNestedBracketsOpen.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\ParseException
     */
    public function testTableArrayWithSomeNameOfTable()
    {
        $filename = __DIR__.'/fixtures/invalid/tableArrayWithSomeNameOfTable.toml';
        
        $parser = new Parser();
        
        $array = $parser->parse(file_get_contents($filename));
    }
}