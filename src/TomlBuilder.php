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
    protected $currentKey;
    /** @var KeyStore */
    protected $keyStore;
    private $currentLine = 0;
    /** @var array */
    private static $specialCharacters;
    /** @var array */
    private static $escapedSpecialCharacters;
    private static $specialCharactersMapping = [
        '\\' => '\\\\',
        "\b" => '\\b',
        "\t" => '\\t',
        "\n" => '\\n',
        "\f" => '\\f',
        "\r" => '\\r',
        '"' => '\\"',
    ];

    /**
     * Constructor.
     *
     * @param int $indent The amount of spaces to use for indentation of nested nodes
     */
    public function __construct(int $indent = 4)
    {
        $this->keyStore = new KeyStore();
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
    public function addValue(string $key, $val, string $comment = '') : TomlBuilder
    {
        $this->currentKey = $key;
        $this->exceptionIfKeyEmpty($key);
        $this->addKey($key);

        if (!$this->isUnquotedKey($key)) {
            $key = '"'.$key.'"';
        }

        $line = "{$key} = {$this->dumpValue($val)}";

        if (!empty($comment)) {
            $line .= ' '.$this->dumpComment($comment);
        }

        $this->append($line, true);

        return $this;
    }

    /**
     * Adds a table.
     *
     * @param string $key Table name. Dot character have a special mean. e.g: "fruit.type"
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addTable(string $key) : TomlBuilder
    {
        $this->exceptionIfKeyEmpty($key);
        $addPreNewline = $this->currentLine > 0 ? true : false;
        $keyParts = explode('.', $key);

        foreach ($keyParts as $keyPart) {
            $this->exceptionIfKeyEmpty($keyPart, "Table: \"{$key}\".");
            $this->exceptionIfKeyIsNotUnquotedKey($keyPart);
        }

        $line = "[{$key}]";
        $this->addTableKey($key);
        $this->append($line, true, false, $addPreNewline);

        return $this;
    }

    /**
     * This method has been marked as deprecated and will be deleted in version 2.0.0
     * @deprecated 2.0.0 Use the method "addArrayOfTable" instead
     */
    public function addArrayTables(string $key) : TomlBuilder
    {
        return $this->addArrayOfTable($key);
    }

    /**
     * Adds an array of tables element
     *
     * @param string $key The name of the array of tables
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addArrayOfTable(string $key) : TomlBuilder
    {
        $this->exceptionIfKeyEmpty($key);
        $addPreNewline = $this->currentLine > 0 ? true : false;
        $keyParts = explode('.', $key);

        foreach ($keyParts as $keyPart) {
            $this->exceptionIfKeyEmpty($keyPart, "Array of table: \"{$key}\".");
            $this->exceptionIfKeyIsNotUnquotedKey($keyPart);
        }

        $line = "[[{$key}]]";
        $this->addArrayOfTableKey($key);
        $this->append($line, true, false, $addPreNewline);

        return $this;
    }

    /**
     * Adds a comment line
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

    /**
     * Returns the escaped characters for basic strings
     */
    protected function getEscapedCharacters() : array
    {
        if (self::$escapedSpecialCharacters !== null) {
            return self::$escapedSpecialCharacters;
        }

        return self::$escapedSpecialCharacters = \array_values(self::$specialCharactersMapping);
    }

    /**
     * Returns the special characters for basic strings
     */
    protected function getSpecialCharacters() : array
    {
        if (self::$specialCharacters !== null) {
            return self::$specialCharacters;
        }

        return self::$specialCharacters = \array_keys(self::$specialCharactersMapping);
    }

    /**
     * Adds a key to the store
     *
     * @param string $key The key name
     *
     * @return void
     */
    protected function addKey(string $key) : void
    {
        if (!$this->keyStore->isValidKey($key)) {
            throw new DumpException("The key \"{$key}\" has already been defined previously.");
        }

        $this->keyStore->addKey($key);
    }

    /**
     * Adds a table key to the store
     *
     * @param string $key The table key name
     *
     * @return void
     */
    protected function addTableKey(string $key) : void
    {
        if (!$this->keyStore->isValidTableKey($key)) {
            throw new DumpException("The table key \"{$key}\" has already been defined previously.");
        }

        if ($this->keyStore->isRegisteredAsArrayTableKey($key)) {
            throw new DumpException("The table \"{$key}\" has already been defined as previous array of tables.");
        }

        $this->keyStore->addTableKey($key);
    }

    /**
     * Adds an array of table key to the store
     *
     * @param string $key The key name
     *
     * @return void
     */
    protected function addArrayOfTableKey(string $key) : void
    {
        if (!$this->keyStore->isValidArrayTableKey($key)) {
            throw new DumpException("The array of table key \"{$key}\" has already been defined previously.");
        }

        if ($this->keyStore->isTableImplicitFromArryTable($key)) {
            throw new DumpException("The key \"{$key}\" has been defined as a implicit table from a previous array of tables.");
        }

        $this->keyStore->addArrayTableKey($key);
    }

    /**
     * Dumps a value
     *
     * @param string|int|bool|float|array|Datetime  $val The value
     *
     * @return string
     */
    protected function dumpValue($val) : string
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
                throw new DumpException("Data type not supporter at the key: \"{$this->currentKey}\".");
        }
    }

    /**
     * Adds content to the output
     *
     * @param string $val
     * @param bool $addPostNewline Indicates if add a newline after the value
     * @param bool $addIndentation Indicates if add indentation to the line
     * @param bool $addPreNewline Indicates if add a new line before the value
     *
     * @return void
     */
    protected function append(string $val, bool $addPostNewline = false, bool $addIndentation = false, bool $addPreNewline = false) : void
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

    private function dumpString(string $val) : string
    {
        if ($this->isLiteralString($val)) {
            return "'".preg_replace('/@/', '', $val, 1)."'";
        }

        $normalized = $this->normalizeString($val);

        if (!$this->isStringValid($normalized)) {
            throw new DumpException("The string has an invalid charters at the key \"{$this->currentKey}\".");
        }

        return '"'.$normalized.'"';
    }

    private function isLiteralString(string $val) : bool
    {
        return strpos($val, '@') === 0;
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
                throw new DumpException("Data types cannot be mixed in an array. Key: \"{$this->currentKey}\".");
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

    private function isStringValid(string $val) : bool
    {
        $noSpecialCharacter = \str_replace($this->getEscapedCharacters(), '', $val);
        $noSpecialCharacter = \preg_replace('/\\\\u([0-9a-fA-F]{4})/', '', $noSpecialCharacter);
        $noSpecialCharacter = \preg_replace('/\\\\u([0-9a-fA-F]{8})/', '', $noSpecialCharacter);

        $pos = strpos($noSpecialCharacter, '\\');

        if ($pos !== false) {
            return false;
        }

        return true;
    }

    private function normalizeString(string $val) : string
    {
        $normalized = \str_replace($this->getSpecialCharacters(), $this->getEscapedCharacters(), $val);

        return $normalized;
    }

    private function exceptionIfKeyEmpty(string $key, string $additionalMessage = '') : void
    {
        $message = 'A key, table name or array of table name cannot be empty or null.';

        if ($additionalMessage != '') {
            $message .= " {$additionalMessage}";
        }

        if (empty(trim($key))) {
            throw new DumpException($message);
        }
    }

    private function exceptionIfKeyIsNotUnquotedKey($key) : void
    {
        if (!$this->isUnquotedKey($key)) {
            throw new DumpException("Only unquoted keys are allowed in this implementation. Key: \"{$key}\".");
        }
    }

    private function isUnquotedKey(string $key) : bool
    {
        return \preg_match('/^([-A-Z_a-z0-9]+)$/', $key) === 1;
    }
}
