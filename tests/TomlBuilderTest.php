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
use Yosymfony\Toml\TomlBuilder;
use Yosymfony\Toml\Toml;

class TomlBuilderTest extends TestCase
{
    public function testExample()
    {
        $tb = new TomlBuilder();

        $result = $tb->addComment('Toml file')
            ->addTable('data.string')
                ->addValue('name', 'Toml', 'This is your name')
                ->addValue('newline', "This string has a \n new line character.")
                ->addValue('winPath', 'C:\\Users\\nodejs\\templates')
                ->addValue('unicode', 'unicode character: '.json_decode('"\u03B4"'))
            ->addTable('data.bool')
                ->addValue('t', true)
                ->addValue('f', false)
            ->addTable('data.integer')
                ->addValue('positive', 25, 'Comment inline.')
                ->addValue('negative', -25)
            ->addTable('data.float')
                ->addValue('positive', 25.25)
                ->addValue('negative', -25.25)
            ->addTable('data.datetime')
                ->addValue('datetime', new \Datetime())
            ->addComment('Related to arrays')
            ->addTable('data.array')
                ->addValue('simple', array(1, 2, 3))
                ->addValue('multiple', array(array(1, 2), array('abc', 'def'), array(1.1, 1.2), array(true, false), array(new \Datetime())))
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testArrayEmpty()
    {
        $tb = new TomlBuilder();

        $result = $tb->addComment('Toml file')
            ->addValue('thevoid', array())
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testImplicitAndExplicitAfter()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('a.b.c')
                ->addValue('answer', 42)
            ->addTable('a')
                ->addValue('better', 43)
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testImplicitAndExplicitBefore()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('a')
                ->addValue('better', 43)
            ->addTable('a.b.c')
                ->addValue('answer', 42)
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testTableEmpty()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('a')
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testTableSubEmpty()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('a')
            ->addTable('a.b')
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testKeyWhitespace()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('valid key', 2)
            ->getTomlString();
            
        $this->assertNotNull(Toml::Parse($result));
    }

    public function testStringEscapesDoubleQuote()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('backspace', "This string has a \b backspace character.")
            ->addValue('tab', "This string has a \t tab character.")
            ->addValue('newline', "This string has a \n new line character.")
            ->addValue('formfeed', "This string has a \f form feed character.")
            ->addValue('carriage', "This string has a \r carriage return character.")
            ->addValue('quote', 'This string has a " quote character.')
            ->addValue('slash', "This string has a / slash character.")
            ->addValue('backslash', 'This string has a \\ backslash character.')
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testKeyLiteralString()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('regex', "@<\i\c*\s*>")
            ->getTomlString();

        $array = Toml::Parse($result);

        $this->assertNotNull($array);

        $this->assertEquals('<\i\c*\s*>', $array['regex']);
    }

    public function testKeyLiteralStringEscapingAt()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('regex', "@@<\i\c*\s*>")
            ->getTomlString();

        $array = Toml::Parse($result);

        $this->assertNotNull($array);

        $this->assertEquals('@<\i\c*\s*>', $array['regex']);
    }

    public function testKeySpecialChars()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('~!@$^&*()_+-`1234567890[]|/?><.,;:\'', 1)
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testStringEscapesSingleQuote()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('backspace', 'This string has a \b backspace character.')
            ->addValue('tab', 'This string has a \t tab character.')
            ->addValue('newline', 'This string has a \n new line character.')
            ->addValue('formfeed', 'This string has a \f form feed character.')
            ->addValue('carriage', 'This string has a \r carriage return character.')
            ->addValue('quote', 'This string has a \" quote character.')
            ->addValue('slash', 'This string has a \/ slash character.')
            ->addValue('backslash', 'This string has a \\ backslash character.')
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }

    public function testArrayOfTables()
    {
        $tb = new TomlBuilder();

        $result = $tb->addArrayOfTable('fruit')
                ->addValue('name', 'apple')
            ->addArrayOfTable('fruit.variety')
                ->addValue('name', 'red delicious')
            ->addArrayOfTable('fruit.variety')
                ->addValue('name', 'granny smith')
            ->addArrayOfTable('fruit')
                ->addValue('name', 'banana')
            ->addArrayOfTable('fruit.variety')
                ->addValue('name', 'plantain')
            ->getTomlString();

        $this->assertNotNull(Toml::Parse($result));
    }
}
