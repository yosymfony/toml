<?php

/*
 * This file is part of the Yosymfony\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * AOT - AOTRef additions and  modifications by Michael Rynn <https://github.com/betrixed/toml>
 */

namespace Yosymfony\Toml;

use Yosymfony\ParserUtils\AbstractParser;
use Yosymfony\ParserUtils\Token;
use Yosymfony\ParserUtils\TokenStream;
use Yosymfony\ParserUtils\SyntaxErrorException;

/**
 * Parser for TOML strings (specification version 0.4.0).
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Parser extends AbstractParser
{

    const PATH_FULL = 2;
    const PATH_PART = 1;
    const PATH_NONE = 0;

    // Waiting for a test case that shows $useKeyStore is needed
    private $useKeyStore = false; 
    private $keys = []; //usage controlled by $useKeyStore
    private $currentKeyPrefix = '';//usage controlled by $useKeyStore
    // array context for key = value
    // parsed result to return
    private $result = [];
    // path string to array context for key = value
    // by convention, is either empty , or set with
    // terminator of '.'

    private $workArray;
    // array of all AOTRef using base name string key
    private $refAOT = [];
    // remenber table paths created in passing
    private $implicitTables = []; // array[string] of bool

    private function registerAOT(AOTRef $obj)
    {
        $this->refAOT[$obj->key] = $obj;
    }

    /**
     * Lookup dictionary for AOTRef to find a complete, or partial match object for key
     * by breaking the key up until match found, or no key left.
     * // TODO: return array of AOTRef objects?
     * @param string $newName
     * @return [AOTRef object, match type] 
     */
    private function getAOTRef(string $newName)
    {
        $testObj = isset($this->refAOT[$newName]) ? $this->refAOT[$newName] : null;
        if (!is_null($testObj)) {
            return [$testObj, Parser::PATH_FULL];
        }
        $ipos = strrpos($newName, '.');
        while ($ipos !== false) {
            $newName = substr($newName, 0, $ipos);
            $testObj = isset($this->refAOT[$newName]) ? $this->refAOT[$newName] : null;
            if (!is_null($testObj)) {
                return [$testObj, Parser::PATH_PART];
            }
            $ipos = strrpos($newName, '.');
        }
        return [null, Parser::PATH_NONE];
    }

    /**
     * Reads string from specified file path and parses it as TOML.
     *
     * @param (string) File path
     *
     * @return (array) Toml::parse() result
     */
    public static function parseFile($path)
    {
        if (!is_file($path)) {
            throw new Exception('Invalid file path');
        }

        $toml = file_get_contents($path);

        // Remove BOM if present
        $toml = preg_replace('/^' . pack('H*', 'EFBBBF') . '/', '', $toml);

        $parser = new Parser(new Lexer());
        return $parser->parse($toml);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $input)
    {
        if (preg_match('//u', $input) === false) {
            throw new SyntaxErrorException('The TOML input does not appear to be valid UTF-8.');
        }

        $input = str_replace(["\r\n", "\r"], "\n", $input);
        $input = str_replace("\t", ' ', $input);

        return parent::parse($input);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseImplentation(TokenStream $ts): array
    {
        try {
            $this->resetWorkArrayToResultArray();

            while ($ts->hasPendingTokens()) {
                $this->processExpression($ts);
            }
        } finally {
            foreach ($this->refAOT as $key => $value) {
                $value->unlink();
            }
        }
        return $this->result;
    }

    /**
     * Process an expression
     *
     * @param TokenStream $ts The token stream
     */
    private function processExpression(TokenStream $ts): void
    {
        $tokenId = $ts->peekNext();
        // get name for debugging
        $tokenName = Lexer::tokenName($tokenId);
        switch ($tokenId) {
            case Lexer::T_HASH :
                $this->parseComment($ts);
                break;
            case Lexer::T_QUOTATION_MARK:
            case Lexer::T_UNQUOTED_KEY:
            case Lexer::T_APOSTROPHE :
            case Lexer::T_INTEGER :
                $this->parseKeyValue($ts);
                break;
            case Lexer::T_LEFT_SQUARE_BRAKET:
                if ($ts->isNextSequence([Lexer::T_LEFT_SQUARE_BRAKET, Lexer::T_LEFT_SQUARE_BRAKET])) {
                    $this->parseArrayOfTables($ts);
                } else {
                    $this->parseTable($ts);
                }
                break;
            case Lexer::T_SPACE :
            case Lexer::T_NEWLINE:
            case Lexer::T_EOS:
                $ts->moveNext();
                break;
            default:
                //TODO: This message is probably outdated by now
                // Not general enougy, probably to match test cases.
                $msg = 'Expected T_HASH or T_UNQUOTED_KEY.';
                $this->unexpectedTokenError($ts->moveNext(), $msg);
                break;
        }
    }

    private function duplicateKey(string $keyName)
    {
        $this->syntaxError("The key \"$keyName\" has already been defined previously.");
    }

    private function parseComment(TokenStream $ts): void
    {
        $this->assertNext(Lexer::T_HASH, $ts);
        do {
            $ts->moveNext();
            $tokenId = $ts->peekNext();
        } while ($tokenId !== Lexer::T_NEWLINE && $tokenId !== Lexer::T_EOS);
    }

    private function skipIfSpace(TokenStream $ts): int
    {
        return $ts->skipWhile(Lexer::T_SPACE);
    }

    private function parseKeyValue(TokenStream $ts, bool $isFromInlineTable = false): void
    {
        $keyName = $this->parseKeyName($ts);
        if ($this->useKeyStore) {
            $this->mustBeUnique($this->currentKeyPrefix . $keyName);
        } else {
            if (isset($this->workArray[$keyName])) {
                $this->duplicateKey($keyName);
            }
        }

        //$this->addKeyToKeyStore($this->composeKeyWithCurrentKeyPrefix($keyName));
        $this->skipIfSpace($ts);
        $this->assertNext(Lexer::T_EQUAL, $ts);
        $this->skipIfSpace($ts);

        $nextToken = $ts->peekNext();

        if ($nextToken === Lexer::T_LEFT_SQUARE_BRAKET) {
            $this->workArray[$keyName] = $this->parseArray($ts);
        } elseif ($nextToken === Lexer::T_LEFT_CURLY_BRACE) {
            $this->parseInlineTable($ts, $keyName);
        } else {
            $this->workArray[$keyName] = $this->parseSimpleValue($ts)->value;
        }

        if (!$isFromInlineTable) {
            $this->skipIfSpace($ts);
            $this->parseCommentIfExists($ts);
            $this->errorIfNextIsNotNewlineOrEOS($ts);
        }
    }

    private function parseKeyName(TokenStream $ts): string
    {
        $token = $ts->peekNext();
        switch ($token) {
            case Lexer::T_UNQUOTED_KEY:
                return $this->matchNext(Lexer::T_UNQUOTED_KEY, $ts);
            case Lexer::T_QUOTATION_MARK:
                return $this->parseBasicString($ts);
            case Lexer::T_APOSTROPHE:
                return $this->parseLiteralString($ts);
            case Lexer::T_INTEGER :
                return (string) $this->parseInteger($ts);
            default:
                $msg = 'Unexpected token in parseKeyName';
                $this->unexpectedTokenError($ts->moveNext(), $msg);
                break;
        }
    }

    /**
     * @return object An object with two public properties: value and type.
     */
    private function parseSimpleValue(TokenStream $ts)
    {
        $token = $ts->peekNext();
        switch ($token) {
            case Lexer::T_BOOLEAN:
                $type = 'boolean';
                $value = $this->parseBoolean($ts);
                break;
            case Lexer::T_INTEGER:
                $type = 'integer';
                $value = $this->parseInteger($ts);
                break;
            case Lexer::T_FLOAT:
                $type = 'float';
                $value = $this->parseFloat($ts);
                break;
            case Lexer::T_QUOTATION_MARK:
                $type = 'string';
                $value = $this->parseBasicString($ts);
                break;
            case Lexer::T_3_QUOTATION_MARK:
                $type = 'string';
                $value = $this->parseMultilineBasicString($ts);
                break;
            case Lexer::T_APOSTROPHE:
                $type = 'string';
                $value = $this->parseLiteralString($ts);
                break;
            case Lexer::T_3_APOSTROPHE:
                $type = 'string';
                $value = $this->parseMultilineLiteralString($ts);
                break;
            case Lexer::T_DATE_TIME:
                $type = 'datetime';
                $value = $this->parseDatetime($ts);
                break;
            default:
                $this->unexpectedTokenError(
                        $ts->moveNext(), 'Expected boolean, integer, long, string or datetime.'
                );
                break;
        }
        $valueStruct = new class() {

            public $value;
            public $type;
        };

        $valueStruct->value = $value;
        $valueStruct->type = $type;

        return $valueStruct;
    }

    private function parseBoolean(TokenStream $ts): bool
    {
        return $this->matchNext(Lexer::T_BOOLEAN, $ts) == 'true' ? true : false;
    }

    private function parseInteger(TokenStream $ts): int
    {
        $token = $ts->moveNext();
        $value = $token->getValue();

        if (preg_match('/([^\d]_[^\d])|(_$)/', $value)) {
            $this->syntaxError(
                    'Invalid integer number: underscore must be surrounded by at least one digit.', $token
            );
        }

        $value = str_replace('_', '', $value);

        if (preg_match('/^0\d+/', $value)) {
            $this->syntaxError(
                    'Invalid integer number: leading zeros are not allowed.', $token
            );
        }

        return (int) $value;
    }

    private function parseFloat(TokenStream $ts): float
    {
        $token = $ts->moveNext();
        $value = $token->getValue();

        if (preg_match('/([^\d]_[^\d])|_[eE]|[eE]_|(_$)/', $value)) {
            $this->syntaxError(
                    'Invalid float number: underscore must be surrounded by at least one digit.', $token
            );
        }

        $value = str_replace('_', '', $value);

        if (preg_match('/^0\d+/', $value)) {
            $this->syntaxError(
                    'Invalid float number: leading zeros are not allowed.', $token
            );
        }

        return (float) $value;
    }

    private function parseBasicString(TokenStream $ts): string
    {
        $this->assertNext(Lexer::T_QUOTATION_MARK, $ts);

        $result = '';

        $tokenId = $ts->peekNext();
        while ($tokenId !== Lexer::T_QUOTATION_MARK) {
            if (($tokenId === Lexer::T_NEWLINE) || ($tokenId === Lexer::T_EOS) || ($tokenId
                === Lexer::T_ESCAPE)) {
                // throws
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            $value = ($tokenId === Lexer::T_ESCAPED_CHARACTER) ? $this->parseEscapedCharacter($ts)
                        : $ts->moveNext()->getValue();
            $result .= $value;
            $tokenId = $ts->peekNext();
        }

        $this->assertNext(Lexer::T_QUOTATION_MARK, $ts);

        return $result;
    }

    private function parseMultilineBasicString(TokenStream $ts): string
    {
        $this->assertNext(Lexer::T_3_QUOTATION_MARK, $ts);

        $result = '';

        $ts->skipWhile(Lexer::T_NEWLINE, 1);
        $nextToken = $ts->peekNext();
        while (true) {

            switch ($nextToken) {
                case Lexer::T_3_QUOTATION_MARK :
                    $this->assertNext(Lexer::T_3_QUOTATION_MARK, $ts);
                    break 2;
                case Lexer::T_EOS:
                    $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_QUOTATION_MARK".');
                    break;
                case Lexer::T_ESCAPE:
                    do {
                        $nextToken = $ts->movePeekNext();
                    } while (($nextToken === Lexer::T_SPACE) || ($nextToken === Lexer::T_NEWLINE) || ($nextToken
                    === Lexer::T_ESCAPE));
                    break;
                case Lexer::T_SPACE:
                    $result .= ' ';
                    $nextToken = $ts->movePeekNext();
                    break;
                case Lexer::T_NEWLINE:
                    $result .= "\n";
                    $nextToken = $ts->movePeekNext();
                    break;
                case Lexer::T_ESCAPED_CHARACTER:
                    $value = $this->parseEscapedCharacter($ts);
                    $result .= $value;
                    $nextToken = $ts->peekNext();
                    break;
                default:
                    $value = $ts->moveNext()->getValue();
                    $result .= $value;
                    $nextToken = $ts->peekNext();
                    break;
            }
        }
        return $result;
    }

    private function parseLiteralString(TokenStream $ts): string
    {
        $this->assertNext(Lexer::T_APOSTROPHE, $ts);

        $result = '';
        $tokenId = $ts->peekNext();

        while ($tokenId !== Lexer::T_APOSTROPHE) {
            if (($tokenId === Lexer::T_NEWLINE) || ($tokenId === Lexer::T_EOS)) {
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            $result .= $ts->moveNext()->getValue();
            $tokenId = $ts->peekNext();
        }
        $this->assertNext(Lexer::T_APOSTROPHE, $ts);
        return $result;
    }

    private function parseMultilineLiteralString(TokenStream $ts): string
    {
        $this->assertNext(Lexer::T_3_APOSTROPHE, $ts);

        $result = '';

        $ts->skipWhile(Lexer::T_NEWLINE, 1);

        while (true) {
            $tokenId = $ts->peekNext();
            if ($tokenId === Lexer::T_3_APOSTROPHE) {
                break;
            }
            if ($tokenId === Lexer::T_EOS) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_APOSTROPHE".');
            }
            $result .= $ts->moveNext()->getValue();
        }

        $this->assertNext(Lexer::T_3_APOSTROPHE, $ts);

        return $result;
    }

    private function parseEscapedCharacter(TokenStream $ts): string
    {
        $token = $ts->moveNext();
        $value = $token->getValue();

        switch ($value) {
            case '\b':
                return "\b";
            case '\t':
                return "\t";
            case '\n':
                return "\n";
            case '\f':
                return "\f";
            case '\r':
                return "\r";
            case '\"':
                return '"';
            case '\\\\':
                return '\\';
        }

        if (strlen($value) === 6) {
            return json_decode('"' . $value . '"');
        }

        preg_match('/\\\U([0-9a-fA-F]{4})([0-9a-fA-F]{4})/', $value, $matches);

        return json_decode('"\u' . $matches[1] . '\u' . $matches[2] . '"');
    }

    private function parseDatetime(TokenStream $ts): \Datetime
    {
        $date = $this->matchNext(Lexer::T_DATE_TIME, $ts);

        return new \Datetime($date);
    }

    private function skipWhite(TokenStream $ts): void
    {
        $tokenId = $ts->peekNext();
        while ($tokenId === Lexer::T_SPACE || $tokenId === Lexer::T_NEWLINE) {
            $tokenId = $ts->movePeekNext();
        }
    }

    private function parseArray(TokenStream $ts): array
    {
        $result = [];
        $leaderType = '';

        $this->assertNext(Lexer::T_LEFT_SQUARE_BRAKET, $ts);

        while (!$ts->isNext(Lexer::T_RIGHT_SQUARE_BRAKET)) {
            $this->skipWhite($ts);
            //$ts->skipWhileAny([Lexer::T_NEWLINE, Lexer::T_SPACE]);
            $this->parseCommentsInsideBlockIfExists($ts);

            if ($ts->isNext(Lexer::T_LEFT_SQUARE_BRAKET)) {
                if ($leaderType === '') {
                    $leaderType = 'array';
                }

                if ($leaderType !== 'array') {
                    $this->syntaxError(sprintf(
                                    'Data types cannot be mixed in an array. Value: "%s".', $valueStruct->value
                    ));
                }

                $result[] = $this->parseArray($ts);
            } else {
                $valueStruct = $this->parseSimpleValue($ts);

                if ($leaderType === '') {
                    $leaderType = $valueStruct->type;
                }

                if ($valueStruct->type !== $leaderType) {
                    $this->syntaxError(sprintf(
                                    'Data types cannot be mixed in an array. Value: "%s".', $valueStruct->value
                    ));
                }

                $result[] = $valueStruct->value;
            }

            $this->skipWhite($ts);
            //$ts->skipWhileAny([Lexer::T_NEWLINE, Lexer::T_SPACE]);
            $this->parseCommentsInsideBlockIfExists($ts);

            if (!$ts->isNext(Lexer::T_RIGHT_SQUARE_BRAKET)) {
                $this->assertNext(Lexer::T_COMMA, $ts);
            }

            $this->skipWhite($ts);
            //$ts->skipWhileAny([Lexer::T_NEWLINE, Lexer::T_SPACE]);
            $this->parseCommentsInsideBlockIfExists($ts);
        }

        $this->assertNext(Lexer::T_RIGHT_SQUARE_BRAKET, $ts);

        return $result;
    }

    private function addArrayKeyToWorkArray(string $keyName): void
    {
        if (isset($this->workArray[$keyName]) === false) {
            $this->workArray[$keyName] = [];
        }

        $this->workArray = &$this->workArray[$keyName];
    }

    private function parseInlineTable(TokenStream $ts, string $keyName): void
    {
        $this->assertNext(Lexer::T_LEFT_CURLY_BRACE, $ts);

        $priorWorkArray = &$this->workArray;

        $this->addArrayKeyToWorkArray($keyName);

        if ($this->useKeyStore) {
            $priorcurrentKeyPrefix = $this->currentKeyPrefix;
            $this->currentKeyPrefix = $this->currentKeyPrefix . $keyName . ".";
        }

        $this->parseSpaceIfExists($ts);

        if ($ts->peekNext() !== Lexer::T_RIGHT_CURLY_BRACE) {
            $this->parseKeyValue($ts, true);
            $this->parseSpaceIfExists($ts);
        }

        while ($ts->peekNext() === Lexer::T_COMMA) {
            $ts->moveNext();

            $this->parseSpaceIfExists($ts);
            $this->parseKeyValue($ts, true);
            $this->parseSpaceIfExists($ts);
        }

        $this->assertNext(Lexer::T_RIGHT_CURLY_BRACE, $ts);
        if ($this->useKeyStore) {
            $this->currentKeyPrefix = $priorcurrentKeyPrefix;
        }
        $this->workArray = &$priorWorkArray;
    }

    private function parseKeyPath(TokenStream $ts)
    {
        $fullTablePath = [];
        $fullTablePath[] = $this->parseKeyName($ts);
        $tokenId = $ts->peekNext();
        while ($tokenId === Lexer::T_DOT) {
            $ts->moveNext();
            $fullTablePath[] = $this->parseKeyName($ts);
            $tokenId = $ts->peekNext();
        }
        return $fullTablePath;
    }

    private function registerAOTError($key)
    {
        throw new \Exception('Array of Table exists but not registered - ' . $key);
    }

    private function parseTable(TokenStream $ts): void
    {
        $this->assertNext(Lexer::T_LEFT_SQUARE_BRAKET, $ts);

        $fullTablePath = $this->parseKeyPath($ts);
        $fullTableName = self::pathToName($fullTablePath);
        // get AOT context, if any
        list($tref, $match) = $this->getAOTRef($fullTableName);

        switch ($match) {
            case Parser::PATH_PART:
                $baseName = $tref->getFullIndexName();
                $offsetPath = array_slice($fullTablePath, $tref->depth);
                $aref = & $tref->ref[$tref->index];

                $lastIndex = count($offsetPath) - 1;
                $doImplicit = false;
                break;
            case Parser::PATH_NONE:
                // root name space
                $baseName = '';
                $offsetPath = $fullTablePath;
                $aref = & $this->result;
                $lastIndex = count($offsetPath) - 1;
                $doImplicit = true;
                break;
            case Parser::PATH_FULL:
            default:
                // This table exactly matches a AOT base path - not allowed
                $this->duplicateKey($fullTableName);
                break;
        }

        $myPrefix = $baseName;

        foreach ($offsetPath as $idx => $tableName) {
            $baseName = (strlen($baseName) > 0) ? $baseName . "." . $tableName : $tableName;
            if ($idx < $lastIndex) {
                if (!isset($aref[$tableName])) {
                    $aref[$tableName] = [];
                    // Implicit table creation
                    if ($doImplicit) {
                        $this->implicitTables[$baseName] = true;
                    }
                }
                $aref = & $aref[$tableName];
            } else {
                if (isset($aref[$tableName])) {
                    // If created implicitly before, should only have 1 value
                    if ($this->useKeyStore) {
                        if (isset($this->keys[$baseName])) {
                            $this->duplicateKey($baseName);
                        }
                    }
                    $isOKThisTime = isset($this->implicitTables[$fullTableName]) && is_array($aref[$tableName]) && (count($aref[$tableName])
                            == 1);
                    if (!$isOKThisTime) {
                        $this->duplicateKey($fullTableName);
                    }
                } else {
                    $aref[$tableName] = [];
                }
                $aref = & $aref[$tableName];
            }
        }
        $this->workArray = & $aref;

        if ($this->useKeyStore) {
            if (!$this->setUniqueKey($baseName)) {
                $this->errorUniqueKey($baseName);
            }
            $this->currentKeyPrefix = $baseName . ".";
        }

        $this->assertNext(Lexer::T_RIGHT_SQUARE_BRAKET, $ts);

        $this->parseSpaceIfExists($ts);
        $this->parseCommentIfExists($ts);
        $this->errorIfNextIsNotNewlineOrEOS($ts);
    }

    private static function pathToName($path)
    {
        $ct = count($path);
        if ($ct > 1) {
            return implode('.', $path);
        } else if ($ct > 0) {
            return $path[0];
        } else {
            return '';
        }
    }

    private function parseArrayOfTables(TokenStream $ts): void
    {
        $this->assertNext(Lexer::T_LEFT_SQUARE_BRAKET, $ts);
        $this->assertNext(Lexer::T_LEFT_SQUARE_BRAKET, $ts);

        $fullTablePath = $this->parseKeyPath($ts);
        $fullTableName = self::pathToName($fullTablePath);
        list($tref, $match) = $this->getAOTRef($fullTableName);

        switch ($match) {
            case Parser::PATH_PART:
                $baseName = $tref->key;
                $aref = & $tref->getBaseRef(true);
                $offsetPath = array_slice($fullTablePath, $tref->depth);
                $lastIndex = count($offsetPath) - 1;
                break;
            case Parser::PATH_NONE:
                // $tref is null
                $baseName = '';
                $offsetPath = $fullTablePath;
                $aref = & $this->result;
                $lastIndex = count($offsetPath) - 1;
                break;
            case Parser::PATH_FULL:
            default:
                /* test case testParseMustParseTableArrayNest 
                 * If albums path has incremented index,
                 * and albums.song path is a full match 
                 * then need to check on the parent references
                 */
                $offsetPath = [];
                $aref = & $tref->getBaseRef(false);
                $baseName = $fullTableName;
                $lastIndex = -1; // not going to be used:
                break;
        }
        if ($lastIndex >= 0) {
            // Calculating parts of AOT for the first time. 
            // Not so good to have this sort of logic in two places.
            foreach ($offsetPath as $idx => $tableName) {
                $baseName = (strlen($baseName) > 0) ? $baseName . "." . $tableName
                            : $tableName;
                if ($idx < $lastIndex) {
                    if (!isset($aref[$tableName])) {
                        // should be the case, if AOT not registered
                        // current spec test requires implicit first member offset 0
                        $tref = new AOTRef($tref, $baseName, $tableName, true);
                        $this->registerAOT($tref);
                        $aref = & $tref->makeAOT($aref, true);
                    } else {
                        if (!is_array($aref[$tableName])) {
                            $this->errorUniqueKey($baseName);
                        }
                        $tref = new AOTRef($tref, $baseName, $tableName, true);
                        $this->registerAOT($tref);
                        $aref = & $tref->makeAOT($aref, false);
                    }
                } else {
                    if (!isset($aref[$tableName])) {
                        $tref = new AOTRef($tref, $baseName, $tableName, false);
                        $this->registerAOT($tref);
                        $aref = & $tref->makeAOT($aref, false);
                    } else {
                        $this->registerAOTError($baseName);
                    }
                }
            }
        }

        //TODO: check case of accessing intrinsic which has first table?
        if ($tref->implicit && $match == Parser::PATH_FULL) {
            $this->tableNameIsAOT($fullTableName);
        }
        // At this point $tref should be valid and 
        // and is current location for key-value insertions
        // hazard this by asking $tref for new table and reference
        $this->workArray = & $tref->newTable();

        if ($this->useKeyStore) {
            $this->currentKeyPrefix = $tref->getFullIndexName() . ".";
        }
        $this->assertNext(Lexer::T_RIGHT_SQUARE_BRAKET, $ts);
        $this->assertNext(Lexer::T_RIGHT_SQUARE_BRAKET, $ts);

        $this->parseSpaceIfExists($ts);
        $this->parseCommentIfExists($ts);
        $this->errorIfNextIsNotNewlineOrEOS($ts);
    }

    /**
     * Get and consume next token.
     * Move on if matches, else throw exception.
     * 
     * @param int $tokenId
     * @param TokenStream $ts
     * @return void
     */
    private function assertNext(int $tokenId, TokenStream $ts): void
    {
        $token = $ts->moveNext(); // token always consumed
        if ($tokenId !== $token->getId()) {
            $tokenName = Lexer::tokenName($tokenId);
            $this->unexpectedTokenError($token, "Expected \"$tokenName\".");
        }
    }

    /**
     * Combined assertNext and return token value
     * @param int $tokenId
     * @param TokenStream $ts
     * @return string
     */
    private function matchNext(int $tokenId, TokenStream $ts): string
    {
        $token = $ts->moveNext(); // token always consumed
        if ($tokenId !== $token->getId()) {
            $tokenName = Lexer::tokenName($tokenId);
            $this->unexpectedTokenError($token, "Expected \"$tokenName\".");
        }
        return $token->getValue();
    }

    private function parseCommentIfExists(TokenStream $ts): void
    {
        if ($ts->peekNext() === Lexer::T_HASH) {
            $this->parseComment($ts);
        }
    }

    private function parseSpaceIfExists(TokenStream $ts): void
    {
        if ($ts->peekNext() === Lexer::T_SPACE) {
            $ts->moveNext();
        }
    }

    private function parseCommentsInsideBlockIfExists(TokenStream $ts): void
    {
        $this->parseCommentIfExists($ts);

        while ($ts->peekNext() === Lexer::T_NEWLINE) {
            $ts->moveNext();
            $ts->skipWhile(Lexer::T_SPACE);
            $this->parseCommentIfExists($ts);
        }
    }

    private function errorUniqueKey($keyName)
    {
        $this->syntaxError(sprintf(
                        'The key "%s" has already been defined previously.', $keyName
        ));
    }
    /** 
     * Runtime check on uniqueness of key
     * Usage is controller by $this->useKeyStore
     * @param string $keyName
     */
    private function mustBeUnique(string $keyName)
    {
        if (!$this->setUniqueKey($keyName)) {
            $this->errorUniqueKey($keyName);
        }
    }
    
    /**
     * Return true if key was already set
     * Usage controlled by $this->useKeyStore
     * @param string $keyName
     * @return bool
     */
    private function setUniqueKey(string $keyName): bool
    {
        if (isset($this->keys[$keyName])) {
            return false;
        }
        $this->keys[$keyName] = true;
        return true;
    }

    private function tableNameIsAOT($keyName)
    {
        $this->syntaxError(
                sprintf('The array of tables "%s" has already been defined as previous table', $keyName)
        );
    }

    private function resetWorkArrayToResultArray(): void
    {
        if ($this->useKeyStore) {
            $this->currentKeyPrefix = '';
        }
        $this->workArray = &$this->result;
    }

    private function errorIfNextIsNotNewlineOrEOS(TokenStream $ts): void
    {
        $tokenId = $ts->peekNext();

        if ($tokenId !== Lexer::T_NEWLINE && $tokenId !== Lexer::T_EOS) {
            $this->unexpectedTokenError($ts->moveNext(), 'Expected T_NEWLINE or T_EOS.');
        }
    }

    private function unexpectedTokenError(Token $token, string $expectedMsg): void
    {
        $name = Lexer::tokenName($token->getId());
        $line = $token->getLine();
        $value = $token->getValue();
        $msg = sprintf('Syntax error: unexpected token "%s" at line %s with value "%s".', $name, $line, $value);

        if (!empty($expectedMsg)) {
            $msg = $msg . ' ' . $expectedMsg;
        }

        throw new SyntaxErrorException($msg);
    }

    private function syntaxError($msg, Token $token = null): void
    {
        if ($token !== null) {
            $name = Lexer::tokenName($token->getId());
            $line = $token->getLine();
            $value = $token->getValue();
            $tokenMsg = sprintf('Token: "%s" line: %s value "%s".', $name, $line, $value);
            $msg .= ' ' . $tokenMsg;
        }
        throw new SyntaxErrorException($msg);
    }

}

/** Keep track of relevant previous AOT declarations 
 *  Array Key is stringified AOT path
 *  Index to last table 
 *  Instead of having separate arrays for each AOT property
 *  cache them all in one indexed object
 *  No reference to parent AOTRef yet.
 *  My terminology: Name is a string; Path is array of names
 *  This class holds references to itself, so maybe the
 *  Parser should call cleanup, on each one it creates,
 *  prior to exit of parse function as a finally
 */
class AOTRef
{

    public $index = -1; // index of last table 
    public $ref = null; // base array reference, which may change
    public $depth = 0; // useful fixed value
    // If created in implicit path. This isn't used much.
    public $implicit = false;
    public $key; // full path lookup key
    public $parent; // follow to parent
    public $name; // last part of path name

    /**
     * Construct with enough details so other stuff works
     * @param ?AOTRef $parent
     * @param string $key
     * @param string $name
     * @param bool $implicit
     */

    public function __construct($parent, string $key, string $name, bool $implicit)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->name = $name;
        $this->implicit = $implicit;

        $depth = 0;
        $p = $this;
        while (!is_null($p)) {
            $depth += 1;
            $p = $p->parent;
        }
        $this->depth = $depth;
    }

    /** Factorization of create AOT logic,
     * Sometimes we want to create initial table, sometimes not
     * Initialize the internal reference
     * Always return reference to deepest
     * @param &array $aref Array where the AOT will be made
     * @param bool $makeTable
     * @return &array
     */
    public function &makeAOT(& $aref, bool $makeTable)
    {
        if (!isset($aref[$this->name])) {
            $aref[$this->name] = [];
            $this->ref = & $aref[$this->name];
            $this->index = -1;
            if ($makeTable) {
                $aref = & $this->newTable();
            } else {
                $aref = & $this->ref;
            }
        } else {
            $this->ref = & $aref[$this->name];
            // Use or check implicit flag?
            $this->index = isset($this->ref[0]) ? 0 : -1;
            if ($makeTable) {
                //  Most likely only 1, or find last one and make new?
                if ($this->index >= 0) {
                    $aref = & $this->ref[0];
                } else {
                    $aref = & $this->newTable();
                }
            } else {
                // return what is here
                if ($this->index >= 0) {
                    $aref = & $this->ref[0];
                } else {
                    $aref = & $this->ref;
                }
            }
        }
        return $aref;
    }

    /**
     * Remove potential cycles from garbage collection
     * Probably have fixed this, if it ever was a problem,
     * by using getObjPath values as temporary
     */
    public function unlink()
    {
        $this->parent = null;
    }

    /**
     * Hazard that this AOT ref location is the one we want,
     * and return a reference to a new table
     */
    public function & newTable()
    {
        $this->ref[] = [];
        $this->index++;
        return $this->ref[$this->index];
    }

    /*
     * Generate temporary array of $root to $this
     */

    private function getObjPath()
    {
        $objPath = array_fill(0, $this->depth, null);
        $idx = $this->depth - 1;
        $p = $this;
        while (!is_null($p)) {
            $objPath[$idx] = $p;
            $idx--;
            $p = $p->parent;
        }
        return $objPath;
    }

    /**
     * This is rather complicated function,
     *  and the logic was decided on partly by trial and error test cases.
     * 
     * Each AOT object holds a reference to its base
     * array, but as nested AOT are repeatedly encountered, such references becomes
     * invalid, ie points to a previous base.
     * Only if it is root object of a path does it remain all-time valid.
     * If not a partial path, do not return reference to  last indexed item of AOT,
     * because it will be set by caller.
     * If the root gets its index updated, then 
     * its likely that child indexed items don't exist, so they are made as the new
     * base reference is calculated..
     * 
     * @param bool $partial - indicates a partial path, forward last index
     */
    public function &getBaseRef(bool $partial)
    {
        $objPath = $this->getObjPath();
        $isReset = false;
        $lastIX = $this->depth - 1;
        foreach ($objPath as $idx => $obj) {
            $canDoIndex = ($partial || $idx < $lastIX) && ($obj->index >= 0);
            if ($idx == 0) {
                $result = & $obj->ref; // $root base never changes
                if ($canDoIndex) {
                    $result = & $result[$obj->index];
                }
            } else {
                if (!isset($result[$obj->name])) {
                    $result[$obj->name] = [];
                    $isReset = true;
                    $result = & $result[$obj->name];
                    $obj->ref = & $result;
                } else {
                    $result = & $result[$obj->name];
                }
                if ($isReset) {
                    if ($canDoIndex) {
                        $obj->index = 0;
                        $result[] = [];
                        $result = & $result[0];
                    } else {
                        $obj->index = -1;
                    }
                } else {
                    if ($canDoIndex) {
                        $result = & $result[$obj->index];
                    }
                }
            }
        }
        return $result;
    }

    // recursive build of full index including last index number of each parent
    public function getFullIndexName(): string
    {
        $part = ($this->index >= 0) ? "." . $this->index : '';
        $name = $this->name . $part;
        $p = $this->parent;
        while (!is_null($p)) {
            $part = ($p->index >= 0) ? "." . $p->index : '';
            $name = $p->name . $part . "." . $name;
            $p = $p->parent;
        }
        return $name;
    }

}
