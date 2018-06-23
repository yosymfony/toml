<?php

/*
 * This file is part of the Yosymfony\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yosymfony\Toml;

/**
 * Internal class for managing keys (key-values, tables and array of tables)
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class KeyStore
{
    private $keys = [];
    private $tables = [];
    private $arrayOfTables = [];
    private $implicitArrayOfTables = [];
    private $currentTable = '';
    private $currentArrayOfTable = '';

    public function addKey(string $name) : void
    {
        if (!$this->isValidKey($name)) {
            throw new \LogicException("The key \"{$name}\" is not valid.");
        }

        $this->keys[] = $this->composeKeyWithCurrentPrefix($name);
    }

    public function isValidKey(string $name) : bool
    {
        $composedKey = $this->composeKeyWithCurrentPrefix($name);

        if (in_array($composedKey, $this->keys, true) === true) {
            return false;
        }

        return true;
    }

    public function addTableKey(string $name) : void
    {
        if (!$this->isValidTableKey($name)) {
            throw new \LogicException("The table key \"{$name}\" is not valid.");
        }

        $this->currentTable = '';
        $this->currentArrayOfTable = $this->getArrayOfTableKeyFromTableKey($name);
        $this->addkey($name);
        $this->currentTable = $name;
        $this->tables[] = $name;
    }

    public function isValidTableKey($name) : bool
    {
        $currentTable = $this->currentTable;
        $currentArrayOfTable = $this->currentArrayOfTable;

        $this->currentTable = '';
        $this->currentArrayOfTable = $this->getArrayOfTableKeyFromTableKey($name);

        if ($this->currentArrayOfTable == $name) {
            return false;
        }

        $isValid = $this->isValidKey($name);
        $this->currentTable = $currentTable;
        $this->currentArrayOfTable = $currentArrayOfTable;

        return $isValid;
    }

    public function isValidInlineTable(string $name): bool
    {
        return $this->isValidTableKey($name);
    }

    public function addInlineTableKey(string $name) : void
    {
        $this->addTableKey($name);
    }

    public function addArrayTableKey(string $name) : void
    {
        if (!$this->isValidArrayTableKey($name)) {
            throw new \LogicException("The array table key \"{$name}\" is not valid.");
        }

        $this->currentTable = '';
        $this->currentArrayOfTable = '';

        if (isset($this->arrayOfTables[$name]) === false) {
            $this->addkey($name);
            $this->arrayOfTables[$name] = 0;
        } else {
            $this->arrayOfTables[$name]++;
        }

        $this->currentArrayOfTable = $name;
        $this->processImplicitArrayTableNameIfNeeded($name);
    }

    public function isValidArrayTableKey(string $name) : bool
    {
        $isInArrayOfTables = isset($this->arrayOfTables[$name]);
        $isInKeys = in_array($name, $this->keys, true);

        if ((!$isInArrayOfTables && !$isInKeys) || ($isInArrayOfTables && $isInKeys)) {
            return true;
        }
        
        return false;
    }

    public function isRegisteredAsTableKey(string $name) : bool
    {
        return in_array($name, $this->tables);
    }

    public function isRegisteredAsArrayTableKey(string $name) : bool
    {
        return isset($this->arrayOfTables[$name]);
    }

    public function isTableImplicitFromArryTable(string $name) : bool
    {
        $isInImplicitArrayOfTables = in_array($name, $this->implicitArrayOfTables);
        $isInArrayOfTables = isset($this->arrayOfTables[$name]);

        if ($isInImplicitArrayOfTables && !$isInArrayOfTables) {
            return true;
        }

        return false;
    }
    
    private function composeKeyWithCurrentPrefix(string $name) : string
    {
        $currentArrayOfTableIndex = '';

        if ($this->currentArrayOfTable != '') {
            $currentArrayOfTableIndex = (string) $this->arrayOfTables[$this->currentArrayOfTable];
        }

        return \trim("{$this->currentArrayOfTable}{$currentArrayOfTableIndex}.{$this->currentTable}.{$name}", '.');
    }

    private function getArrayOfTableKeyFromTableKey(string $name) : string
    {
        if (isset($this->arrayOfTables[$name])) {
            return $name;
        }

        $keyParts = explode('.', $name);

        if (count($keyParts) === 1) {
            return '';
        }

        array_pop($keyParts);

        while (count($keyParts) > 0) {
            $candidateKey = implode('.', $keyParts);

            if (isset($this->arrayOfTables[$candidateKey])) {
                return $candidateKey;
            }

            array_pop($keyParts);
        }

        return '';
    }

    private function processImplicitArrayTableNameIfNeeded(string $name) : void
    {
        $nameParts = explode('.', $name);

        if (count($nameParts) < 2) {
            return;
        }

        array_pop($nameParts);

        while (count($nameParts) != 0) {
            $this->implicitArrayOfTables[] = implode('.', $nameParts);
            array_pop($nameParts);
        }
    }
}
