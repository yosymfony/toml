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
    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesArraysAndInts()
    {
        $tb = new TomlBuilder();

        $tb->addValue('arrays-and-ints', array(1, array('Arrays are not integers.')))
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesIntsAndFloats()
    {
        $tb = new TomlBuilder();
        $tb->addValue('arrays-and-ints', array(1, 1.0))
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesStringsAndInts()
    {
        $tb = new TomlBuilder();
        $tb->addValue('arrays-and-ints', array('hi', 42))
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateTable()
    {
        $tb = new TomlBuilder();
        $tb->addTable('a')
            ->addTable('a')
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeyTable()
    {
        $tb = new TomlBuilder();
        $tb->addTable('fruit')
            ->addValue('type', 'apple')
            ->addTable('fruit.type')
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeys()
    {
        $tb = new TomlBuilder();
        $tb->addValue('dupe', false)
            ->addValue('dupe', true)
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testEmptyTable()
    {
        $tb = new TomlBuilder();
        $tb->addTable('')
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testEmptyImplicitKeygroup()
    {
        $tb = new TomlBuilder();
        $tb->addTable('naughty..naughty')
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testInvalidKey()
    {
        $tb = new TomlBuilder();
        $tb->addValue('value#1', '2')
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testNullValue()
    {
        $tb = new TomlBuilder();
        $tb->addValue('theNull', null)
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testTableArrayImplicit()
    {
        $tb = new TomlBuilder();
        $tb->addArrayTables('albums.songs')
                ->addValue('name', 'Glory Days')
            ->addArrayTables('albums')
                ->addValue('name', 'Born in the USA')
            ->getTomlString();
    }

    /**
     * @expectedException Yosymfony\Toml\Exception\DumpException
     */
    public function testTableArrayWithSomeNameOfTable()
    {
        $tb = new TomlBuilder();
        $tb->addArrayTables('fruit')
                ->addValue('name', 'apple')
            ->addArrayTables('fruit.variety')
                ->addValue('name', 'red delicious')
            ->addTable('fruit.variety')
                ->addValue('name', 'granny smith')
            ->getTomlString();
    }
}
