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
use Yosymfony\Toml\Lexer;

class LexerTest extends TestCase
{
    private $lexer;

    public function setUp()
    {
        $this->lexer = new Lexer();
    }

    public function testTokenizeMustRecognizeEqualToken()
    {
        $ts = $this->lexer->tokenize('=');

        $this->assertTrue($ts->peekNext() === Lexer::T_EQUAL);
    }

    public function testTokenizeMustRecognizeBooleanTokenWhenThereIsATrueValue()
    {
        $ts = $this->lexer->tokenize('true');

        $this->assertTrue($ts->peekNext() === Lexer::T_BOOLEAN);
    }

    public function testTokenizeMustRecognizeBooleanTokenWhenThereIsAFalseValue()
    {
        $ts = $this->lexer->tokenize('false');

        $this->assertTrue($ts->peekNext() === Lexer::T_BOOLEAN);
    }

    public function testTokenizeMustRecognizeUnquotedKeyToken()
    {
        $ts = $this->lexer->tokenize('title');

        $this->assertTrue($ts->peekNext() === Lexer::T_UNQUOTED_KEY);
    }

    public function testTokenizeMustRecognizeIntegerTokenWhenThereIsAPositiveNumber()
    {
        $ts = $this->lexer->tokenize('25');

        $this->assertTrue($ts->peekNext() === Lexer::T_INTEGER);
    }

    public function testTokenizeMustRecognizeIntegerTokenWhenThereIsANegativeNumber()
    {
        $ts = $this->lexer->tokenize('-25');

        $this->assertTrue($ts->peekNext() === Lexer::T_INTEGER);
    }

    public function testTokenizeMustRecognizeIntegerTokenWhenThereIsNumberWithUnderscoreSeparator()
    {
        $ts = $this->lexer->tokenize('2_5');

        $this->assertTrue($ts->peekNext() === Lexer::T_INTEGER);
    }

    public function testTokenizeMustRecognizeFloatTokenWhenThereIsAFloatNumber()
    {
        $ts = $this->lexer->tokenize('2.5');

        $this->assertTrue($ts->peekNext() === Lexer::T_FLOAT);
    }

    public function testTokenizeMustRecognizeFloatTokenWhenThereIsAFloatNumberWithUnderscoreSeparator()
    {
        $ts = $this->lexer->tokenize('9_224_617.445_991_228_313');

        $this->assertTrue($ts->peekNext() === Lexer::T_FLOAT);
    }

    public function testTokenizeMustRecognizeFloatTokenWhenThereIsANegativeFloatNumber()
    {
        $ts = $this->lexer->tokenize('-2.5');

        $this->assertTrue($ts->peekNext() === Lexer::T_FLOAT);
    }

    public function testTokenizeMustRecognizeFloatTokenWhenThereIsANumberWithExponent()
    {
        $ts = $this->lexer->tokenize('5e+22');

        $this->assertTrue($ts->peekNext() === Lexer::T_FLOAT);
    }

    public function testTokenizeMustRecognizeFloatTokenWhenThereIsANumberWithExponentAndUnderscoreSeparator()
    {
        $ts = $this->lexer->tokenize('1e1_000');

        $this->assertTrue($ts->peekNext() === Lexer::T_FLOAT);
    }

    public function testTokenizeMustRecognizeFloatTokenWhenThereIsAFloatNumberWithExponent()
    {
        $ts = $this->lexer->tokenize('6.626e-34');

        $this->assertTrue($ts->peekNext() === Lexer::T_FLOAT);
    }

    public function testTokenizeMustRecognizeDataTimeTokenWhenThereIsRfc3339Datetime()
    {
        $ts = $this->lexer->tokenize('1979-05-27T07:32:00Z');

        $this->assertTrue($ts->peekNext() === Lexer::T_DATE_TIME);
    }

    public function testTokenizeMustRecognizeDataTimeTokenWhenThereIsRfc3339DatetimeWithOffset()
    {
        $ts = $this->lexer->tokenize('1979-05-27T00:32:00-07:00');

        $this->assertTrue($ts->peekNext() === Lexer::T_DATE_TIME);
    }

    public function testTokenizeMustRecognizeDataTimeTokenWhenThereIsRfc3339DatetimeWithOffsetSecondFraction()
    {
        $ts = $this->lexer->tokenize('1979-05-27T00:32:00.999999-07:00');

        $this->assertTrue($ts->peekNext() === Lexer::T_DATE_TIME);
    }

    public function testTokenizeMustRecognizeQuotationMark()
    {
        $ts = $this->lexer->tokenize('"');

        $this->assertTrue($ts->peekNext() === Lexer::T_QUOTATION_MARK);
    }

    public function testTokenizeMustRecognize3QuotationMark()
    {
        $ts = $this->lexer->tokenize('"""');

        $this->assertTrue($ts->isNextSequence([Lexer::T_3_QUOTATION_MARK, Lexer::T_EOS]));
    }

    public function testTokenizeMustRecognizeApostrophe()
    {
        $ts = $this->lexer->tokenize("'");

        $this->assertTrue($ts->peekNext() === Lexer::T_APOSTROPHE);
    }

    public function testTokenizeMustRecognize3Apostrophe()
    {
        $ts = $this->lexer->tokenize("'''");

        $this->assertTrue($ts->peekNext() === Lexer::T_3_APOSTROPHE);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsBackspace()
    {
        $ts = $this->lexer->tokenize('\b');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsTab()
    {
        $ts = $this->lexer->tokenize('\t');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsLinefeed()
    {
        $ts = $this->lexer->tokenize('\n');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsFormfeed()
    {
        $ts = $this->lexer->tokenize('\f');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsCarriageReturn()
    {
        $ts = $this->lexer->tokenize('\r');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsQuote()
    {
        $ts = $this->lexer->tokenize('\"');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsBackslash()
    {
        $ts = $this->lexer->tokenize('\\\\');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsUnicodeUsingFourCharacters()
    {
        $ts = $this->lexer->tokenize('\u00E9');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeEscapedCharacterWhenThereIsUnicodeUsingEightCharacters()
    {
        $ts = $this->lexer->tokenize('\U00E90000');

        $this->assertTrue($ts->peekNext() === Lexer::T_ESCAPED_CHARACTER);
    }

    public function testTokenizeMustRecognizeBasicUnescapedString()
    {
        $ts = $this->lexer->tokenize('@text');

        $this->assertTrue($ts->isNextSequence([
            Lexer::T_BASIC_UNESCAPED,
            Lexer::T_EOS
        ]));
    }

    public function testTokenizeMustRecognizeHash()
    {
        $ts = $this->lexer->tokenize('#');

        $this->assertTrue($ts->peekNext() === Lexer::T_HASH);
    }

    public function testTokenizeMustRecognizeEscape()
    {
        $ts = $this->lexer->tokenize('\\');

        $this->assertTrue($ts->isNextSequence([Lexer::T_ESCAPE, Lexer::T_EOS]));
    }

    public function testTokenizeMustRecognizeEscapeAndEscapedCharacter()
    {
        $ts = $this->lexer->tokenize('\\ \b');

        $this->assertTrue($ts->isNextSequence([
            Lexer::T_ESCAPE,
            Lexer::T_SPACE,
            Lexer::T_ESCAPED_CHARACTER,
            Lexer::T_EOS
        ]));
    }

    public function testTokenizeMustRecognizeLeftSquareBraket()
    {
        $ts = $this->lexer->tokenize('[');

        $this->assertTrue($ts->peekNext() === Lexer::T_LEFT_SQUARE_BRAKET);
    }

    public function testTokenizeMustRecognizeRightSquareBraket()
    {
        $ts = $this->lexer->tokenize(']');

        $this->assertTrue($ts->peekNext() === Lexer::T_RIGHT_SQUARE_BRAKET);
    }

    public function testTokenizeMustRecognizeDot()
    {
        $ts = $this->lexer->tokenize('.');

        $this->assertTrue($ts->peekNext() === Lexer::T_DOT);
    }

    public function testTokenizeMustRecognizeLeftCurlyBrace()
    {
        $ts = $this->lexer->tokenize('{');

        $this->assertTrue($ts->peekNext() === Lexer::T_LEFT_CURLY_BRACE);
    }

    public function testTokenizeMustRecognizeRightCurlyBrace()
    {
        $ts = $this->lexer->tokenize('}');

        $this->assertTrue($ts->peekNext() === Lexer::T_RIGHT_CURLY_BRACE);
    }
}
