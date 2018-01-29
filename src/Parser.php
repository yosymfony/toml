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

    private $useKeyStore = true; // extra validation??
    private $uniqueKeys = [];
    // parsed result to return
    private $result = [];
    // path string to array context for key = value
    // by convention, is either empty , or set with
    // terminator of '.'
    private $currentKeyPrefix = '';
    // array context for key = value
    private $workArray;
    // array of all AOTRef using base name string key
    private $refAOT = [];
    // tables created in passing
    private $implicitTables = []; // [key] = true 
    private static $tokensNotAllowedInBasicStrings = [
        'T_ESCAPE',
        'T_NEWLINE',
        'T_EOS',
    ];
    private static $tokensNotAllowedInLiteralStrings = [
        'T_NEWLINE',
        'T_EOS',
    ];

    /**
     * Return tail of path after removing common parts
     * Use ordered arrays of keys for each path
     * Return full path if no common parts.
     * @param array $rootPath
     * @param array $childPath
     */
    /*
      private static function getChildPath(array $rootPath, array $childPath)
      {
      // Make this work for path seperator '.'
      $rlen = count($rootPath);
      $clen = count($childPath);
      $common = -1;
      if (($clen >= $rlen) && ($rlen > 0)) {
      for ($i = 0; $i < $rlen; $i++) {
      if ($rootPath[$i] == $childPath[$i]) {
      $common = $i;
      } else {
      break;
      }
      }
      if ($common >= 0) {
      // exclude common
      return array_slice($childPath, $common + 1);
      }
      }
      // nothing in common, entire path is different
      return $childPath;
      }
     */
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
        if ($ts->isNext('T_HASH')) {
            $this->parseComment($ts);
        } elseif ($ts->isNextAny(['T_QUOTATION_MARK', 'T_UNQUOTED_KEY', 'T_APOSTROPHE', 'T_INTEGER'])) {
            $this->parseKeyValue($ts);
        } elseif ($ts->isNextSequence(['T_LEFT_SQUARE_BRAKET', 'T_LEFT_SQUARE_BRAKET'])) {
            $this->parseArrayOfTables($ts);
        } elseif ($ts->isNext('T_LEFT_SQUARE_BRAKET')) {
            $this->parseTable($ts);
        } elseif ($ts->isNextAny(['T_SPACE', 'T_NEWLINE', 'T_EOS'])) {
            $ts->moveNext();
        } else {
            $msg = 'Expected T_HASH or T_UNQUOTED_KEY.';
            $this->unexpectedTokenError($ts->moveNext(), $msg);
        }
    }

    private function parseComment(TokenStream $ts): void
    {
        $this->matchNext('T_HASH', $ts);

        while (!$ts->isNextAny(['T_NEWLINE', 'T_EOS'])) {
            $ts->moveNext();
        }
    }

    private function duplicateKey(string $keyName)
    {
        $this->syntaxError("The key \"$keyName\" has already been defined previously.");
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
        $this->parseSpaceIfExists($ts);
        $this->matchNext('T_EQUAL', $ts);
        $this->parseSpaceIfExists($ts);


        if ($ts->isNext('T_LEFT_SQUARE_BRAKET')) {
            $this->workArray[$keyName] = $this->parseArray($ts);
        } elseif ($ts->isNext('T_LEFT_CURLY_BRACE')) {
            $this->parseInlineTable($ts, $keyName);
        } else {
            $this->workArray[$keyName] = $this->parseSimpleValue($ts)->value;
        }

        if (!$isFromInlineTable) {
            $this->parseSpaceIfExists($ts);
            $this->parseCommentIfExists($ts);
            $this->errorIfNextIsNotNewlineOrEOS($ts);
        }
    }

    private function parseKeyName(TokenStream $ts): string
    {
        if ($ts->isNext('T_UNQUOTED_KEY')) {
            return $this->matchNext('T_UNQUOTED_KEY', $ts);
        }
        if ($ts->isNext('T_APOSTROPHE')) {
            return $this->parseLiteralString($ts);
        }
        if ($ts->isNext('T_INTEGER')) {
            // integers can be keys, but only as a string (Not a limitation of php)
            return (string) $this->parseInteger($ts);
        }
        return $this->parseBasicString($ts);
    }

    /**
     * @return object An object with two public properties: value and type.
     */
    private function parseSimpleValue(TokenStream $ts)
    {
        if ($ts->isNext('T_BOOLEAN')) {
            $type = 'boolean';
            $value = $this->parseBoolean($ts);
        } elseif ($ts->isNext('T_INTEGER')) {
            $type = 'integer';
            $value = $this->parseInteger($ts);
        } elseif ($ts->isNext('T_FLOAT')) {
            $type = 'float';
            $value = $this->parseFloat($ts);
        } elseif ($ts->isNext('T_QUOTATION_MARK')) {
            $type = 'string';
            $value = $this->parseBasicString($ts);
        } elseif ($ts->isNext('T_3_QUOTATION_MARK')) {
            $type = 'string';
            $value = $this->parseMultilineBasicString($ts);
        } elseif ($ts->isNext('T_APOSTROPHE')) {
            $type = 'string';
            $value = $this->parseLiteralString($ts);
        } elseif ($ts->isNext('T_3_APOSTROPHE')) {
            $type = 'string';
            $value = $this->parseMultilineLiteralString($ts);
        } elseif ($ts->isNext('T_DATE_TIME')) {
            $type = 'datetime';
            $value = $this->parseDatetime($ts);
        } else {
            $this->unexpectedTokenError(
                    $ts->moveNext(), 'Expected boolean, integer, long, string or datetime.'
            );
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
        return $this->matchNext('T_BOOLEAN', $ts) == 'true' ? true : false;
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
        $this->matchNext('T_QUOTATION_MARK', $ts);

        $result = '';

        while (!$ts->isNext('T_QUOTATION_MARK')) {
            if ($ts->isNextAny(self::$tokensNotAllowedInBasicStrings)) {
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            $value = $ts->isNext('T_ESCAPED_CHARACTER') ? $this->parseEscapedCharacter($ts)
                        : $ts->moveNext()->getValue();
            $result .= $value;
        }

        $this->matchNext('T_QUOTATION_MARK', $ts);

        return $result;
    }

    private function parseMultilineBasicString(TokenStream $ts): string
    {
        $this->matchNext('T_3_QUOTATION_MARK', $ts);

        $result = '';

        if ($ts->isNext('T_NEWLINE')) {
            $ts->moveNext();
        }

        while (!$ts->isNext('T_3_QUOTATION_MARK')) {
            if ($ts->isNext('T_EOS')) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_QUOTATION_MARK".');
            }

            if ($ts->isNext('T_ESCAPE')) {
                $ts->skipWhileAny(['T_ESCAPE', 'T_SPACE', 'T_NEWLINE']);
            }

            if ($ts->isNext('T_EOS')) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_QUOTATION_MARK".');
            }

            if (!$ts->isNext('T_3_QUOTATION_MARK')) {
                $value = $ts->isNext('T_ESCAPED_CHARACTER') ? $this->parseEscapedCharacter($ts)
                            : $ts->moveNext()->getValue();
                $result .= $value;
            }
        }

        $this->matchNext('T_3_QUOTATION_MARK', $ts);

        return $result;
    }

    private function parseLiteralString(TokenStream $ts): string
    {
        $this->matchNext('T_APOSTROPHE', $ts);

        $result = '';

        while (!$ts->isNext('T_APOSTROPHE')) {
            if ($ts->isNextAny(self::$tokensNotAllowedInLiteralStrings)) {
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            $result .= $ts->moveNext()->getValue();
        }

        $this->matchNext('T_APOSTROPHE', $ts);

        return $result;
    }

    private function parseMultilineLiteralString(TokenStream $ts): string
    {
        $this->matchNext('T_3_APOSTROPHE', $ts);

        $result = '';

        if ($ts->isNext('T_NEWLINE')) {
            $ts->moveNext();
        }

        while (!$ts->isNext('T_3_APOSTROPHE')) {
            if ($ts->isNext('T_EOS')) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_APOSTROPHE".');
            }

            $result .= $ts->moveNext()->getValue();
        }

        $this->matchNext('T_3_APOSTROPHE', $ts);

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
        $date = $this->matchNext('T_DATE_TIME', $ts);

        return new \Datetime($date);
    }

    private function parseArray(TokenStream $ts): array
    {
        $result = [];
        $leaderType = '';

        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);

        while (!$ts->isNext('T_RIGHT_SQUARE_BRAKET')) {
            $ts->skipWhileAny(['T_NEWLINE', 'T_SPACE']);
            $this->parseCommentsInsideBlockIfExists($ts);

            if ($ts->isNext('T_LEFT_SQUARE_BRAKET')) {
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

            $ts->skipWhileAny(['T_NEWLINE', 'T_SPACE']);
            $this->parseCommentsInsideBlockIfExists($ts);

            if (!$ts->isNext('T_RIGHT_SQUARE_BRAKET')) {
                $this->matchNext('T_COMMA', $ts);
            }

            $ts->skipWhileAny(['T_NEWLINE', 'T_SPACE']);
            $this->parseCommentsInsideBlockIfExists($ts);
        }

        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);

        return $result;
    }

    private function parseInlineTable(TokenStream $ts, string $keyName): void
    {
        $this->matchNext('T_LEFT_CURLY_BRACE', $ts);
        if ($this->useKeyStore) {
            $priorcurrentKeyPrefix = $this->currentKeyPrefix;
        }
        $priorWorkArray = &$this->workArray;

        $this->addArrayKeyToWorkArray($keyName);
        if ($this->useKeyStore) {
            $this->currentKeyPrefix = $this->currentKeyPrefix . $keyName . ".";
        }
        $this->parseSpaceIfExists($ts);

        if (!$ts->isNext('T_RIGHT_CURLY_BRACE')) {
            $this->parseKeyValue($ts, true);
            $this->parseSpaceIfExists($ts);
        }

        while ($ts->isNext('T_COMMA')) {
            $ts->moveNext();

            $this->parseSpaceIfExists($ts);
            $this->parseKeyValue($ts, true);
            $this->parseSpaceIfExists($ts);
        }

        $this->matchNext('T_RIGHT_CURLY_BRACE', $ts);
        if ($this->useKeyStore) {
            $this->currentKeyPrefix = $priorcurrentKeyPrefix;
        }
        $this->workArray = &$priorWorkArray;
    }

    private function parseKeyPath(TokenStream $ts)
    {
        $fullTablePath = [];
        $fullTablePath[] = $this->parseKeyName($ts);
        while ($ts->isNext('T_DOT')) {
            $ts->moveNext();
            $fullTablePath[] = $this->parseKeyName($ts);
        }
        return $fullTablePath;
    }

    private function registerAOTError($key)
    {
        throw new \Exception('Parser Array of Table exists but not registered - ' . $key);
    }

    private function parseTable(TokenStream $ts): void
    {
        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);

        $fullTablePath = $this->parseKeyPath($ts);
        $fullTableName = self::pathToName($fullTablePath);
        // get AOT context, if any
        list($tref, $match) = $this->getAOTRef($fullTableName);

        switch ($match) {
            case Parser::PATH_PART:
                $basePath = $tref->getBasePath();
                $baseName = $tref->getFullIndexName();
                $offsetPath = array_slice($fullTablePath, count($basePath));
                $aref = & $tref->ref[$tref->index];

                $lastIndex = count($offsetPath) - 1;
                $doImplicit = false;
                break;
            case Parser::PATH_NONE:
                // root name space
                $basePath = [];
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
                    if (isset($this->uniqueKeys[$baseName])) {
                        $this->duplicateKey($baseName);
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

        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);

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
        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);
        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);

        $fullTablePath = $this->parseKeyPath($ts);
        $fullTableName = self::pathToName($fullTablePath);
        list($tref, $match) = $this->getAOTRef($fullTableName);

        switch ($match) {
            case Parser::PATH_PART:
                $basePath = $tref->getBasePath();
                $baseName = $tref->key;
                $aref = & $tref->getBaseRef(true);
                $offsetPath = array_slice($fullTablePath, count($basePath));
                $lastIndex = count($offsetPath) - 1;
                break;
            case Parser::PATH_NONE:
                // $tref is null
                $basePath = [];
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
                $basePath = $tref->getBasePath();
                $baseName = $fullTableName;
                $lastIndex = -1; // not going to be used:
                break;
        }


        if ($lastIndex >= 0) {
            foreach ($offsetPath as $idx => $tableName) {
                $baseName = (strlen($baseName) > 0) ? $baseName . "." . $tableName
                            : $tableName;
                if ($idx < $lastIndex) {
                    // current spec test requires implicit first member offset 0
                    // TOML is intuitive, and so is the spec.

                    if (!isset($aref[$tableName])) {
                        // should be the case, since AOT not registered
                        $aref[$tableName] = [];
                        $aref = & $aref[$tableName];
                        // register the new implicit path
                        // Paths don't care (yet) about AOT offsets
                        $aref[] = []; // set a first member
                        $tref = new AOTRef($tref, $baseName, $tableName, $aref, true);
                        $this->registerAOT($tref);
                        $aref = &$aref[0];
                    } else {
                        // not expecting this
                        // in passing, still need to register as AOT as implicit
                        if (!is_array($aref[$tableName])) {
                            $this->errorUniqueKey($baseName);
                        }
                        //TODO: also must not have $aref[0] set here
                        $aref = & $aref[$tableName];
                        // testParseMustParseATableAndArrayOfTables
                        // according to this test , no AOT is set at this level
                        //$aref[] = []; // set a first table member
                        $tref = new AOTRef($tref, $baseName, $tableName, $aref, true);
                        $this->registerAOT($tref);
                        //$this->registerAOTError($baseName);
                    }
                } else {
                    if (!isset($aref[$tableName])) {
                        $aref[$tableName] = [];
                        $aref = & $aref[$tableName];
                        $tref = new AOTRef($tref, $baseName, $tableName, $aref, false);
                        $this->registerAOT($tref);
                    } else {
                        $this->registerAOTError($baseName);
                    }
                }
            }
        }

        // Always add another table?  Not checking actual count?
        //TODO: check case of accessing intrinsic which has first table?
        if ($tref->implicit && $match == Parser::PATH_FULL) {
            $this->tableNameIsAOT($fullTableName);
        }


        $pos = $tref->index + 1;
        $tref->index = $pos;
        $aref[] = [];
        $this->workArray = & $aref[$pos];

        if ($this->useKeyStore) {
            $this->currentKeyPrefix = $tref->getFullIndexName() . ".";
        }
        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);
        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);

        $this->parseSpaceIfExists($ts);
        $this->parseCommentIfExists($ts);
        $this->errorIfNextIsNotNewlineOrEOS($ts);
    }

    private function matchNext(string $tokenName, TokenStream $ts): string
    {
        if (!$ts->isNext($tokenName)) {
            $this->unexpectedTokenError($ts->moveNext(), "Expected \"$tokenName\".");
        }

        return $ts->moveNext()->getValue();
    }

    private function parseSpaceIfExists(TokenStream $ts): void
    {
        if ($ts->isNext('T_SPACE')) {
            $ts->moveNext();
        }
    }

    private function parseCommentIfExists(TokenStream $ts): void
    {
        if ($ts->isNext('T_HASH')) {
            $this->parseComment($ts);
        }
    }

    private function parseCommentsInsideBlockIfExists(TokenStream $ts): void
    {
        $this->parseCommentIfExists($ts);

        while ($ts->isNext('T_NEWLINE')) {
            $ts->moveNext();
            $ts->skipWhile('T_SPACE');
            $this->parseCommentIfExists($ts);
        }
    }

    private function errorUniqueKey($keyName)
    {
        $this->syntaxError(sprintf(
                        'The key "%s" has already been defined previously.', $keyName
        ));
    }

    private function mustBeUnique(string $keyName)
    {
        if (!$this->setUniqueKey($keyName)) {
            $this->errorUniqueKey($keyName);
        }
    }

    private function setUniqueKey(string $keyName): bool
    {
        if (isset($this->uniqueKeys[$keyName])) {
            return false;
        }
        $this->uniqueKeys[$keyName] = true;
        return true;
    }

    private function tableNameIsAOT($keyName)
    {
        $this->syntaxError(
                sprintf('The array of tables "%s" has already been defined as previous table', $keyName)
        );
    }

    private function isNecesaryToProcessImplicitKeyNameParts(array $keynameParts): bool
    {
        if (count($keynameParts) > 1) {
            array_pop($keynameParts);
            $implicitArrayOfTablesName = implode('.', $keynameParts);

            if (in_array($implicitArrayOfTablesName, $this->arrayOfTablekeyCounters)
                    === false) {
                return true;
            }
        }

        return false;
    }

    private function addArrayKeyToWorkArray(string $keyName): void
    {
        if (isset($this->workArray[$keyName]) === false) {
            $this->workArray[$keyName] = [];
        }
        $this->workArray = &$this->workArray[$keyName];
    }

    private function resetWorkArrayToResultArray(): void
    {
        $this->currentKeyPrefix = '';
        $this->workArray = &$this->result;
    }

    private function errorIfNextIsNotNewlineOrEOS(TokenStream $ts): void
    {
        if (!$ts->isNextAny(['T_NEWLINE', 'T_EOS'])) {
            $this->unexpectedTokenError($ts->moveNext(), 'Expected T_NEWLINE or T_EOS.');
        }
    }

    private function unexpectedTokenError(Token $token, string $expectedMsg): void
    {
        $name = $token->getName();
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
            $name = $token->getName();
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

    public $key; // full path lookup key
    public $name; // last part of path name
    public $ref; // base array reference
    public $index; // index of last table 
    public $implicit; // indicates implicit creation: part of explicit path
    public $parent; // follow to parent

    public function __construct($parent, string $key, string $name, array & $ref, bool $implicit)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->name = $name;
        $this->ref = & $ref;
        // cannot use count here!
        $this->index = isset($ref[0]) ? 0 : -1;
        $this->implicit = $implicit;
        $this->objPath = $this->calcObjPath();
    }

    /**
     * Remove potential cycles from garbage collection
     */
    public function unlink()
    {
        unset($this->objPath);
        $this->parent = null;
    }

    /**
     * Get this object and its parents in reverse order so root is first
     * and this is last. Assumes parents are fixed at construction time
     * @return array
     */
    private function calcObjPath(): array
    {
        $op = [];
        $p = $this;
        while (!is_null($p)) {
            $op[] = $p;
            $p = $p->parent;
        }
        return array_reverse($op);
    }

    /**
     * Each object holds a reference to its base
     * array, but if nested, that reference becomes
     * invalid, ie points to a previous base.
     * Only the root object remains valid, I think.
     * Don't return reference to  last indexed item of this,
     * because it may be updated.
     * If the root got updated, then 
     * its likely that child items don't exist, so make them.
     * Also the index values become invalid
     * @param bool $partial - indicates can index last item.
     */
    public function &getBaseRef(bool $partial)
    {
        $lastIX = count($this->objPath) - 1;
        $isReset = false;
        foreach ($this->objPath as $idx => $obj) {
            $canDoIndex = ($partial || $idx < $lastIX) && ($obj->index >= 0);
            if ($idx == 0) {
                $result = & $obj->ref; // maybe the only thing reliable
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

    public function getBasePath(): array
    {
        return array_map(function($obj) {
            return $obj->name;
        }, $this->objPath);
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
