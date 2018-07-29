<?php

/*
 * This file is part of the Yosymfony Toml.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yosymfony\Toml\tests;

use PHPUnit\Framework\TestCase;
use Yosymfony\Toml\TomlArray;

class TomlArrayTest extends TestCase
{
    /** @var TomlArray */
    private $tomlArray;

    public function setUp()
    {
        $this->tomlArray = new TomlArray();
    }

    public function tearDown()
    {
        $this->tomlArray = null;
    }

    public function testGetArrayMustReturnTheKeyValueAdded() : void
    {
        $this->tomlArray->addKeyValue('company', 'acme');

        $this->assertEquals([
            'company' => 'acme'
        ], $this->tomlArray->getArray());
    }

    public function testGetArrayMustReturnTheTableWithTheKeyValue() : void
    {
        $this->tomlArray->addTableKey('companyData');
        $this->tomlArray->addKeyValue('company', 'acme');

        $this->assertEquals([
            'companyData' => [
                'company' => 'acme'
            ],
        ], $this->tomlArray->getArray());
    }

    public function testGetArrayMustReturnAnArrayOfTables() : void
    {
        $this->tomlArray->addArrayTableKey('companyData');
        $this->tomlArray->addKeyValue('company', 'acme1');
        $this->tomlArray->addArrayTableKey('companyData');
        $this->tomlArray->addKeyValue('company', 'acme2');

        $this->assertEquals([
            'companyData' => [
                [
                    'company' => 'acme1'
                ],
                [
                    'company' => 'acme2'
                ],
            ]
        ], $this->tomlArray->getArray());
    }
}
