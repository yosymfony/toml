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

class TomlBuilderInvalidTest extends TestCase
{
    private $builder;

    public function setUp() : void
    {
        $this->builder = new TomlBuilder();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null.
     */
    public function testAddValueMustFailWhenEmptyKey()
    {
        $this->builder->addValue('', 'value');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null.
     */
    public function testAddTableMustFailWhenEmptyKey()
    {
        $this->builder->addTable('');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null.
     */
    public function testAddArrayOfTableMustFailWhenEmptyKey()
    {
        $this->builder->addArrayOfTable('');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null.
     */
    public function testAddValueMustFailWhenKeyWithJustWhiteSpaces()
    {
        $whiteSpaceKey = ' ';

        $this->builder->addValue($whiteSpaceKey, 'value');
    }

    /**
    * @expectedException Yosymfony\Toml\Exception\DumpException
    * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null.
    */
    public function testAddTableMustFailWhenKeyWithJustWhiteSpaces()
    {
        $whiteSpaceKey = ' ';

        $this->builder->addTable($whiteSpaceKey);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null.
     */
    public function testAddArrayOfTableMustFailWhenKeyWithJustWhiteSpaces()
    {
        $whiteSpaceKey = ' ';

        $this->builder->addArrayOfTable($whiteSpaceKey);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage Data types cannot be mixed in an array. Key: "strings-and-ints".
     */
    public function testAddValueMustFailWhenMixedTypes()
    {
        $this->builder->addValue('strings-and-ints', ["uno", 1]);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage The table key "a" has already been defined previously.
     */
    public function testAddTableMustFailWhenDuplicateTables()
    {
        $this->builder->addTable('a')
            ->addTable('a');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage The table key "fruit.type" has already been defined previously.
     */
    public function testAddTableMustFailWhenDuplicateKeyTable()
    {
        $this->builder->addTable('fruit')
            ->addValue('type', 'apple')
            ->addTable('fruit.type');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage The key "dupe" has already been defined previously.
     */
    public function testAddValueMustFailWhenDuplicateKeys()
    {
        $this->builder->addValue('dupe', false)
            ->addValue('dupe', true);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage A key, table name or array of table name cannot be empty or null. Table: "naughty..naughty".
     */
    public function testEmptyImplicitKeyGroup()
    {
        $this->builder->addTable('naughty..naughty');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage Data type not supporter at the key: "theNull".
     */
    public function testAddValueMustFailWithNullValue()
    {
        $this->builder->addValue('theNull', null);
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage Data type not supporter at the key: "theNewClass".
     */
    public function testAddValueMustFailWithUnsuportedValueType()
    {
        $this->builder->addValue('theNewClass', new class {
        });
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage The key "albums" has been defined as a implicit table from a previous array of tables.
     */
    public function testaddArrayOfTableMustFailWhenThereIsATableArrayImplicit()
    {
        $this->builder->addArrayOfTable('albums.songs')
                ->addValue('name', 'Glory Days')
            ->addArrayOfTable('albums')
                ->addValue('name', 'Born in the USA');
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     * @expectedExceptionMessage Only unquoted keys are allowed in this implementation. Key: "valid key".
     */
    public function testAddTableMustFailWithNoUnquotedKeys()
    {
        $this->builder->addTable('valid key');
    }
}
