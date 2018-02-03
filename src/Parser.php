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
    private $currentKeyPrefix = ''; //usage controlled by $useKeyStore
    // array context for key = value
    // parsed result to return
    private $result = [];
    // path string to array context for key = value
    // by convention, is either empty , or set with
    // terminator of '.'

    private $workArray;
    // array of all AOTRef using base name string key
    private $refAOT = []; // deprecating this version now
    //
    private $allPaths = [];  // blend of all registered table paths
    private $pathFull = null;  // instance of last fully specificied path
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
                $this->parseTablePath($ts);
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
        $tokenId = $ts->peekNext();
        if ($tokenId != Lexer::T_HASH) {
            $this->throwTokenError($ts->moveNext(), $tokenId);
        }
        while(true) {
            $tokenId = $ts->movePeekNext();
            if ($tokenId === Lexer::T_NEWLINE || $tokenId === Lexer::T_EOS) {
                break;
            }
        }  
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
           $this->finishLine($ts);
        }
    }

    private function parseKeyName(TokenStream $ts, bool $stripQuote = true): string
    {
        $token = $ts->peekNext();
        switch ($token) {
            case Lexer::T_UNQUOTED_KEY:
                return $this->matchNext(Lexer::T_UNQUOTED_KEY, $ts);
            case Lexer::T_QUOTATION_MARK:
                return $this->parseBasicString($ts, $stripQuote);
            case Lexer::T_APOSTROPHE:
                return $this->parseLiteralString($ts, $stripQuote);
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

    /** In path parsing, we may want to keep quotes, because they can be used
     *  to enclose a '.' as a none separator. 
     * @param TokenStream $ts
     * @param type $stripQuote
     * @return string
     */
    private function parseBasicString(TokenStream $ts, $stripQuote = true): string
    {
        $this->assertNext(Lexer::T_QUOTATION_MARK, $ts);

        $result = $stripQuote ? '' : "\"";

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

        if (!$stripQuote) {
            $result .= "\"";
        }
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

    /**
     * 
     * @param TokenStream $ts
     * @param bool $stripQuote
     * @return string
     */
    private function parseLiteralString(TokenStream $ts, bool $stripQuote = true): string
    {
        $this->assertNext(Lexer::T_APOSTROPHE, $ts);

        $result = $stripQuote ? '' : "'";
        $tokenId = $ts->peekNext();

        while ($tokenId !== Lexer::T_APOSTROPHE) {
            if (($tokenId === Lexer::T_NEWLINE) || ($tokenId === Lexer::T_EOS)) {
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            $result .= $ts->moveNext()->getValue();
            $tokenId = $ts->peekNext();
        }
        if (!$stripQuote) {
            $result .= "'";
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

    /**
     * Nothing more of interest on the line,
     * anything besides a comment is an error
     */
    private function finishLine(TokenStream $ts) : void {
            $this->skipIfSpace($ts);
            $this->parseCommentIfExists($ts);
            $this->errorIfNextIsNotNewlineOrEOS($ts);     
    }
    /** Return a PathFull object from the table path.
     *  The parse is the easy part. What to do with it is hard.
     * @param TokenStream $ts
     * @return array [PathFull,$lastMatch]
     */
    private function parsePathFull(TokenStream $ts)
    {
        $pathToken = $ts->moveNext();
        if ($pathToken->getId() != Lexer::T_LEFT_SQUARE_BRAKET)
        {
            $this->tablePathError("Path start [ expected", $pathToken);
        }

        $isAOT = false;

        $parts = [];
        $dotCount = 0;
        $hasAOT = false;
        $hasTables = false;

        while (true) {
            $tokenId = $ts->peekNext();
            switch ($tokenId) {
                case Lexer::T_HASH:
                    $this->tablePathError("Unexpected '#' in path", $ts->moveNext());
                    break;
                case Lexer::T_EQUAL:
                    $this->tablePathError("Unexpected '=' in path", $ts->moveNext());
                    break;
                case Lexer::T_SPACE:
                    $ts->moveNext();
                    break;
                case Lexer::T_NEWLINE:
                    $this->tablePathError("New line in unfinished path", $ts->moveNext());
                    break;
                case Lexer::T_RIGHT_SQUARE_BRAKET:
                    $ts->moveNext();
                    if ($isAOT) {
                        $isAOT = false;
                        break;
                    } else {
                        break 2;
                    }
                case Lexer::T_LEFT_SQUARE_BRAKET:
                    $token = $ts->moveNext();
                    if ($dotCount < 1 && count($parts) > 0) {
                        $this->tablePathError("Expected a '.' after path key", $token);
                    }
                    if ($isAOT) {
                        // one too many
                        $this->$token("Too many consecutive [ in path", $token);
                    }
                    $isAOT = true;
                    
                    break;
                case Lexer::T_DOT;
                    $token = $ts->moveNext();
                    if ($dotCount === 1) {
                        $this->tablePathError("Found '..' in path", $token);
                    }
                    $dotCount += 1;
                    break;
                default:
                    if (!$isAOT) {
                        $hasTables = true;
                    }
                    else {
                        $hasAOT = true;
                    }
                    if ($dotCount < 1 && count($parts) > 0) {
                        $this->tablePathError("Expected a '.' after path key", $ts->moveNext());
                    }
                    $dotCount = 0;
                    $parts[] = new PathPart($this->parseKeyName($ts, false), $isAOT);
                    break;
            }
        }
        if ($dotCount > 0) {
            // one too many
            $this->syntaxError("Extra path '.'", $pathToken);
        }
        $findKey = '';
        $lastMatch = '';
        foreach ($parts as $idx => $p) {
            if ($idx > 0) {
                $findKey .= '.';
            } 
            $findKey .= $p->name;
            if (isset($this->allPaths[$findKey])) {
                $lastMatch = $findKey;
            }
        }
        $pf = new PathFull();
        $pf->key = $findKey;
        $pf->parts = $parts;
        $pf->line = $pathToken->getLine();
        $pf->setKind($hasTables, $hasAOT);
        if ($pf->kind === PathFull::PF_EMPTY) {
            $this->tablePathError("Path cannot be empty", $pathToken);
        }
        $pf->last = $parts[count($parts) - 1];
        return [$pf, $lastMatch];
    }

    private function tablePathError($msg,Token $token=null) {
        if (!is_null($token)) {
            $msg .= ", Line " . $token->getLine();
        }
        throw new SyntaxErrorException($msg);
    }
    
    private function tablePathClash($orig, $pf) {
        $msg = "Table path [" . $pf->key . "] at line " . $pf->line 
                . " interferes with path at line " . $orig->line;
        throw new SyntaxErrorException($msg);
    }
    /**
     * @param TokenStream $ts
     * For mixed AOT and table paths, some rules to be followed, or else.
     * For existing paths, a new AOT-T is created if end of path is AOT
     * New path dynamic AOT segments always create an initial AOT-T
     * Existing AOT segments ended with a Table Path are unaltered.
     * 
     * Traverse the path parts and adjust workArray
     * 
     * Would be nice to have a token that says "I am relative" to last path,
     * //TODO: Try relative paths and replacement. Means more tokens to LEXER
     * If first character is a "plus" + , it extends and replaces last path
     * If first character is a "minus" - , it extends last path without replacement
     * 
     */
    
    
    private function parseTablePath(TokenStream $ts): void
    {
        list($pf, $lastMatch) = $this->parsePathFull($ts);

        $this->finishLine($ts);
        
        if (empty($lastMatch) || ($lastMatch !== $pf->key)) {
            //$match = Parser::PATH_NONE;
            if (!empty($lastMatch)) {
                $orig = $this->allPaths[$lastMatch];
                $hasAOT = false;
                $hasTables = false;
                $origParts = $orig->parts;
                $origCt = count($origParts);
                foreach($pf->parts as $idx => $part) {
                    if ($idx < $origCt) {
                        if ($origParts[$idx]->isAOT) {
                            $part->isAOT = true;
                            $hasAOT = true;
                        }
                        else {
                            $hasTables = true;
                            if ($part->isAOT) {
                                // lenient here?
                                $part->isAOT = false;
                                //$this->tablePathClash($orig, $pf);
                            }
                        }
                    }
                    else {
                        if ($part->isAOT) {
                            $hasAOT = true;
                        }
                        else {
                            $hasTables = true;
                        }
                    }
                }
                // rewrite kind
                $pf->setKind($hasTables,$hasAOT);
            }
            $this->allPaths[$pf->key] = $pf;
        } else {
            // Not all existing test cases can be satisfied here
            // A exact base path match.
            $orig = $this->allPaths[$lastMatch];
            if ($orig->kind === PathFull::PF_TABLE && $pf->kind === PathFull::PF_TABLE) {
                // obvious duplicate action.
                $this->tablePathClash($orig, $pf);
            }
            elseif ($pf->kind === PathFull::PF_TABLE) {
                // original has some AOT
                if ($orig->kind === PathFull::PF_AOT) {
                    $this->tablePathClash($orig, $pf);
                }
                elseif ($orig->kind === PathFull::PF_MIXED && (!$orig->last->isAOT)) {
                    // let it go through for now
                }
            }
            $pf = $orig; // take as request to reuse original
        } 

        $aref = & $this->result;
        $lastIndex = count($pf->parts) - 1;
        $prefix = ''; // for key prefix
        
        foreach ($pf->parts as $idx => $part) {
            $pname = $part->name;
            $slen = strlen($pname);
            if (strlen($pname) >= 2) {
                $quote = substr($pname,0,1);
                if ($quote === "'" || $quote === '"') {
                    $pname = substr($pname,1,$slen-2);
                }
            }
            $prefix = ($idx===0) ? $pname : $prefix .= '.' . $pname;
            
            $isNewPart = !isset($aref[$pname]);
            if ($isNewPart) {
                $aref[$pname] = [];
                $aref = & $aref[$pname];
            } else {
                $aref = & $aref[$pname];
                if (!is_array($aref)) {
                    $this->errorUniqueKey($prefix);
                }
            }
            
            if ($part->isAOT) {
                // important that keys do not share with AOT base
                $lastTableIndex = count($aref);
                
                if ($idx == $lastIndex || $isNewPart) {
                    //extend
                    $aref[] = [];
                    $aref = &$aref[$lastTableIndex];
                }
                else { // not a new part
                    $lastTableIndex--;
                    $aref = &$aref[$lastTableIndex];
                }
                $prefix .= '[' . (string) $lastTableIndex . ']';
            }
            
        }
        $this->currentKeyPrefix = $prefix . "."; 
        $this->workArray = & $aref;
    }
    private function throwTokenError($token, int $expectedId) {
        $tokenName = Lexer::tokenName($expectedId);
        $this->unexpectedTokenError($token, "Expected \"$tokenName\".");
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
            $this->throwTokenError($token, $tokenId);
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
            $this->throwTokenError($token, $tokenId);
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

class PathPart
{

    public $name;
    public $isAOT;

    public function __construct(string $name, bool $isAOT)
    {
        $this->name = $name;
        $this->isAOT = $isAOT;
    }

}

class PathFull
{

    const PF_EMPTY = 0;
    const PF_TABLE = 1;
    const PF_AOT = 2;
    const PF_MIXED = 3;

    public $key;
    public $parts;
    public $kind;
    public $last;
    public $line;
    
    public function setKind(bool $hasTables, bool $hasAOT) : void
    {
        if ($hasTables && $hasAOT) {
            $this->kind = PathFull::PF_MIXED;
        }
        elseif ($hasAOT) {
            $this->kind = PathFull::PF_AOT;
        }
        elseif ($hasTables) {
            $this->kind = PathFull::PF_TABLE;
        }
        else {
            $this->kind = PathFull::PF_EMPTY;
        }
    }

}

