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
     * @param int $indent The amount of spaces to use for indentation of nested nodes.
     */
    public function __constructor($indent = 4)
    {
        $this->prefix = $indent ? str_repeat(' ', $indent) : '';
    }

    /**
     * Add a key-value.
     *
     * @param string $key
     * @param mixed  $val
     * @param string $comment
     *
     * @return TomlBuilder
     */
    public function addValue($key, $val, $comment = null)
    {
        if (false === is_string($key)) {
            throw new DumpException(sprintf('The key must be a string.'));
        }

        if (strpos($key, '#')) {
            throw new DumpException(sprintf('Character "#" is not valid for the key.'));
        }

        if (false !== strpos($key, ' ')) {
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
     * Alias for addTable method.
     *
     * @deprecated Since version 0.2
     *
     * @param string $keygroup
     *
     * @return TomlBuilder
     */
    public function addGroup($keygroup)
    {
        return $this->addTable($keygroup);
    }

    /**
     * Add a table.
     *
     * @param string $key Tablename. Dot character have a special meant.
     *
     * @return TomlBuilder
     */
    public function addTable($key)
    {
        if (false === is_string($key)) {
            throw new DumpException(sprintf('The key of a table must be a string'));
        }

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
     * @param string $key
     *
     * @return TomlBuilder
     */
    public function addArrayTables($key)
    {
        if (false === is_string($key)) {
            throw new DumpException(sprintf('The key of a table must be a string'));
        }

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
     * Add a comment line.
     *
     * @param string $comment
     *
     * @return TomlBuilder
     */
    public function addComment($comment)
    {
        if (is_string($comment)) {
            $this->append($this->dumpComment($comment), true);
        } else {
            throw new DumpException('The comment must be a string');
        }

        return $this;
    }

    /**
     * Get the TOML string.
     *
     * @return string
     */
    public function getTomlString()
    {
        return $this->output;
    }

    private function dumpValue($val)
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
                throw new DumpException(sprintf('Data type not supporter at the key: %s', $this->currentKey));
        }
    }

    private function dumpString($val)
    {
        if (0 === strpos($val, '@')) {
            return "'".preg_replace('/@/', '', $val, 1)."'";
        }

        $normalized = $this->normalizeString($val);

        if (false === $this->isStringValid($normalized)) {
            throw new DumpException(sprintf('The string have a invalid cacharters at the key: %s', $this->currentKey));
        }

        return '"'.$normalized.'"';
    }

    private function dumpBool($val)
    {
        return true === $val ? 'true' : 'false';
    }

    private function dumpArray($val)
    {
        $result = '';
        $first = true;
        $dataType = null;
        $lastType = null;

        foreach ($val as $item) {
            $lastType = gettype($item);
            $dataType = $dataType == null ? $lastType : $dataType;

            if ($lastType != $dataType) {
                throw new DumpException(sprintf('Data types cannot be mixed in an array. Key %s', $this->currentKey));
            }

            $result .= $first ? $this->dumpValue($item) : ', '.$this->dumpValue($item);
            $first = false;
        }

        return '['.$result.']';
    }

    private function dumpComment($val)
    {
        return '#'.$val;
    }

    private function dumpDatetime($val)
    {
        return $val->format('Y-m-d\TH:i:s\Z'); // ZULU form
    }

    private function dumpInteger($val)
    {
        return strval($val);
    }

    private function dumpFloat($val)
    {
        return strval($val);
    }

    private function getKeyPart($key)
    {
        if (is_string($key) && strlen($key = trim($key)) > 0) {
            return $key.' = ';
        } else {
            throw new DumpException('The key must be a string and must not be empty');
        }
    }

    private function append($val, $addPostNewline = false, $addIndentation = false, $addPreNewline = false)
    {
        if ($addPreNewline) {
            $this->output .= "\n";
            $this->currentLine++;
        }

        if ($addIndentation) {
            $val = $this->prefix.$val;
        }

        $this->output .= $val;

        if ($addPostNewline) {
            $this->output .= "\n";
            $this->currentLine++;
        }
    }

    private function addKey($key)
    {
        $this->currentKey = $key;
        $absKey = $this->getAbsoluteKey($key, $this->currentTable, $this->currentArrayOfTables);
        $this->addKeyToKeyList($absKey);
    }

    private function addKeyTable($key)
    {
        if (in_array($key, $this->keyListArryOfTables)) {
            throw new DumpException(
                sprintf('The table %s has already been defined as previous array of tables', $key));
        }

        $this->currentTable = $key;
        $absKey = $this->getAbsoluteKey($key, '', $this->currentArrayOfTables);
        $this->addKeyToKeyList($absKey);
    }

    private function addKeyArrayOfTables($key, array $keyParts)
    {
        if (true == $this->isTableImplicit($keyParts)) {
            $this->addInvalidArrayOfTablesKey($keyParts);
            $this->addKeyTable($key, $keyParts);

            return;
        }

        if (in_array($key, $this->keyListInvalidArrayOfTables)) {
            throw new DumpException(
                sprintf('The array of tables %s has already been defined as previous table', $key));
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

    private function getArrayTablesKeyPath(array $keyParts)
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

    private function addKeyToKeyList($key)
    {
        if (in_array($key, $this->keyList)) {
            throw new DumpException(sprintf('Syntax error: the key %s has already been defined', $key));
        }

        $this->keyList[] = $key;
    }

    private function getAbsoluteKey($key, $currentKeyTable, $currentKeyArrayOfTables)
    {
        $prefix = strlen($currentKeyArrayOfTables) > 0 ? $currentKeyArrayOfTables.'.' : '';
        $prefix .= strlen($currentKeyTable) > 0 ? $currentKeyTable.'.' : '';

        return $prefix.$key;
    }

    private function isTableImplicit(array $keyParts)
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

    private function addInvalidArrayOfTablesKey(array $keyParts)
    {
        foreach ($keyParts as $keyPart) {
            $this->keyListInvalidArrayOfTables[] = implode('.', $keyParts);
            array_pop($keyParts);
        }
    }

    /**
     * @param string $val String encoded by json_encode.
     *
     * @return bool True if string is compliance with TOML
     */
    private function isStringValid($val)
    {
        $allowed = array(
            '\\\\',
            '\\b',
            '\\t',
            '\\n',
            '\\f',
            '\\r',
            '\\"',
            '\\/',
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

    private function normalizeString($val)
    {
        $allowed = array(
            '\\' => '\\\\',
            "\b" => '\\b',
            "\t" => '\\t',
            "\n" => '\\n',
            "\f" => '\\f',
            "\r" => '\\r',
            '"' => '\\"',
            '/' => '\\/',
        );

        $normalized = str_replace(array_keys($allowed), $allowed, $val);

        return $normalized;
    }
}
