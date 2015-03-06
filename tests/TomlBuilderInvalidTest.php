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

use Yosymfony\Toml\TomlBuilder;
use Yosymfony\Toml\Toml;
use Yosymfony\Toml\Exception\DumpException;

class TomlBuilderInvalidTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesArraysAndInts()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('arrays-and-ints', array(1, array("Arrays are not integers.")))
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesIntsAndFloats()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('arrays-and-ints', array(1, 1.0))
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesStringsAndInts()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('arrays-and-ints', array("hi", 42))
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateTable()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('a')
            ->addTable('a')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeyTable()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('fruit')
            ->addValue('type', 'apple')
            ->addTable('fruit.type')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeys()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('dupe', false)
            ->addValue('dupe', true)
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testEmptyTable()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testEmptyImplicitKeygroup()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable('naughty..naughty')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNullTable()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable(null)
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNonStringTable()
    {
        $tb = new TomlBuilder();

        $result = $tb->addTable(2)
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNonStringKey()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue(2, '2')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testInvalidKey()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('value#1', '2')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNullKey()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue(null, 'null')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNullValue()
    {
        $tb = new TomlBuilder();

        $result = $tb->addValue('theNull', null)
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testTableArrayImplicit()
    {
        $tb = new TomlBuilder();

        $result = $tb
            ->addArrayTables('albums.songs')
                ->addValue('name', 'Glory Days')
            ->addArrayTables('albums')
                ->addValue('name', 'Born in the USA')
            ->getTomlString();
    }

    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testTableArrayWithSomeNameOfTable()
    {
        $tb = new TomlBuilder();

        $result = $tb
            ->addArrayTables('fruit')
                ->addValue('name', 'apple')
            ->addArrayTables('fruit.variety')
                ->addValue('name', 'red delicious')
            ->addTable('fruit.variety')
                ->addValue('name', 'granny smith')
            ->getTomlString();
    }
}
