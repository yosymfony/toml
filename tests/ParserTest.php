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

class ParserTest extends TestCase
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

    public function testParseMustReturnAnEmptyArrayWhenEmptyInput()
    {
        $array = $this->parser->parse('');

        $this->assertEquals([], $array);
    }

    public function testParseMustParseBooleans()
    {
        $toml = <<<'toml'
        t = true
        f = false
toml;
        $array = $this->parser->parse($toml);

        $this->assertEquals([
            't' => true,
            'f' => false,
        ], $array);
    }

    public function testParseMustParseIntegers()
    {
        $toml = <<<'toml'
        answer = 42
        neganswer = -42
        positive = +90
        underscore = 1_2_3_4_5
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'answer' => 42,
            'neganswer' => -42,
            'positive' => 90,
            'underscore' => 12345,
        ], $array);
    }

    public function testParseMustParseLongIntegers()
    {
        $toml = <<<'toml'
        answer = 9223372036854775807
        neganswer = -9223372036854775808
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'answer' => 9223372036854775807,
            'neganswer' => -9223372036854775808,
        ], $array);
    }

    public function testParseMustParseFloats()
    {
        $toml = <<<'toml'
        pi = 3.14
        negpi = -3.14
        positive = +1.01
        exponent1 = 5e+22
        exponent2 = 1e6
        exponent3 = -2E-2
        exponent4 = 6.626e-34
        underscore = 6.6_26e-3_4
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'pi' => 3.14,
            'negpi' => -3.14,
            'positive' => 1.01,
            'exponent1' => 4.9999999999999996E+22,
            'exponent2' => 1000000.0,
            'exponent3' => -0.02,
            'exponent4' => 6.6259999999999998E-34,
            'underscore' => 6.6259999999999998E-34,
        ], $array);
    }

    public function testParseMustParseLongFloats()
    {
        $toml = <<<'toml'
        longpi = 3.141592653589793
        neglongpi = -3.141592653589793
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'longpi' => 3.141592653589793,
            'neglongpi' => -3.141592653589793
        ], $array);
    }

    public function testParseMustParseBasicStringsWithASimpleString()
    {
        $array = $this->parser->parse('answer = "You are not drinking enough whisky."');

        $this->assertEquals([
            'answer' => 'You are not drinking enough whisky.',
        ], $array);
    }

    public function testParseMustParseAnEmptyString()
    {
        $array = $this->parser->parse('answer = ""');

        $this->assertEquals([
            'answer' => '',
        ], $array);
    }

    public function testParseMustParseStringsWithEscapedCharacters() : void
    {
        $toml = <<<'toml'
        backspace = "This string has a \b backspace character."
        tab = "This string has a \t tab character."
        newline = "This string has a \n new line character."
        formfeed = "This string has a \f form feed character."
        carriage = "This string has a \r carriage return character."
        quote = "This string has a \" quote character."
        backslash = "This string has a \\ backslash character."
        notunicode1 = "This string does not have a unicode \\u escape."
        notunicode2 = "This string does not have a unicode \\u0075 escape."
toml;

        $array = $this->parser->parse($toml);
        $this->assertEquals([
            'backspace' => "This string has a \b backspace character.",
            'tab' => "This string has a \t tab character.",
            'newline' => "This string has a \n new line character.",
            'formfeed' => "This string has a \f form feed character.",
            'carriage' => "This string has a \r carriage return character.",
            'quote' => 'This string has a " quote character.',
            'backslash' => 'This string has a \\ backslash character.',
            'notunicode1' => 'This string does not have a unicode \\u escape.',
            'notunicode2' => 'This string does not have a unicode \\u0075 escape.',
        ], $array);
    }

    public function testParseMustParseStringsWithPound()
    {
        $toml = <<<'toml'
        pound = "We see no # comments here."
        poundcomment = "But there are # some comments here." # Did I # mess you up?
toml;

        $array = $this->parser->parse($toml);
        $this->assertEquals([
            'pound' => 'We see no # comments here.',
            'poundcomment' => 'But there are # some comments here.'
        ], $array);
    }

    public function testParseMustParseWithUnicodeCharacterEscaped()
    {
        $toml = <<<'toml'
        answer4 = "\u03B4"
        answer8 = "\U000003B4"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'answer4' => json_decode('"\u03B4"'),
            'answer8' => json_decode('"\u0000\u03B4"'),
        ], $array);
    }

    public function testParseMustParseStringWithALiteralUnicodeCharacter()
    {
        $array = $this->parser->parse('answer = "δ"');

        $this->assertEquals([
            'answer' => 'δ',
        ], $array);
    }

    public function testParseMustParseMultilineStrings()
    {
        $toml = <<<'toml'
        multiline_empty_one = """"""
        multiline_empty_two = """
"""
        multiline_empty_three = """\
    """
        multiline_empty_four = """\
           \
           \
           """

        equivalent_one = "The quick brown fox jumps over the lazy dog."
        equivalent_two = """
The quick brown \


          fox jumps over \
            the lazy dog."""

        equivalent_three = """\
               The quick brown \
               fox jumps over \
               the lazy dog.\
               """
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'multiline_empty_one' => '',
            'multiline_empty_two' => '',
            'multiline_empty_three' => '',
            'multiline_empty_four' => '',
            'equivalent_one' => 'The quick brown fox jumps over the lazy dog.',
            'equivalent_two' => 'The quick brown fox jumps over the lazy dog.',
            'equivalent_three' => 'The quick brown fox jumps over the lazy dog.',
        ], $array);
    }

    public function testParseMustParseLiteralStrings()
    {
        $toml = <<<'toml'
        backspace = 'This string has a \b backspace character.'
        tab = 'This string has a \t tab character.'
        newline = 'This string has a \n new line character.'
        formfeed = 'This string has a \f form feed character.'
        carriage = 'This string has a \r carriage return character.'
        slash = 'This string has a \/ slash character.'
        backslash = 'This string has a \\ backslash character.'
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'backspace' => 'This string has a \b backspace character.',
            'tab' => 'This string has a \t tab character.',
            'newline' => 'This string has a \n new line character.',
            'formfeed' => 'This string has a \f form feed character.',
            'carriage' => 'This string has a \r carriage return character.',
            'slash' => 'This string has a \/ slash character.',
            'backslash' => 'This string has a \\\\ backslash character.',
        ], $array);
    }

    public function testParseMustParseMultilineLiteralStrings()
    {
        $toml = <<<'toml'
        oneline = '''This string has a ' quote character.'''
        firstnl = '''
This string has a ' quote character.'''
multiline = '''
This string
has ' a quote character
and more than
one newline
in it.'''
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'oneline' => "This string has a ' quote character.",
            'firstnl' => "This string has a ' quote character.",
            'multiline' => "This string\nhas ' a quote character\nand more than\none newline\nin it.",
        ], $array);
    }

    public function testDatetime()
    {
        $toml = <<<'toml'
        bestdayever = 1987-07-05T17:45:00Z
        bestdayever2 = 1979-05-27T00:32:00-07:00
        bestdayever3 = 1979-05-27T00:32:00.999999-07:00
        bestdayever4 = 1979-05-27T07:32:00
        bestdayever5 = 1979-05-27T00:32:00.999999
        bestdayever6 = 1979-05-27

toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'bestdayever' => new \Datetime('1987-07-05T17:45:00Z'),
            'bestdayever2' => new \Datetime('1979-05-27T00:32:00-07:00'),
            'bestdayever3' => new \Datetime('1979-05-27T00:32:00.999999-07:00'),
            'bestdayever4' => new \Datetime('1979-05-27T07:32:00'),
            'bestdayever5' => new \Datetime('1979-05-27T00:32:00.999999'),
            'bestdayever6' => new \Datetime('1979-05-27'),
        ], $array);
    }

    public function testParseMustParseArraysWithNoSpaces()
    {
        $array = $this->parser->parse('ints = [1,2,3]');

        $this->assertEquals([
            'ints' => [1,2,3],
        ], $array);
    }

    public function testParseMustParseHeterogeneousArrays()
    {
        $array = $this->parser->parse('mixed = [[1, 2], ["a", "b"], [1.1, 2.1]]');

        $this->assertEquals([
            'mixed' => [
                [1,2],
                ['a', 'b'],
                [1.1, 2.1],
            ],
        ], $array);
    }

    public function testParseMustParseArraysNested()
    {
        $array = $this->parser->parse('nest = [["a"], ["b"]]');

        $this->assertEquals([
            'nest' => [
                ['a'],
                ['b']
            ],
        ], $array);
    }

    public function testArrayEmpty()
    {
        $array = $this->parser->parse('thevoid = [[[[[]]]]]');

        $this->assertEquals([
            'thevoid' => [
                [
                    [
                        [
                            [],
                        ],
                    ],
                ],
            ],
        ], $array);
    }

    public function testParseMustParseArrays()
    {
        $toml = <<<'toml'
        ints = [1, 2, 3]
        floats = [1.1, 2.1, 3.1]
        strings = ["a", "b", "c"]
        allStrings = ["all", 'strings', """are the same""", '''type''']
        MultilineBasicString = ["all", """
Roses are red
Violets are blue""",]
        dates = [
          1987-07-05T17:45:00Z,
          1979-05-27T07:32:00Z,
          2006-06-01T11:00:00Z,
        ]
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'ints' => [1, 2, 3],
            'floats' => [1.1, 2.1, 3.1],
            'strings' => ['a', 'b', 'c'],
            'allStrings' => ['all', 'strings', 'are the same', 'type'],
            'MultilineBasicString' => [
                'all',
                "Roses are red\nViolets are blue",
            ],
            'dates' => [
                new \DateTime('1987-07-05T17:45:00Z'),
                new \DateTime('1979-05-27T07:32:00Z'),
                new \DateTime('2006-06-01T11:00:00Z'),
            ],
        ], $array);
    }

    public function testParseMustParseAKeyWithoutNameSpacesAroundEqualSign()
    {
        $array = $this->parser->parse('answer=42');

        $this->assertEquals([
            'answer' => 42,
        ], $array);
    }

    public function testParseMustParseKeyWithSpace()
    {
        $array = $this->parser->parse('"a b" = 1');

        $this->assertNotNull($array);

        $this->assertEquals([
            'a b' => 1,
        ], $array);
    }

    public function testParseMustParseKeyWithSpecialCharacters()
    {
        $array = $this->parser->parse('"~!@$^&*()_+-`1234567890[]|/?><.,;:\'" = 1');

        $this->assertEquals([
            '~!@$^&*()_+-`1234567890[]|/?><.,;:\'' => 1,
        ], $array);
    }

    public function testParseMustParseBareIntegerKeys()
    {
        $toml = <<<'toml'
        [sequence]
        -1 = 'detect person'
        0 = 'say hello'
        1 = 'chat'
        10 = 'say bye'
toml;
        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'sequence' => [
                '-1' => 'detect person',
                 '0' => 'say hello',
                 '1' => 'chat',
                 '10' => 'say bye'
             ]
                 ], $array);
    }

    public function testParseMustParseAnEmptyTable()
    {
        $array = $this->parser->parse('[a]');

        $this->assertEquals([
            'a' => [],
        ], $array);
    }

    public function testParseMustParseATableWithAWhiteSpaceInTheName()
    {
        $array = $this->parser->parse('["valid key"]');

        $this->assertEquals([
            'valid key' => [],
        ], $array);
    }

    public function testParseMustParseATableAQuotedName()
    {
        $toml = <<<'toml'
        [dog."tater.man"]
        type = "pug"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'dog' => [
                'tater.man' => [
                    'type' => 'pug',
                ],
            ],
        ], $array);
    }

    public function testParseMustParseATableWithAPoundInTheName()
    {
        $toml = <<<'toml'
        ["key#group"]
        answer = 42
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'key#group' => [
                'answer' => 42,
            ],
        ], $array);
    }

    public function testParseMustParseATableAndASubtableEmpties()
    {
        $toml = <<<'toml'
        [a]
        [a.b]
toml;
        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'a' => [
                'b' => [],
            ],
        ], $array);
    }

    public function testParseMustParseATableWithImplicitGroups()
    {
        $toml = <<<'toml'
        [a.b.c]
        answer = 42
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'a' => [
                'b' => [
                    'c' => [
                        'answer' => 42,
                    ],
                ],
            ],
        ], $array);
    }

    public function testParseMustParseAImplicitAndExplicitAfterTable()
    {
        $toml = <<<'toml'
        [a.b.c]
        answer = 42

        [a]
        better = 43
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'a' => [
                'better' => 43,
                'b' =>  [
                    'c' => [
                        'answer' => 42,
                    ],
                ],
            ],
        ], $array);
    }

    public function testParseMustParseImplicitAndExplicitTableBefore()
    {
        $toml = <<<'toml'
        [a]
        better = 43

        [a.b.c]
        answer = 42
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'a' => [
                'better' => 43,
                'b' =>  [
                    'c' => [
                        'answer' => 42,
                    ],
                ],
            ],
        ], $array);
    }

    public function testParseMustParseInlineTableEmpty()
    {
        $array = $this->parser->parse('name = {}');

        $this->assertEquals([
            'name' => [],
        ], $array);
    }

    public function testParseMustParseInlineTableOneElement()
    {
        $array = $this->parser->parse('name = { first = "Tom" }');

        $this->assertEquals([
            'name' => [
                'first' => 'Tom'
            ],
        ], $array);
    }

    public function testParseMustParseAnInlineTableDefinedInATable()
    {
        $toml = <<<'toml'
        [tab1]
        key1 = {name='Donald Duck'}
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'tab1' => [
                'key1' => [
                    'name' => 'Donald Duck'
                ],
            ],
        ], $array);
    }

    public function testParseMustParseInlineTableExamples()
    {
        $toml = <<<'toml'
name = { first = "Tom", last = "Preston-Werner" }
point = { x = 1, y = 2 }
strings = { key1 = """
Roses are red
Violets are blue""", key2 = """
The quick brown \


  fox jumps over \
    the lazy dog.""" }
inline = { x = 1, y = { a = 2, "b.deep" = 'my value' } }
another = {number = 1}
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'name' => [
                'first' => 'Tom',
                'last' => 'Preston-Werner',
            ],
            'point' => [
                'x' => 1,
                'y' => 2,
            ],
            'strings' => [
                'key1' => "Roses are red\nViolets are blue",
                'key2' => 'The quick brown fox jumps over the lazy dog.',
            ],
            'inline' => [
                'x' => 1,
                'y' => [
                    'a' => 2,
                    'b.deep' => 'my value',
                ],
            ],
            'another' => [
                'number' => 1,
            ],
        ], $array);
    }

    public function testParseMustParseTableArrayImplicit()
    {
        $toml = <<<'toml'
        [[albums.songs]]
        name = "Glory Days"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'albums' => [
                'songs' => [
                    [
                        'name' => 'Glory Days'
                    ],
                ],
            ],
        ], $array);
    }

    public function testParseMustParseTableArrayOne()
    {
        $toml = <<<'toml'
        [[people]]
        first_name = "Bruce"
        last_name = "Springsteen"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'people' => [
                [
                    'first_name' => 'Bruce',
                    'last_name' => 'Springsteen',
                ],
            ],
        ], $array);
    }

    public function testParseMustParseTableArrayMany()
    {
        $toml = <<<'toml'
        [[people]]
        first_name = "Bruce"
        last_name = "Springsteen"

        [[people]]
        first_name = "Eric"
        last_name = "Clapton"

        [[people]]
        first_name = "Bob"
        last_name = "Seger"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'people' => [
                [
                    'first_name' => 'Bruce',
                    'last_name' => 'Springsteen',
                ],
                [
                    'first_name' => 'Eric',
                    'last_name' => 'Clapton',
                ],
                [
                    'first_name' => 'Bob',
                    'last_name' => 'Seger',
                ],
            ],
        ], $array);
    }

    public function testParseMustParseTableArrayNest()
    {
        $toml = <<<'toml'
        [[albums]]
        name = "Born to Run"

          [[albums.songs]]
          name = "Jungleland"

          [[albums.songs]]
          name = "Meeting Across the River"

        [[albums]]
        name = "Born in the USA"

          [[albums.songs]]
          name = "Glory Days"

          [[albums.songs]]
          name = "Dancing in the Dark"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'albums' => [
                [
                    'name' => 'Born to Run',
                    'songs' => [
                        ['name' => 'Jungleland'],
                        ['name' => 'Meeting Across the River'],
                    ],
                ],
                [
                    'name' => 'Born in the USA',
                    'songs' => [
                        ['name' => 'Glory Days'],
                        ['name' => 'Dancing in the Dark'],
                    ],
                ],
            ],
        ], $array);
    }

    /**
     * @see https://github.com/yosymfony/toml/issues/12
     */
    public function testParseMustParseATableAndArrayOfTables()
    {
        $toml = <<<'toml'
        [fruit]
        name = "apple"

        [[fruit.variety]]
        name = "red delicious"

        [[fruit.variety]]
        name = "granny smith"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'fruit' => [
                'name' => 'apple',
                'variety' => [
                    ['name' => 'red delicious'],
                    ['name' => 'granny smith'],
                ],
            ],
        ], $array);
    }

    /**
     * @see https://github.com/yosymfony/toml/issues/23
     */
    public function testParseMustParseTablesContainedWithinArrayTables()
    {
        $toml = <<<'toml'
        [[tls]]
        entrypoints = ["https"]
        [tls.certificate]
            certFile = "certs/foo.crt"
            keyFile  = "keys/foo.key"

        [[tls]]
        entrypoints = ["https"]
        [tls.certificate]
            certFile = "certs/bar.crt"
            keyFile  = "keys/bar.key"
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'tls' => [
                [
                    'entrypoints' => ['https'],
                    'certificate' => [
                        'certFile' => 'certs/foo.crt',
                        'keyFile' => 'keys/foo.key',
                    ],

                ],
                [
                    'entrypoints' => ['https'],
                    'certificate' => [
                        'certFile' => 'certs/bar.crt',
                        'keyFile' => 'keys/bar.key',
                    ],

                ],
            ],
        ], $array);
    }

    public function testParseMustParseCommentsEverywhere()
    {
        $toml = <<<'toml'
        # Top comment.
          # Top comment.
        # Top comment.

        # [no-extraneous-groups-please]

        [group] # Comment
        answer = 42 # Comment
        # no-extraneous-keys-please = 999
        # Inbetween comment.
        more = [ # Comment
          # What about multiple # comments?
          # Can you handle it?
          #
                  # Evil.
        # Evil.
          42, 42, # Comments within arrays are fun.
          # What about multiple # comments?
          # Can you handle it?
          #
                  # Evil.
        # Evil.
        # ] Did I fool you?
        ] # Hopefully not.
toml;

        $array = $this->parser->parse($toml);

        $this->assertNotNull($array);

        $this->assertArrayHasKey('answer', $array['group']);
        $this->assertArrayHasKey('more', $array['group']);

        $this->assertEquals($array['group']['answer'], 42);
        $this->assertEquals($array['group']['more'][0], 42);
        $this->assertEquals($array['group']['more'][1], 42);
    }

    public function testParseMustParseASimpleExample()
    {
        $toml = <<<'toml'
        best-day-ever = 1987-07-05T17:45:00Z
        emptyName = ""

        [numtheory]
        boring = false
        perfection = [6, 28, 496]
toml;

        $array = $this->parser->parse($toml);

        $this->assertEquals([
            'best-day-ever' => new \DateTime('1987-07-05T17:45:00Z'),
            'emptyName' => '',
            'numtheory' => [
                'boring' => false,
                'perfection' => [6, 28, 496],
            ],
        ], $array);
    }
}
