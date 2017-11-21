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

use Yosymfony\Toml\Exception\DumpException;

/**
 * Create inline TOML strings.
 *
 * @author Victor Puertas <vpgugr@gmail.com>
 *
 * Usage:
 * <code>
 * $tomlString = new TomlBuilder()
 *  ->addTable('server.mail')
 *  ->addValue('ip', '192.168.0.1', 'Internal IP')
 *  ->addValue('port', 25)
 *  ->getTomlString();
 * </code>
 */
class TomlBuilder
{
    protected $prefix = '';
    protected $output = '';
    protected $currentLine = 0;
    protected $keyList = array();
    protected $keyListArryOfTables = array();
    protected $keyListInvalidArrayOfTables = array();
    protected $currentTable = '';
    protected $currentArrayOfTables = '';
    protected $currentKey = null;

    /**
     * Constructor.
     *
     * @param int $indent The amount of spaces to use for indentation of nested nodes
     */
    public function __constructor(int $indent = 4)
    {
        $this->prefix = $indent ? str_repeat(' ', $indent) : '';
    }

    /**
     * Adds a key value pair
     *
     * @param string $key The key name
     * @param string|int|bool|float|array|Datetime  $val The value
     * @param string $comment Comment (optional argument).
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addValue(string $key, $val, string $comment = null) : TomlBuilder
    {
        if (strpos($key, '#')) {
            throw new DumpException(sprintf('Character "#" is not valid for the key.'));
        }

        if (preg_match('/^([-A-Z_a-z0-9]+)$/', $key) === 0) {
            $key = '"'.$key.'"';
        }

        $keyPart = $this->getKeyPart($key);

        $this->append($keyPart);

        $data = $this->dumpValue($val);

        if (is_string($comment)) {
            $data .= ' '.$this->dumpComment($comment);
        }

        $this->addKey($key);
        $this->append($data, true);

        return $this;
    }

    /**
     * Adds a table.
     *
     * @param string $key Tablename. Dot character have a special meant
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addTable(string $key) : TomlBuilder
    {
        $addPreNewline = $this->currentLine > 0 ? true : false;

        if (false !== strpos($key, ' ')) {
            $key = '"'.$key.'"';
        }

        $keyParts = explode('.', $key);
        $val = '['.$key.']';

        foreach ($keyParts as $keyPart) {
            if (strlen($keyPart) == 0) {
                throw new DumpException(sprintf('The key must not be empty at table: %s', $key));
            }
        }

        $this->addKeyTable($key, $keyParts);
        $this->append($val, true, false, $addPreNewline);

        return $this;
    }

    /**
     * Adds an array of tables
     *
     * @param string $key The name of the array of tables
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addArrayTables(string $key) : TomlBuilder
    {
        $addPreNewline = $this->currentLine > 0 ? true : false;

        $keyParts = explode('.', $key);
        $val = '[['.$key.']]';

        foreach ($keyParts as $keyPart) {
            if (strlen($keyPart) == 0) {
                throw new DumpException(sprintf('The key must not be empty at array of tables: %s', $key));
            }
        }

        $this->addKeyArrayOfTables($key, $keyParts);
        $this->append($val, true, false, $addPreNewline);

        return $this;
    }

    /**
     * Adds a comment line.
     *
     * @param string $comment The comment
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addComment(string $comment) : TomlBuilder
    {
        $this->append($this->dumpComment($comment), true);

        return $this;
    }

    /**
     * Gets the TOML string
     *
     * @return string
     */
    public function getTomlString() : string
    {
        return $this->output;
    }

    private function dumpValue($val) : string
    {
        switch (true) {
            case is_string($val):
                return $this->dumpString($val);
            case is_array($val):
                return $this->dumpArray($val);
            case is_int($val):
                return $this->dumpInteger($val);
            case is_float($val):
                return $this->dumpFloat($val);
            case is_bool($val):
                return $this->dumpBool($val);
            case $val instanceof \Datetime:
                return $this->dumpDatetime($val);
            default:
                throw new DumpException(sprintf('Data type not supporter at the key "%s"', $this->currentKey));
        }
    }

    private function dumpString(string $val) : string
    {
        if (0 === strpos($val, '@')) {
            return "'".preg_replace('/@/', '', $val, 1)."'";
        }

        $normalized = $this->normalizeString($val);

        if (false === $this->isStringValid($normalized)) {
            throw new DumpException(sprintf('The string have a invalid cacharters at the key "%s"', $this->currentKey));
        }

        return '"'.$normalized.'"';
    }

    private function dumpBool(bool $val) : string
    {
        return $val ? 'true' : 'false';
    }

    private function dumpArray(array $val) : string
    {
        $result = '';
        $first = true;
        $dataType = null;
        $lastType = null;

        foreach ($val as $item) {
            $lastType = gettype($item);
            $dataType = $dataType == null ? $lastType : $dataType;

            if ($lastType != $dataType) {
                throw new DumpException(sprintf('Data types cannot be mixed in an array. Key "%s"', $this->currentKey));
            }

            $result .= $first ? $this->dumpValue($item) : ', '.$this->dumpValue($item);
            $first = false;
        }

        return '['.$result.']';
    }

    private function dumpComment(string $val) : string
    {
        return '#'.$val;
    }

    private function dumpDatetime(\Datetime $val) : string
    {
        return $val->format('Y-m-d\TH:i:s\Z'); // ZULU form
    }

    private function dumpInteger(int $val) : string
    {
        return strval($val);
    }

    private function dumpFloat(float $val) : string
    {
        return strval($val);
    }

    private function getKeyPart(string $key) : string
    {
        if (strlen($key = trim($key)) > 0) {
            return $key.' = ';
        } else {
            throw new DumpException('The key must be a string and must not be empty');
        }
    }

    private function append(string $val, bool $addPostNewline = false, bool $addIndentation = false, bool $addPreNewline = false) : void
    {
        if ($addPreNewline) {
            $this->output .= "\n";
            ++$this->currentLine;
        }

        if ($addIndentation) {
            $val = $this->prefix.$val;
        }

        $this->output .= $val;

        if ($addPostNewline) {
            $this->output .= "\n";
            ++$this->currentLine;
        }
    }

    private function addKey(string $key) : void
    {
        $this->currentKey = $key;
        $absKey = $this->getAbsoluteKey($key, $this->currentTable, $this->currentArrayOfTables);
        $this->addKeyToKeyList($absKey);
    }

    private function addKeyTable(string $key) : void
    {
        if (in_array($key, $this->keyListArryOfTables)) {
            throw new DumpException(
                sprintf('The table %s has already been defined as previous array of tables', $key)
            );
        }

        $this->currentTable = $key;
        $absKey = $this->getAbsoluteKey($key, '', $this->currentArrayOfTables);
        $this->addKeyToKeyList($absKey);
    }

    private function addKeyArrayOfTables(string $key, array $keyParts) : void
    {
        if ($this->isTableImplicit($keyParts)) {
            $this->addInvalidArrayOfTablesKey($keyParts);
            $this->addKeyTable($key, $keyParts);

            return;
        }

        if (in_array($key, $this->keyListInvalidArrayOfTables)) {
            throw new DumpException(
                sprintf('The array of tables %s has already been defined as previous table', $key)
            );
        }

        if (false == isset($this->keyListArryOfTables[$key])) {
            $this->keyListArryOfTables[$key] = 0;
            $this->addKeyToKeyList($key);
        } else {
            $counter = $this->keyListArryOfTables[$key] + 1;
            $this->keyListArryOfTables[$key] = $counter + 1;
        }

        $keyPath = $this->getArrayTablesKeyPath($keyParts);
        $this->addKeyToKeyList($keyPath);
        $this->currentArrayOfTables = $keyPath;
    }

    private function getArrayTablesKeyPath(array $keyParts) : string
    {
        $path = $simplePath = '';

        foreach ($keyParts as $keyPart) {
            $simplePath .= $keyPart;
            $counter = $this->keyListArryOfTables[$simplePath];
            $path .= $keyPart.$counter.'.';
            $simplePath .= '.';
        }

        return rtrim($path, '.');
    }

    private function addKeyToKeyList(string $key) : void
    {
        if (in_array($key, $this->keyList)) {
            throw new DumpException(sprintf('Syntax error: the key "%s" has already been defined', $key));
        }

        $this->keyList[] = $key;
    }

    private function getAbsoluteKey(string $key, string $currentKeyTable, string $currentKeyArrayOfTables) : string
    {
        $prefix = strlen($currentKeyArrayOfTables) > 0 ? $currentKeyArrayOfTables.'.' : '';
        $prefix .= strlen($currentKeyTable) > 0 ? $currentKeyTable.'.' : '';

        return $prefix.$key;
    }

    private function isTableImplicit(array $keyParts) : bool
    {
        if (count($keyParts) > 1) {
            array_pop($keyParts);

            $key = implode('.', $keyParts);

            if (false == in_array($key, $this->keyListArryOfTables)) {
                return true;
            }
        }

        return false;
    }

    private function addInvalidArrayOfTablesKey(array $keyParts) : void
    {
        foreach ($keyParts as $keyPart) {
            $this->keyListInvalidArrayOfTables[] = implode('.', $keyParts);
            array_pop($keyParts);
        }
    }

    private function isStringValid(string $val) : bool
    {
        $allowed = array(
            '\\\\',
            '\\b',
            '\\t',
            '\\n',
            '\\f',
            '\\r',
            '\\"',
        );

        $noSpecialCharacter = str_replace($allowed, '', $val);
        $noSpecialCharacter = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '', $noSpecialCharacter);
        $noSpecialCharacter = preg_replace('/\\\\u([0-9a-fA-F]{8})/', '', $noSpecialCharacter);

        $pos = strpos($noSpecialCharacter, '\\');

        if (false !== $pos) {
            return false;
        }

        return true;
    }

    private function normalizeString(string $val) : string
    {
        $allowed = array(
            '\\' => '\\\\',
            "\b" => '\\b',
            "\t" => '\\t',
            "\n" => '\\n',
            "\f" => '\\f',
            "\r" => '\\r',
            '"' => '\\"',
        );

        $normalized = str_replace(array_keys($allowed), $allowed, $val);

        return $normalized;
    }
}
