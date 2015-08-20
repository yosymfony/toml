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

use Yosymfony\Toml\Parser;
use Yosymfony\Toml\Toml;

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

        $array = $parser->parse('mixed = [[1, 2], ["a", "b"], [1.1, 2.1]]');

        $this->assertNotNull($array);

        $this->assertArrayHasKey('mixed', $array);

        $this->assertTrue(is_array($array['mixed'][0]));
        $this->assertTrue(is_array($array['mixed'][1]));
        $this->assertTrue(is_array($array['mixed'][2]));

        $this->assertEquals($array['mixed'][0][0], 1);
        $this->assertEquals($array['mixed'][0][1], 2);

        $this->assertEquals($array['mixed'][1][0], 'a');
        $this->assertEquals($array['mixed'][1][1], 'b');

        $this->assertEquals($array['mixed'][2][0], 1.1);
        $this->assertEquals($array['mixed'][2][1], 2.1);
    }

    public function testArraysNoSpaces()
    {
        $parser = new Parser();

        $array = $parser->parse('ints = [1,2,3]');

        $this->assertNotNull($array);

        $this->assertArrayHasKey('ints', $array);

        $this->assertEquals($array['ints'][0], 1);
        $this->assertEquals($array['ints'][1], 2);
        $this->assertEquals($array['ints'][2], 3);
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
        $filename = __DIR__.'/fixtures/valid/arrays.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertArrayHasKey('ints', $array);
        $this->assertArrayHasKey('floats', $array);
        $this->assertArrayHasKey('strings', $array);
        $this->assertArrayHasKey('dates', $array);

        $this->assertEquals(1, $array['ints'][0]);
        $this->assertEquals(2, $array['ints'][1]);
        $this->assertEquals(3, $array['ints'][2]);

        $this->assertEquals(1.1, $array['floats'][0]);
        $this->assertEquals(2.1, $array['floats'][1]);
        $this->assertEquals(3.1, $array['floats'][2]);

        $this->assertEquals('a', $array['strings'][0]);
        $this->assertEquals('b', $array['strings'][1]);
        $this->assertEquals('c', $array['strings'][2]);

        $this->assertEquals('all', $array['allStrings'][0]);
        $this->assertEquals('strings', $array['allStrings'][1]);
        $this->assertEquals('are the same', $array['allStrings'][2]);
        $this->assertEquals('type', $array['allStrings'][3]);

        $this->assertEquals('all', $array['MultilineBasicString'][0]);
        $this->assertEquals("Roses are red\nViolets are blue", $array['MultilineBasicString'][1]);

        $this->assertTrue($array['dates'][0] instanceof \Datetime);
        $this->assertTrue($array['dates'][1] instanceof \Datetime);
        $this->assertTrue($array['dates'][2] instanceof \Datetime);
    }

    public function testBool()
    {
        $filename = __DIR__.'/fixtures/valid/bool.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertArrayHasKey('t', $array);
        $this->assertArrayHasKey('t', $array);

        $this->assertEquals(true, $array['t']);
        $this->assertEquals(false, $array['f']);
    }

    public function testCommentsEverywhere()
    {
        $filename = __DIR__.'/fixtures/valid/commentsEverywhere.toml';

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
        $filename = __DIR__.'/fixtures/valid/datetime.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertArrayHasKey('bestdayever', $array);
        $this->assertArrayHasKey('bestdayever2', $array);
        $this->assertArrayHasKey('bestdayever3', $array);

        $this->assertTrue($array['bestdayever'] instanceof \Datetime);
        $this->assertTrue($array['bestdayever2'] instanceof \Datetime);
        $this->assertTrue($array['bestdayever3'] instanceof \Datetime);
    }

    public function testEmpty()
    {
        $parser = new Parser();

        $array = $parser->parse("");

        $this->assertNull($array);
    }

    public function testExample()
    {
        $filename = __DIR__.'/fixtures/valid/example.toml';

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
        $filename = __DIR__.'/fixtures/valid/float.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals(3.14, $array['pi']);
        $this->assertEquals(-3.14, $array['negpi']);
        $this->assertEquals(1.01, $array['positive']);
        $this->assertEquals(4.9999999999999996E+22, $array['exponent1']);
        $this->assertEquals(1000000.0, $array['exponent2']);
        $this->assertEquals(-0.02, $array['exponent3']);
        $this->assertEquals(6.6259999999999998E-34, $array['exponent4']);
        $this->assertEquals(6.6259999999999998E-34, $array['underscore']);
    }

    public function testImplicitAndExplicitAfter()
    {
        $filename = __DIR__.'/fixtures/valid/implicitAndExplicitAfter.toml';

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
        $filename = __DIR__.'/fixtures/valid/implicitAndExplicitBefore.toml';

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
        $filename = __DIR__.'/fixtures/valid/implicitGroups.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertArrayHasKey('a', $array);
        $this->assertArrayHasKey('b', $array['a']);
        $this->assertArrayHasKey('c', $array['a']['b']);

        $this->assertEquals($array['a']['b']['c']['answer'], 42);
    }

    public function testInteger()
    {
        $filename = __DIR__.'/fixtures/valid/integer.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals(42, $array['answer']);
        $this->assertEquals(-42, $array['neganswer']);
        $this->assertEquals(90, $array['positive']);
        $this->assertEquals(12345, $array['underscore']);
    }

    public function testKeyEqualsNoSpace()
    {
        $parser = new Parser();

        $array = $parser->parse('answer=42');

        $this->assertNotNull($array);

        $this->assertEquals($array['answer'], 42);
    }

    public function testKeySpace()
    {
        $parser = new Parser();

        $array = $parser->parse('"a b" = 1');

        $this->assertNotNull($array);

        $this->assertEquals($array['a b'], 1);
    }

    public function testKeySpecialChars()
    {
        $filename = __DIR__.'/fixtures/valid/keySpecialChars.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals($array['~!@$^&*()_+-`1234567890[]|/?><.,;:\''], 1);
    }

    public function testInlineTable()
    {
        $filename = __DIR__.'/fixtures/valid/inlineTable.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals('Tom', $array['name']['first']);
        $this->assertEquals('Preston-Werner', $array['name']['last']);

        $this->assertEquals(1, $array['point']['x']);
        $this->assertEquals(2, $array['point']['y']);

        $this->assertEquals("Roses are red\nViolets are blue", $array['strings']['key1']);
        $this->assertEquals('The quick brown fox jumps over the lazy dog.', $array['strings']['key2']);

        $this->assertEquals(1, $array['inline']['x']);
        $this->assertEquals(2, $array['inline']['y']['a']);
        $this->assertEquals('my value', $array['inline']['y']['b.deep']);

        $this->assertEquals(1, $array['another']['number']);
    }

    public function testInlineTableEmpty()
    {
        $parser = new Parser();

        $array = $parser->parse('name = {}');

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['name']));
    }

    public function testInlineTableOneElement()
    {
        $parser = new Parser();

        $array = $parser->parse('name = { first = "Tom" }');

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['name']));
        $this->assertEquals('Tom', $array['name']['first']);
    }

    public function testTableEmpty()
    {
        $parser = new Parser();

        $array = $parser->parse('[a]');

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['a']));
    }

    public function testTableSubEmpty()
    {
        $filename = __DIR__.'/fixtures/valid/tableSubEmpty.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['a']));
        $this->assertTrue(is_array($array['a']['b']));
    }

    public function testTableWhiteSpace()
    {
        $parser = new Parser();

        $array = $parser->parse('["valid key"]');

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['valid key']));
    }

    public function testTableQuotedName()
    {
        $filename = __DIR__.'/fixtures/valid/tableQuotedName.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['dog']));
        $this->assertTrue(is_array($array['dog']['tater.man']));
    }

    public function testTableWithPound()
    {
        $filename = __DIR__.'/fixtures/valid/tableWithPound.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertTrue(is_array($array['key#group']));

        $this->assertEquals($array['key#group']['answer'], 42);
    }

    public function testLongFloat()
    {
        $filename = __DIR__.'/fixtures/valid/longFloat.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals($array['longpi'], 3.141592653589793);
        $this->assertEquals($array['neglongpi'], -3.141592653589793);
    }

    public function testLongInteger()
    {
        $filename = __DIR__.'/fixtures/valid/longInteger.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertTrue($array['answer'] > 0);
        $this->assertTrue($array['neganswer'] < 0);
    }

    public function testMultilineString()
    {
        $filename = __DIR__.'/fixtures/valid/multilineString.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals('', $array['multiline_empty_one']);
        $this->assertEquals('', $array['multiline_empty_two']);
        $this->assertEquals('', $array['multiline_empty_three']);
        $this->assertEquals('', $array['multiline_empty_four']);
        $this->assertEquals('The quick brown fox jumps over the lazy dog.', $array['equivalent_one']);
        $this->assertEquals('The quick brown fox jumps over the lazy dog.', $array['equivalent_two']);
        $this->assertEquals('The quick brown fox jumps over the lazy dog.', $array['equivalent_three']);
    }

    public function testRawMultilineString()
    {
        $filename = __DIR__.'/fixtures/valid/rawMultilineString.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals("This string has a ' quote character.", $array['oneline']);
        $this->assertEquals("This string has a ' quote character.", $array['firstnl']);
        $this->assertEquals("This string\nhas ' a quote character\nand more than\none newline\nin it.", $array['multiline']);
    }

    public function testRawString()
    {
        $filename = __DIR__.'/fixtures/valid/rawString.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals('This string has a \b backspace character.', $array['backspace']);
        $this->assertEquals('This string has a \t tab character.', $array['tab']);
        $this->assertEquals('This string has a \n new line character.', $array['newline']);
        $this->assertEquals('This string has a \f form feed character.', $array['formfeed']);
        $this->assertEquals('This string has a \r carriage return character.', $array['carriage']);
        $this->assertEquals('This string has a \/ slash character.', $array['slash']);
        $this->assertEquals('This string has a \\\\ backslash character.', $array['backslash']);
    }

    public function testStringEmpty()
    {
        $parser = new Parser();

        $array = $parser->parse('answer = ""');

        $this->assertNotNull($array);

        $this->assertEquals($array['answer'], '');
    }

    public function testStringEscapes()
    {
        $filename = __DIR__.'/fixtures/valid/stringEscapes.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals("This string has a \b backspace character.", $array['backspace']);
        $this->assertEquals("This string has a \t tab character.", $array['tab']);
        $this->assertEquals("This string has a \n new line character.", $array['newline']);
        $this->assertEquals("This string has a \f form feed character.", $array['formfeed']);
        $this->assertEquals("This string has a \r carriage return character.", $array['carriage']);
        $this->assertEquals("This string has a \" quote character.", $array['quote']);
        $this->assertEquals("This string has a \\ backslash character.", $array['backslash']);
        $this->assertEquals("This string does not have a unicode \\u escape.", $array['notunicode1']);
        $this->assertEquals('This string does not have a unicode \u005Cu escape.', $array['notunicode2']);
        $this->assertEquals("This string does not have a unicode \\u0075 escape.", $array['notunicode3']);
        $this->assertEquals("This string does not have a unicode \\\u0075 escape.", $array['notunicode4']);
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
        $filename = __DIR__.'/fixtures/valid/stringWithPound.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals($array['pound'], 'We see no # comments here.');
        $this->assertEquals($array['poundcomment'], 'But there are # some comments here.');
    }

    public function testUnicodeEscape()
    {
        $filename = __DIR__.'/fixtures/valid/unicodeEscape.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);

        $this->assertEquals(json_decode('"\u03B4"'), $array['answer4']);
        $this->assertEquals(json_decode('"\u0000\u03B4"'), $array['answer8']);
    }

    public function testUnicodeLitteral()
    {
        $parser = new Parser();

        $array = $parser->parse('answer = "δ"');

        $this->assertNotNull($array);

        $this->assertEquals($array['answer'], 'δ');
    }

    public function testTableArrayImplicit()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayImplicit.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);
        $this->assertCount(1, $array);
        $this->assertEquals('Glory Days', $array['albums']['songs']['name']);
    }

    public function testTableArrayMany()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayMany.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);
        $this->assertCount(1, $array);

        $this->assertEquals('Bruce', $array['people'][0]['first_name']);
        $this->assertEquals('Springsteen', $array['people'][0]['last_name']);

        $this->assertEquals('Eric', $array['people'][1]['first_name']);
        $this->assertEquals('Clapton', $array['people'][1]['last_name']);

        $this->assertEquals('Bob', $array['people'][2]['first_name']);
        $this->assertEquals('Seger', $array['people'][2]['last_name']);
    }

    public function testTableArrayNest()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayNest.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);
        $this->assertCount(1, $array);

        $this->assertEquals('Born to Run', $array['albums'][0]['name']);
        $this->assertEquals('Jungleland', $array['albums'][0]['songs'][0]['name']);
        $this->assertEquals('Meeting Across the River', $array['albums'][0]['songs'][1]['name']);

        $this->assertEquals('Born in the USA', $array['albums'][1]['name']);
        $this->assertEquals('Glory Days', $array['albums'][1]['songs'][0]['name']);
        $this->assertEquals('Dancing in the Dark', $array['albums'][1]['songs'][1]['name']);
    }

    public function testTableArrayOne()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayOne.toml';

        $parser = new Parser();

        $array = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($array);
        $this->assertCount(1, $array);

        $this->assertEquals('Bruce', $array['people'][0]['first_name']);
        $this->assertEquals('Springsteen', $array['people'][0]['last_name']);
    }
}
