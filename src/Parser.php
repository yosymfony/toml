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

use Yosymfony\Toml\Exception\ParseException;

/**
 * Parser for Toml strings (0.2.0 specification).
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Parser
{
    private $lexer = null;
    private $currentLine = 0;
    private $data = null;
    private $result = array();
    private $tableNames = array();
    private $arrayTableNames = array();
    private $invalidArrayTablesName = array();
    private $inlineTableCounter = 0;
    private $inlineTableNameStack = array();

    public function __construct()
    {
        $this->data = &$this->result;
    }

    /**
     * Parses TOML string into a PHP value.
     *
     * @param string $value A TOML string
     *
     * @return mixed A PHP value
     */
    public function parse($value)
    {
        $this->lexer = new Lexer($value);
        $this->lexer->getToken();

        while ($this->lexer->getCurrentToken()->getType() !== Lexer::TOKEN_EOF) {
            switch ($this->lexer->getCurrentToken()->getType()) {
                case Lexer::TOKEN_HASH:
                    $this->processComment();        // #comment
                    break;
                case Lexer::TOKEN_LBRANK:
                    $this->processTables();         // [table] or [[array of tables]]
                    break;
                case Lexer::TOKEN_LITERAL:
                case Lexer::TOKEN_QUOTES:
                    $this->processKeyValue();       // key = value or "key name" = value
                    break;
                case Lexer::TOKEN_NEWLINE:
                    $this->currentLine++;

                    if ($this->inlineTableCounter > 0) {
                        throw new ParseException(
                            'Syntax error: unexpected newline inside a inline table',
                            $this->currentLine,
                            $this->lexer->getCurrentToken()->getValue());
                    }

                    break;
                case Lexer::TOKEN_RKEY:
                    if (0 === $this->inlineTableCounter) {
                        throw new ParseException(
                            'Syntax error: unexpected token',
                            $this->currentLine,
                            $this->lexer->getCurrentToken()->getValue());
                    }

                    $this->inlineTableCounter--;array_pop($this->inlineTableNameStack);
                    break;
                case Lexer::TOKEN_COMMA:
                    if ($this->inlineTableCounter > 0) {
                        break;
                    } else {
                        throw new ParseException(
                            'Syntax error: unexpected token',
                            $this->currentLine,
                            $this->lexer->getCurrentToken()->getValue());
                    }
                default:
                    throw new ParseException(
                        'Syntax error: unexpected token',
                        $this->currentLine,
                        $this->lexer->getCurrentToken()->getValue());
            }

            $this->lexer->getToken();
        }

        return empty($this->result) ? null : $this->result;
    }

    private function processComment()
    {
        while ($this->isTokenValidForComment($this->lexer->getToken())) {
            // do nothing
        }
    }

    private function isTokenValidForComment(Token $token)
    {
        return Lexer::TOKEN_NEWLINE !== $token->getType() && Lexer::TOKEN_EOF !== $token->getType();
    }

    private function processTables()
    {
        if (Lexer::TOKEN_LBRANK === $this->lexer->getNextToken()->getType()) {
            $this->processArrayOfTables();
        } else {
            $this->processTable();
        }

        $finalTokenType = $this->lexer->getToken()->getType();

        switch ($finalTokenType) {
            case Lexer::TOKEN_NEWLINE:
                $this->currentLine++;
                break;
            case Lexer::TOKEN_HASH:
                $this->processComment();
                break;
            case Lexer::TOKEN_EOF:
                break;
            default:
                throw new ParseException(
                    'Syntax error: expected new line or EOF after table/array of tables value',
                    $this->currentLine,
                    $this->lexer->getCurrentToken()->getValue());
        }
    }

    private function processArrayOfTables()
    {
        $key = '';

        $this->lexer->getToken();

        while ($this->isTokenValidForTablename($this->lexer->getToken())) {
            $key .= $this->lexer->getCurrentToken()->getValue();
        }

        $this->setArrayOfTables($key);

        $currentTokenType = $this->lexer->getCurrentToken()->getType();
        $nextTokenType = $this->lexer->getToken()->getType();

        if (Lexer::TOKEN_RBRANK !== $currentTokenType || Lexer::TOKEN_RBRANK !== $nextTokenType) {
            throw new ParseException(
                'Syntax error: expected close brank',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
    }

    private function processInlineTable($key)
    {
        $this->inlineTableCounter++;

        array_push($this->inlineTableNameStack, $key);

        $key = implode('.', $this->inlineTableNameStack);

        $this->setTable($key);
    }

    private function processTable()
    {
        $key = '';
        $quotesKey = false;

        while ($this->isTokenValidForTablename($this->lexer->getToken()) == true) {
            if (Lexer::TOKEN_QUOTES === $this->lexer->getCurrentToken()->getType()) {
                if (Lexer::TOKEN_STRING !== $this->lexer->getToken()->getType()) {
                    throw new ParseException(
                        sprintf('Syntax error: expected string. Key: %s', $key),
                        $this->currentLine,
                        $this->lexer->getCurrentToken()->getValue());
                }

                $key .= str_replace('.', '/./', $this->lexer->getCurrentToken()->getValue());

                if (Lexer::TOKEN_QUOTES !== $this->lexer->getToken()->getType()) {
                    throw new ParseException(
                        sprintf('Syntax error: expected quotes for closing key: %s', $key),
                        $this->currentLine,
                        $this->lexer->getCurrentToken()->getValue());
                }
            } else {
                $subkey = $this->lexer->getCurrentToken()->getValue();

                if (false === $this->isValidKey($subkey, false)) {
                    throw new ParseException(
                        sprintf('Syntax error: the key %s is invalid. A key without embraces can not contains white spaces', $key),
                        $this->currentLine,
                        $subkey);
                }

                $key .= $subkey;
            }
        }

        $this->setTable($key);

        if (Lexer::TOKEN_RBRANK !== $this->lexer->getCurrentToken()->getType()) {
            throw new ParseException(
                'Syntax error: expected close brank',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
    }

    private function isTokenValidForTablename(Token $token)
    {
        return Lexer::TOKEN_LITERAL === $token->getType() || Lexer::TOKEN_QUOTES === $token->getType();
    }

    private function setTable($key)
    {
        $nameParts = preg_split('/(?<=[^\/])[.](?<![\/])/', $key);
        $this->data = &$this->result;

        if (in_array($key, $this->tableNames) || in_array($key, $this->arrayTableNames)) {
            throw new ParseException(
                sprintf('Syntax error: the table %s has already been defined', $key),
                $this->currentLine, $this->lexer->getCurrentToken()->getValue());
        }

        $this->tableNames[] = $key;

        foreach ($nameParts as $namePart) {
            $namePart = str_replace('/./', '.', $namePart);

            if (0 == strlen($namePart)) {
                throw new ParseException('The name of the table must not be empty', $this->currentLine, $key);
            }

            if (array_key_exists($namePart, $this->data)) {
                if (!is_array($this->data[$namePart])) {
                    throw new ParseException(
                        sprintf('Syntax error: the table %s has already been defined', $key),
                        $this->currentLine, $this->lexer->getCurrentToken()->getValue());
                }
            } else {
                $this->data[$namePart] = array();
            }

            $this->data = &$this->data[$namePart];
        }
    }

    private function setArrayOfTables($key)
    {
        $nameParts = explode('.', $key);
        $endIndex = count($nameParts) - 1;

        if (true == $this->isTableImplicit($nameParts)) {
            $this->addInvalidArrayTablesName($nameParts);
            $this->setTable($key);

            return;
        }

        if (in_array($key, $this->invalidArrayTablesName)) {
            throw new ParseException(
                sprintf('Syntax error: the array of tables %s has already been defined as previous table', $key),
                $this->currentLine, $this->lexer->getCurrentToken()->getValue());
        }

        $this->data = &$this->result;
        $this->arrayTableNames[] = $key;

        foreach ($nameParts as $index => $namePart) {
            if (0 == strlen($namePart)) {
                throw new ParseException('The key must not be empty', $this->currentLine, $key);
            }

            if (false == array_key_exists($namePart, $this->data)) {
                $this->data[$namePart] = array();
                $this->data[$namePart][] = array();
            } elseif ($endIndex == $index) {
                $this->data[$namePart][] = array();
            }

            $this->data = &$this->getLastElementRef($this->data[$namePart]);
        }
    }

    private function processKeyValue()
    {
        $quotesKey = false;

        if (Lexer::TOKEN_QUOTES === $this->lexer->getCurrentToken()->getType()) {
            $quotesKey = true;
            $this->lexer->getToken();

            if (Lexer::TOKEN_STRING !== $this->lexer->getCurrentToken()->getType()) {
                throw new ParseException(
                    sprintf('Syntax error: expected string. Key: %s', $this->lexer->getCurrentToken()->getValue()),
                    $this->currentLine,
                    $this->lexer->getCurrentToken()->getValue());
            }

            $key = $this->lexer->getCurrentToken()->getValue();
            $this->lexer->getToken();
        } else {
            $key = $this->lexer->getCurrentToken()->getValue();

            while ($this->isTokenValidForKey($this->lexer->getToken())) {
                $key = $key.$this->lexer->getCurrentToken()->getValue();
            }
        }

        if ($quotesKey) {
            if (Lexer::TOKEN_QUOTES === $this->lexer->getCurrentToken()->getType()) {
                $this->lexer->getToken();
            } else {
                throw new ParseException(
                    sprintf('Syntax error: expected quotes for closing key: %s', $key),
                    $this->currentLine,
                    $this->lexer->getCurrentToken()->getValue());
            }
        }

        if (Lexer::TOKEN_EQUAL !== $this->lexer->getCurrentToken()->getType()) {
            throw new ParseException(
                sprintf('Syntax error: expected equal near the key: %s', $key),
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }

        $key = trim($key);

        if (false === $this->isValidKey($key, $quotesKey)) {
            throw new ParseException(
                sprintf('Syntax error: the key %s is invalid. A key without embraces can not contains white spaces', $key),
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }

        if (array_key_exists($key, $this->data)) {
            throw new ParseException(
                sprintf('Syntax error: the key %s has already been defined', $key),
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }

        switch ($this->lexer->getToken()->getType()) {
            case Lexer::TOKEN_QUOTES:
            case Lexer::TOKEN_TRIPLE_QUOTES:
            case Lexer::TOKEN_QUOTE:
            case Lexer::TOKEN_TRIPLE_QUOTE:
                $this->data[$key] = $this->getStringValue($this->lexer->getCurrentToken());
                break;
            case Lexer::TOKEN_LKEY:
                    $this->processInlineTable($key);
                    break;
            case Lexer::TOKEN_LBRANK:
                $this->data[$key] = $this->getArrayValue();
                break;
            case Lexer::TOKEN_LITERAL:
                $this->data[$key] = $this->getLiteralValue();
                break;
            default:
                throw new ParseException(
                    'Syntax error: expected data type',
                    $this->currentLine,
                    $this->lexer->getCurrentToken()->getValue());
        }
    }

    private function isTokenValidForKey(Token $token)
    {
        return Lexer::TOKEN_EQUAL  !== $token->getType()
            && Lexer::TOKEN_NEWLINE !== $token->getType()
            && Lexer::TOKEN_EOF !== $token->getType()
            && Lexer::TOKEN_QUOTES !== $token->getType()
            && Lexer::TOKEN_HASH !== $token->getType()
            && Lexer::TOKEN_TRIPLE_QUOTES !== $token->getType();
    }

    private function isValidKey($key, $quotesActived)
    {
        if (false === $quotesActived && false !== strpos($key, ' ')) {
            return false;
        }

        return true;
    }

    private function getStringValue($openToken)
    {
        $result = '';

        if (Lexer::TOKEN_STRING !== $this->lexer->getToken()->getType()) {
            throw new ParseException(
                'Syntax error: expected string',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }

        $result = (string) $this->lexer->getCurrentToken()->getValue();

        if ($openToken->getType() !== $this->lexer->getToken()->getType()) {
            throw new ParseException(
                'Syntax error: expected close quotes',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }

        return $result;
    }

    private function getArrayValue()
    {
        $result = array();
        $dataType = null;
        $lastType = null;
        $value = null;

        while (Lexer::TOKEN_RBRANK != $this->lexer->getToken()->getType()) {
            switch ($this->lexer->getCurrentToken()->getType()) {
                case Lexer::TOKEN_COMMA:
                    if ($dataType == null) {
                        throw new ParseException('Expected data type before comma', $this->currentLine, $value);
                    }
                    break;
                case Lexer::TOKEN_QUOTES:
                case Lexer::TOKEN_TRIPLE_QUOTES:
                case Lexer::TOKEN_QUOTE:
                case Lexer::TOKEN_TRIPLE_QUOTE:
                    $lastType = 'string';
                    $dataType = $dataType == null ? $lastType : $dataType;
                    $value = $this->getStringValue($this->lexer->getCurrentToken());
                    $result[] = $value;
                    break;
                case Lexer::TOKEN_LBRANK:
                    $lastType = 'array';
                    $dataType = $dataType == null ? $lastType : $dataType;
                    $result[] = $this->getArrayValue();
                    break;
                case Lexer::TOKEN_LITERAL:
                    $value = $this->getLiteralValue();
                    $lastType = gettype($value);
                    $dataType = $dataType == null ? $lastType : $dataType;
                    $result[] = $value;
                    break;
                case Lexer::TOKEN_HASH:
                    $this->processComment();
                    break;
                case Lexer::TOKEN_NEWLINE:
                    $this->currentLine++;
                    break;
                case Lexer::TOKEN_RBRANK:
                    break;
                default:
                    throw new ParseException('Syntax error', $this->currentLine, $this->lexer->getCurrentToken()->getValue());
            }

            if ($lastType != $dataType) {
                throw new ParseException('Data types cannot be mixed in an array', $this->currentLine, $value);
            }
        }

        return $result;
    }

    private function getLiteralValue()
    {
        $token = $this->lexer->getCurrentToken();

        if ($this->isLiteralBoolean($token)) {
            return $token->getValue() == 'true' ? true : false;
        }

        if ($this->isLiteralInteger($token)) {
            return $this->getInteger($token);
        }

        if ($this->isLiteralFloat($token)) {
            return $this->getFloat($token);
        }

        if ($this->isLiteralDatetime($token)) {
            return new \Datetime($token->getValue());
        }

        throw new ParseException('Unknown value type', $this->currentLine, $token->getValue());
    }

    private function isLiteralBoolean(Token $token)
    {
        $result = false;

        switch ($token->getValue()) {
            case 'true':
            case 'false':
                $result = true;
        }

        return $result;
    }

    private function isLiteralInteger(Token $token)
    {
        return preg_match('/^[+-]?(\d_?)+$/', $token->getValue());
    }

    private function isLiteralFloat(Token $token)
    {
        return preg_match('/^[+-]?(((\d_?)+[\.](\d_?)+)|(((\d_?)+[\.]?(\d_?)*)[eE][+-]?(\d_?)+))$/', $token->getValue());
    }

    private function isLiteralDatetime(Token $token)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|(\.\d{6})?-\d{2}:\d{2})$/', $token->getValue());
    }

    private function &getLastElementRef(&$array)
    {
        end($array);

        return $array[key($array)];
    }

    private function isTableImplicit(array $tablenameParts)
    {
        if (count($tablenameParts) > 1) {
            array_pop($tablenameParts);

            $tablename = implode('.', $tablenameParts);

            if (false == in_array($tablename, $this->arrayTableNames)) {
                return true;
            }
        }

        return false;
    }

    private function getInteger(Token $token)
    {
        $value = $token->getValue();

        if (preg_match('/([^\d]_[^\d])|(_$)/', $value)) {
            throw new ParseException('Invalid integer number: underscore must be surrounded by at least one digit', $this->currentLine, $token->getValue());
        }

        $value = str_replace('_', '', $value);

        if (preg_match('/^0\d+/', $value)) {
            throw new ParseException('Invalid integer number: leading zeros are not allowed', $this->currentLine, $token->getValue());
        }

        return (int) $value;
    }

    private function getFloat(Token $token)
    {
        $value = $token->getValue();

        if (preg_match('/([^\d]_[^\d])|_[eE]|[eE]_|(_$)/', $value)) {
            throw new ParseException('Invalid float number: underscore must be surrounded by at least one digit', $this->currentLine, $token->getValue());
        }

        $value = str_replace('_', '', $value);

        if (preg_match('/^0\d+/', $value)) {
            throw new ParseException('Invalid float number: leading zeros are not allowed', $this->currentLine, $token->getValue());
        }

        return (float) $value;
    }

    private function addInvalidArrayTablesName(array $tablenameParts)
    {
        foreach ($tablenameParts as $part) {
            $this->invalidArrayTablesName[] = implode('.', $tablenameParts);
            array_pop($tablenameParts);
        }
    }
}
