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
use Yosymfony\Toml\Parser;
use Yosymfony\Toml\Lexer;

class ParserInvalidTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new Parser(new Lexer());
    }

    public function tearDown()
    {
        $this->parser = null;
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_EQUAL" at line 1 with value "=".
     */
    public function testKeyEmpty()
    {
        $this->parser->parse('= 1');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_HASH" at line 1 with value "#".
     */
    public function testParseMustFailWhenKeyHash()
    {
        $this->parser->parse('a# = 1');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_NEWLINE" at line 1
     */
    public function testParseMustFailWhenKeyNewline()
    {
        $this->parser->parse("a\n= 1");
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage The key "dupe" has already been defined previously.
     */
    public function testDuplicateKeys()
    {
        $toml = <<<'toml'
        dupe = false
        dupe = true
toml;

        $this->parser->parse($toml);
    }

    /**
     * TOM04 spaces around '.' can be ignored, therefore space after a key name
     * isn't a problem, the problem is the first wrong character, the '='
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Unexpected '=' in path, Line 1
     */
    public function testParseMustFailWhenKeyOpenBracket()
    {
        $this->parser->parse('[abc = 1');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_EOS" at line 1
     */
    public function testParseMustFailWhenKeySingleOpenBracket()
    {
        $this->parser->parse('[');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "b".
     */
    public function testParseMustFailWhenKeySpace()
    {
        $this->parser->parse('a b = 1');
    }
    /** TOM04 - White space around . is ignored, best practice is no white space, but
     * the fail problem is the '='
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Unexpected '=' in path, Line 2
     */
    public function testParseMustFailWhenKeyStartBracket()
    {
        $this->parser->parse("[a]\n[xyz = 5\n[b]");
    }


    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_EQUAL" at line 1 with value "=".
     */
    public function testParseMustFailWhenKeyTwoEquals()
    {
        $this->parser->parse('key= = 1');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "the".
     */
    public function testParseMustFailWhenTextAfterInteger()
    {
        $this->parser->parse('answer = 42 the ultimate answer?');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Invalid integer number: leading zeros are not allowed. Token: "T_INTEGER" line: 1 value "042".
     */
    public function testParseMustFailWhenIntegerLeadingZeros()
    {
        $this->parser->parse('answer = 042');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "_42".
     */
    public function testParseMustFailWhenIntegerLeadingUnderscore()
    {
        $this->parser->parse('answer = _42');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Invalid integer number: underscore must be surrounded by at least one digit.
     */
    public function testParseMustFailWhenIntegerFinalUnderscore()
    {
        $this->parser->parse('answer = 42_');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Invalid integer number: leading zeros are not allowed. Token: "T_INTEGER" line: 1 value "0_42".
     */
    public function testParseMustFailWhenIntegerLeadingZerosWithUnderscore()
    {
        $this->parser->parse('answer = 0_42');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_DOT" at line 1 with value ".".
     */
    public function testParseMustFailWhenFloatNoLeadingZero()
    {
        $toml = <<<'toml'
        answer = .12345
        neganswer = -.12345
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_DOT" at line 1 with value ".".
     */
    public function testParseMustFailWhenFloatNoTrailingDigits()
    {
        $toml = <<<'toml'
        answer = 1.
        neganswer = -1.
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "_1".
     */
    public function testParseMustFailWhenFloatLeadingUnderscore()
    {
        $this->parser->parse('number = _1.01');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Invalid float number: underscore must be surrounded by at least one digit.
     */
    public function testParseMustFailWhenFloatFinalUnderscore()
    {
        $this->parser->parse('number = 1.01_');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Invalid float number: underscore must be surrounded by at least one digit.
     */
    public function testParseMustFailWhenFloatUnderscorePrefixE()
    {
        $this->parser->parse('number = 1_e6');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "e_6".
     */
    public function testParseMustFailWhenFloatUnderscoreSufixE()
    {
        $this->parser->parse('number = 1e_6');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_INTEGER" at line 1 with value "-7".
     */
    public function testParseMustFailWhenDatetimeMalformedNoLeads()
    {
        $this->parser->parse('no-leads = 1987-7-05T17:45:00Z');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "T17".
     */
    public function testParseMustFailWhenDatetimeMalformedNoSecs()
    {
        $this->parser->parse('no-secs = 1987-07-05T17:45Z');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_INTEGER" at line 1 with value "17".
     */
    public function testParseMustFailWhenDatetimeMalformedNoT()
    {
        $this->parser->parse('no-t = 1987-07-0517:45:00Z');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_INTEGER" at line 1 with value "-07".
     */
    public function testParseMustFailWhenDatetimeMalformedWithMilli()
    {
        $this->parser->parse('with-milli = 1987-07-5T17:45:00.12Z');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_ESCAPE" at line 1 with value "\". This character is not valid.
     */
    public function testParseMustFailWhenBasicStringHasBadByteEscape()
    {
        $this->parser->parse('naughty = "\xAg"');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_ESCAPE" at line 1 with value "\". This character is not valid.
     */
    public function testParseMustFailWhenBasicStringHasBadEscape()
    {
        $this->parser->parse('invalid-escape = "This string has a bad \a escape character."');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_ESCAPE" at line 1 with value "\". This character is not valid.
     */
    public function testParseMustFailWhenBasicStringHasByteEscapes()
    {
        $this->parser->parse('answer = "\x33"');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_EOS" at line 1 with value "". This character is not valid.
     */
    public function testParseMustFailWhenBasicStringIsNotClose()
    {
        $this->parser->parse('no-ending-quote = "One time, at band camp');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "No". Expected T_NEWLINE or T_EOS.
     */
    public function testParseMustFailWhenThereIsTextAfterBasicString()
    {
        $this->parser->parse('string = "Is there life after strings?" No.');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Data types cannot be mixed in an array. Value: "1".
     */
    public function testParseMustFailWhenThereIsAnArrayWithMixedTypesArraysAndInts()
    {
        $this->parser->parse('arrays-and-ints =  [1, ["Arrays are not integers."]]');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Data types cannot be mixed in an array. Value: "1.1".
     */
    public function testParseMustFailWhenThereIsAnArrayWithMixedTypesIntsAndFloats()
    {
        $this->parser->parse('ints-and-floats = [1, 1.1]');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Data types cannot be mixed in an array. Value: "42".
     */
    public function testParseMustFailWhenThereIsAnArrayWithMixedTypesStringsAndInts()
    {
        $this->parser->parse('strings-and-ints = ["hi", 42]');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 2 with value "No".
     */
    public function testParseMustFailWhenAppearsTextAfterArrayEntries()
    {
        $toml = <<<'toml'
        array = [
            "Is there life after an array separator?", No
            "Entry"
        ]
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 2 with value "No".
     */
    public function testParseMustFailWhenAppearsTextBeforeArraySeparator()
    {
        $toml = <<<'toml'
        array = [
            "Is there life before an array separator?" No,
            "Entry"
        ]
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage  Syntax error: unexpected token "T_UNQUOTED_KEY" at line 3 with value "I".
     */
    public function testParseMustFailWhenAppearsTextInArray()
    {
        $toml = <<<'toml'
        array = [
            "Entry 1",
            I don't belong,
            "Entry 2",
        ]
toml;
        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage The key "fruit.type" has already been defined previously.
     */
    public function testParseMustFailWhenDuplicateKeyTable()
    {
        $toml = <<<'toml'
        [fruit]
        type = "apple"

        [fruit.type]
        apple = "yes"
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Table path [a] at line 2 interferes with path at line 1
     */
    public function testParseMustFailWhenDuplicateTable()
    {
        $toml = <<<'toml'
        [a]
        [a]
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Path cannot be empty, Line 1
     */
    public function testParseMustFailWhenTableEmpty()
    {
        $this->parser->parse('[]');
    }

    /**
     * TOM04 - expected a dot
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Expected a '.' after path key, Line 1
     */
    public function testParseMustFailWhenTableWhitespace()
    {
        $this->parser->parse('[invalid key]');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Found '..' in path, Line 1
     */
    public function testParseMustFailWhenEmptyImplicitTable()
    {
        $this->parser->parse('[naughty..naughty]');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Unexpected '#' in path, Line 1
     */
    public function testParseMustFailWhenTableWithPound()
    {
        $this->parser->parse("[key#group]\nanswer = 42");
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "this".
     */
    public function testParseMustFailWhenTextAfterTable()
    {
        $this->parser->parse('[error] this shouldn\'t be here');
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Expected a '.' after path key, Line 1
     */
    public function testParseMustFailWhenTableNestedBracketsOpen()
    {
        $toml = <<<'toml'
        [a[b]
        zyx = 42
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "b".
     */
    public function testParseMustFailWhenTableNestedBracketsClose()
    {
        $toml = <<<'toml'
        [a]b]
        zyx = 42
toml;
        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Syntax error: unexpected token "T_NEWLINE" at line 1
     */
    public function testParseMustFailWhenInlineTableWithNewline()
    {
        $toml = <<<'toml'
        name = { first = "Tom",
	           last = "Preston-Werner"
        }
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Table path [fruit.variety] at line 8 interferes with path at line 4
     */
    public function testParseMustFailWhenTableArrayWithSomeNameOfTable()
    {
        $toml = <<<'toml'
        [[fruit]]
        name = "apple"

        [[fruit.variety]]
        name = "red delicious"

        # This table conflicts with the previous table
        [fruit.variety]
        name = "granny smith"
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage Path cannot be empty, Line 1
     */
    public function testParseMustFailWhenTableArrayMalformedEmpty()
    {
        $toml = <<<'toml'
        [[]]
        name = "Born to Run"
toml;

        $this->parser->parse($toml);
    }

    /**
     * @expectedException Yosymfony\ParserUtils\SyntaxErrorException
     * @expectedExceptionMessage New line in unfinished path, Line 1
     */
    public function testParseMustFailWhenTableArrayMalformedBracket()
    {
        $toml = <<<'toml'
        [[albums]
        name = "Born to Run"
toml;

        $this->parser->parse($toml);
    }

    
}
