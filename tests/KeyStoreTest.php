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
use Yosymfony\Toml\KeyStore;

class KeyStoreTest extends TestCase
{
    private $keyStore;

    public function setUp()
    {
        $this->keyStore = new KeyStore();
    }

    public function testIsValidKeyMustReturnTrueWhenTheKeyDoesNotExist()
    {
        $this->assertTrue($this->keyStore->isValidKey('a'));
    }

    public function testIsValidKeyMustReturnFalseWhenDuplicateKeys()
    {
        $this->keyStore->addKey('a');

        $this->assertFalse($this->keyStore->isValidKey('a'));
    }

    public function testIsValidTableKeyMustReturnTrueWhenTheTableKeyDoesNotExist()
    {
        $this->assertTrue($this->keyStore->isValidTableKey('a'));
    }

    public function testIsValidTableKeyMustReturnTrueWhenSuperTableIsNotDireclyDefined()
    {
        $this->keyStore->addTableKey('a.b');
        $this->keyStore->addKey('c');

        $this->assertTrue($this->keyStore->isValidTableKey('a'));
    }

    public function testIsValidTableKeyMustReturnFalseWhenDuplicateTableKeys()
    {
        $this->keyStore->addTableKey('a');

        $this->assertFalse($this->keyStore->isValidTableKey('a'));
    }

    public function testIsValidTableKeyMustReturnFalseWhenThereIsAKeyWithTheSameName()
    {
        $this->keyStore->addTableKey('a');
        $this->keyStore->addKey('b');

        $this->assertFalse($this->keyStore->isValidTableKey('a.b'));
    }

    public function testIsValidArrayTableKeyMustReturnFalseWhenThereIsAPreviousKeyWithTheSameName()
    {
        $this->keyStore->addKey('a');

        $this->assertFalse($this->keyStore->isValidArrayTableKey('a'));
    }

    public function testIsValidArrayTableKeyMustReturnFalseWhenThereIsAPreviousTableWithTheSameName()
    {
        $this->keyStore->addTableKey('a');

        $this->assertFalse($this->keyStore->isValidArrayTableKey('a'));
    }

    public function testIsValidTableKeyMustReturnFalseWhenAttemptingToDefineATableKeyEqualToPreviousDefinedArrayTable()
    {
        $this->keyStore->addArrayTableKey('a');
        $this->keyStore->addArrayTableKey('a.b');

        $this->assertFalse($this->keyStore->isValidTableKey('a.b'));
    }

    public function testIsValidTableKeyMustReturnTrueWithTablesInsideArrayOfTables()
    {
        $this->keyStore->addArrayTableKey('a');
        $this->keyStore->addTableKey('a.b');
        $this->keyStore->addArrayTableKey('a');

        $this->assertTrue($this->keyStore->isValidTableKey('a.b'));
    }
}
