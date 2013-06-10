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
        
        $result = $tb->addValue('arrays-and-ints', array(1, array("Arrays are not integers.")))->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesIntsAndFloats()
    {
         $tb = new TomlBuilder();
        
        $result = $tb->addValue('arrays-and-ints', array(1, 1.0))->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testArrayMixedTypesStringsAndInts()
    {
         $tb = new TomlBuilder();
        
        $result = $tb->addValue('arrays-and-ints', array("hi", 42))->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeygroup()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('a')->
            addGroup('a')->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeyKeygroup()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('fruit')->
            addValue('type', 'apple')->
            addGroup('fruit.type')->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testDuplicateKeys()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue('dupe', false)->
            addValue('dupe', true)->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testEmptyKeygroup()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('')->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testEmptyImplicitKeygroup()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup('naughty..naughty')->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNullKeygroup()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup(null)->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNonStringKeygroup()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addGroup(2)->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNonStringKey()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue(2, '2')->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNullKey()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue(null, 'null')->
            getTomlString();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\DumpException
     */
    public function testNullValue()
    {
        $tb = new TomlBuilder();
        
        $result = $tb->addValue('theNull', null)->
            getTomlString();
    }
}