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

use Yosymfony\Toml\Exception\ParseException;
use Yosymfony\Toml\Lexer;
use Yosymfony\Toml\ObjectParser;
use Yosymfony\Toml\Token;
use Yosymfony\Toml\Toml;

/*
 * Tests based on toml-test from BurntSushi
 *
 * @author Victor Puertas <vpgugr@gmail.com>

 * @see https://github.com/BurntSushi/toml-test/tree/master/tests/valid
 */
class ObjectParserTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayEmpty()
    {
        date_default_timezone_set('UTC');

        $parser = new ObjectParser();

        $object = $parser->parse('thevoid = [[[[[]]]]]');

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('thevoid', $object);

        $this->assertTrue(is_array($object->thevoid));
        $this->assertTrue(is_array($object->thevoid[0]));
        $this->assertTrue(is_array($object->thevoid[0][0]));
        $this->assertTrue(is_array($object->thevoid[0][0][0]));
        $this->assertTrue(is_array($object->thevoid[0][0][0][0]));
    }

    public function testArraysHeterogeneous()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('mixed = [[1, 2], ["a", "b"], [1.0, 2.0]]');

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('mixed', $object);

        $this->assertTrue(is_array($object->mixed[0]));
        $this->assertTrue(is_array($object->mixed[1]));
        $this->assertTrue(is_array($object->mixed[2]));

        $this->assertEquals($object->mixed[0][0], 1);
        $this->assertEquals($object->mixed[0][1], 2);

        $this->assertEquals($object->mixed[1][0], 'a');
        $this->assertEquals($object->mixed[1][1], 'b');

        $this->assertEquals($object->mixed[2][0], 1.0);
        $this->assertEquals($object->mixed[2][1], 2.0);
    }

    public function testArraysNested()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('nest = [["a"], ["b"]]');

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('nest', $object);

        $this->assertTrue(is_array($object->nest[0]));
        $this->assertTrue(is_array($object->nest[1]));

        $this->assertEquals($object->nest[0][0], 'a');
        $this->assertEquals($object->nest[1][0], 'b');
    }

    public function testArrays()
    {
        $filename = __DIR__.'/fixtures/valid/arrays.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('ints', $object);
        $this->assertObjectHasAttribute('floats', $object);
        $this->assertObjectHasAttribute('strings', $object);
        $this->assertObjectHasAttribute('dates', $object);

        $this->assertEquals($object->ints[0], 1);
        $this->assertEquals($object->ints[1], 2);
        $this->assertEquals($object->ints[2], 3);

        $this->assertEquals($object->floats[0], 1.0);
        $this->assertEquals($object->floats[1], 2.0);
        $this->assertEquals($object->floats[2], 3.0);

        $this->assertEquals($object->strings[0], 'a');
        $this->assertEquals($object->strings[1], 'b');
        $this->assertEquals($object->strings[2], 'c');

        $this->assertTrue($object->dates[0] instanceof \Datetime);
        $this->assertTrue($object->dates[1] instanceof \Datetime);
        $this->assertTrue($object->dates[2] instanceof \Datetime);
    }

    public function testBool()
    {
        $filename = __DIR__.'/fixtures/valid/bool.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('t', $object);
        $this->assertObjectHasAttribute('t', $object);

        $this->assertEquals($object->t, true);
        $this->assertEquals($object->f, false);
    }

    public function testCommentsEverywhere()
    {
        $filename = __DIR__.'/fixtures/valid/commentsEverywhere.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('answer', $object->group);
        $this->assertObjectHasAttribute('more', $object->group);

        $this->assertEquals($object->group->answer, 42);
        $this->assertEquals($object->group->more[0], 42);
        $this->assertEquals($object->group->more[1], 42);
    }

    public function testDatetime()
    {
        $parser = new ObjectParser();

        $object = $parser->parse("bestdayever = 1987-07-05T17:45:00Z");

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('bestdayever', $object);

        $this->assertTrue($object->bestdayever instanceof \Datetime);
    }

    public function testEmpty()
    {
        $parser = new ObjectParser();

        $object = $parser->parse("");

        $this->assertNull($object);
    }

    public function testExample()
    {
        $filename = __DIR__.'/fixtures/valid/example.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('best-day-ever', $object);
        $this->assertObjectHasAttribute('emptyName', $object);
        $this->assertObjectHasAttribute('numtheory', $object);

        $this->assertTrue($object->{'best-day-ever'} instanceof \Datetime);
        $this->assertEquals("", $object->emptyName);
        $this->assertTrue(is_object($object->numtheory));

        $this->assertEquals($object->numtheory->boring, false);
        $this->assertEquals($object->numtheory->perfection[0], 6);
        $this->assertEquals($object->numtheory->perfection[1], 28);
        $this->assertEquals($object->numtheory->perfection[2], 496);
    }

    public function testFloat()
    {
        $filename = __DIR__.'/fixtures/valid/float.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('pi', $object);
        $this->assertObjectHasAttribute('negpi', $object);

        $this->assertEquals($object->pi, 3.14);
        $this->assertEquals($object->negpi, -3.14);
    }

    public function testImplicitAndExplicitAfter()
    {
        $filename = __DIR__.'/fixtures/valid/implicitAndExplicitAfter.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('a', $object);
        $this->assertObjectHasAttribute('b', $object->a);
        $this->assertObjectHasAttribute('c', $object->a->b);
        $this->assertObjectHasAttribute('better', $object->a);

        $this->assertEquals($object->a->b->c->answer, 42);
        $this->assertEquals($object->a->better, 43);
    }

    public function testImplicitAndExplicitBefore()
    {
        $filename = __DIR__.'/fixtures/valid/implicitAndExplicitBefore.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('a', $object);
        $this->assertObjectHasAttribute('b', $object->a);
        $this->assertObjectHasAttribute('c', $object->a->b);
        $this->assertObjectHasAttribute('better', $object->a);

        $this->assertEquals($object->a->b->c->answer, 42);
        $this->assertEquals($object->a->better, 43);
    }

    public function testImplicitGroups()
    {
        $filename = __DIR__.'/fixtures/valid/implicitGroups.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertObjectHasAttribute('a', $object);
        $this->assertObjectHasAttribute('b', $object->a);
        $this->assertObjectHasAttribute('c', $object->a->b);

        $this->assertEquals($object->a->b->c->answer, 42);
    }

    public function testImplicitInteger()
    {
        $filename = __DIR__.'/fixtures/valid/integer.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertEquals($object->answer, 42);
        $this->assertEquals($object->neganswer, -42);
    }

    public function testKeyEqualsNoSpace()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('answer=42');

        $this->assertNotNull($object);

        $this->assertEquals($object->answer, 42);
    }

    public function testKeySpecialChars()
    {
        $filename = __DIR__.'/fixtures/valid/keySpecialChars.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertEquals($object->{"~!@#$^&*()_+-`1234567890[]\|/?><.,;:'"}, 1);
    }

    public function testKeyWithPound()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('key#name = 5');

        $this->assertNotNull($object);

        $this->assertEquals($object->{'key#name'}, 5);
    }

    public function testTableEmpty()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('[a]');

        $this->assertNotNull($object);

        $this->assertTrue(is_object($object->a));
    }

    public function testTableSubEmpty()
    {
        $filename = __DIR__.'/fixtures/valid/tableSubEmpty.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertTrue(is_object($object->a));
        $this->assertTrue(is_object($object->a->b));
    }

    public function testTableWhiteSpace()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('[valid key]');

        $this->assertNotNull($object);

        $this->assertTrue(is_object($object->{'valid key'}));
    }

    public function testTableWithPound()
    {
        $filename = __DIR__.'/fixtures/valid/tableWithPound.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertTrue(is_object($object->{'key#group'}));

        $this->assertEquals($object->{'key#group'}->answer, 42);
    }

    public function testLongFloat()
    {
        $filename = __DIR__.'/fixtures/valid/longFloat.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertEquals($object->longpi, 3.141592653589793);
        $this->assertEquals($object->neglongpi, -3.141592653589793);
    }

    public function testLongInteger()
    {
        $filename = __DIR__.'/fixtures/valid/longInteger.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertTrue($object->answer > 0);
        $this->assertTrue($object->neganswer < 0);
    }

    public function testStringEscapes()
    {
        $filename = __DIR__.'/fixtures/valid/stringEscapes.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertEquals($object->backspace, "This string has a \b backspace character.");
        $this->assertEquals($object->tab, "This string has a \t tab character.");
        $this->assertEquals($object->newline, "This string has a \n new line character.");
        $this->assertEquals($object->formfeed, "This string has a \f form feed character.");
        $this->assertEquals($object->carriage, "This string has a \r carriage return character.");
        $this->assertEquals($object->quote, "This string has a \" quote character.");
        $this->assertEquals($object->slash, "This string has a / slash character.");
        $this->assertEquals($object->backslash, "This string has a \\ backslash character.");
    }

    public function testStringSimple()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('answer = "You are not drinking enough whisky."');

        $this->assertNotNull($object);

        $this->assertEquals($object->answer, 'You are not drinking enough whisky.');
    }

    public function testStringWithPound()
    {
        $filename = __DIR__.'/fixtures/valid/stringWithPound.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);

        $this->assertEquals($object->pound, 'We see no # comments here.');
        $this->assertEquals($object->poundcomment, 'But there are # some comments here.');
    }

    public function testUnicodeEscape()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('answer = "\u03B4"');

        $this->assertNotNull($object);

        $this->assertEquals($object->answer, json_decode('"\u03B4"'));
    }

    public function testUnicodeLitteral()
    {
        $parser = new ObjectParser();

        $object = $parser->parse('answer = "δ"');

        $this->assertNotNull($object);

        $this->assertEquals($object->answer, 'δ');
    }

    public function testTableArrayImplicit()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayImplicit.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);
        $this->assertCount(1, get_object_vars($object));
        $this->assertEquals('Glory Days', $object->albums->songs->name);
    }

    public function testTableArrayMany()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayMany.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);
        $this->assertCount(1, get_object_vars($object));

        $this->assertEquals('Bruce', $object->people[0]->first_name);
        $this->assertEquals('Springsteen', $object->people[0]->last_name);

        $this->assertEquals('Eric', $object->people[1]->first_name);
        $this->assertEquals('Clapton', $object->people[1]->last_name);

        $this->assertEquals('Bob', $object->people[2]->first_name);
        $this->assertEquals('Seger', $object->people[2]->last_name);
    }

    public function testTableArrayNest()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayNest.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);
        $this->assertCount(1, get_object_vars($object));

        $this->assertEquals('Born to Run', $object->albums[0]->name);
        $this->assertEquals('Jungleland', $object->albums[0]->songs[0]->name);
        $this->assertEquals('Meeting Across the River', $object->albums[0]->songs[1]->name);

        $this->assertEquals('Born in the USA', $object->albums[1]->name);
        $this->assertEquals('Glory Days', $object->albums[1]->songs[0]->name);
        $this->assertEquals('Dancing in the Dark', $object->albums[1]->songs[1]->name);
    }

    public function testTableArrayOne()
    {
        $filename = __DIR__.'/fixtures/valid/tableArrayOne.toml';

        $parser = new ObjectParser();

        $object = $parser->parse(file_get_contents($filename));

        $this->assertNotNull($object);
        $this->assertCount(1, get_object_vars($object));

        $this->assertEquals('Bruce', $object->people[0]->first_name);
        $this->assertEquals('Springsteen', $object->people[0]->last_name);
    }
}
