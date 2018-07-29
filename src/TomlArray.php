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
 * Internal class for managing a Toml array
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class TomlArray
{
    private const DOT_ESCAPED = '%*%';

    private $result = [];
    private $currentPointer;
    private $originInlineTableCurrentPointer;
    private $ArrayTableKeys = [];
    private $inlineTablePointers = [];

    public function __construct()
    {
        $this->resetCurrentPointer();
    }

    public function addKeyValue(string $name, $value) : Void
    {
        $this->currentPointer[$name] = $value;
    }

    public function addTableKey(string $name) : Void
    {
        $this->resetCurrentPointer();
        $this->goToKey($name);
    }

    public function beginInlineTableKey(string $name) : Void
    {
        $this->inlineTablePointers[] = &$this->currentPointer;
        $this->goToKey($name);
    }

    public function endCurrentInlineTableKey() : Void
    {
        $indexLastElement = $this->getKeyLastElementOfArray($this->inlineTablePointers);
        $this->currentPointer = &$this->inlineTablePointers[$indexLastElement];
        unset($this->inlineTablePointers[$indexLastElement]);
    }

    public function addArrayTableKey(string $name) : Void
    {
        $this->resetCurrentPointer();
        $this->goToKey($name);
        $this->currentPointer[] = [];
        $this->setCurrentPointerToLastElement();

        if (!$this->existsInArrayTableKey($name)) {
            $this->ArrayTableKeys[] = $name;
        }
    }

    public function escapeKey(string $name) : string
    {
        return \str_replace('.', self::DOT_ESCAPED, $name);
    }
    
    public function getArray() : array
    {
        return $this->result;
    }

    private function unescapeKey(string $name) : string
    {
        return \str_replace(self::DOT_ESCAPED, '.', $name);
    }

    private function goToKey(string $name) : Void
    {
        $keyParts = explode('.', $name);
        $accumulatedKey = '';
        $countParts = count($keyParts);

        foreach ($keyParts as $index => $keyPart) {
            $keyPart = $this->unescapeKey($keyPart);
            $isLastKeyPart = $index == $countParts -1;
            $accumulatedKey .= $accumulatedKey == '' ? $keyPart : '.'.$keyPart;

            if (\array_key_exists($keyPart, $this->currentPointer) === false) {
                $this->currentPointer[$keyPart] = [];
            }

            $this->currentPointer = &$this->currentPointer[$keyPart];

            if ($this->existsInArrayTableKey($accumulatedKey) && !$isLastKeyPart) {
                $this->setCurrentPointerToLastElement();
                continue;
            }
        }
    }

    private function setCurrentPointerToLastElement() : void
    {
        $indexLastElement = $this->getKeyLastElementOfArray($this->currentPointer);
        $this->currentPointer = &$this->currentPointer[$indexLastElement];
    }

    private function resetCurrentPointer() : Void
    {
        $this->currentPointer = &$this->result;
    }

    private function existsInArrayTableKey($name) : bool
    {
        return \in_array($this->unescapeKey($name), $this->ArrayTableKeys);
    }

    private function getKeyLastElementOfArray(array &$arr)
    {
        end($arr);

        return key($arr);
    }
}
