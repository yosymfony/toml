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
    private $currentToken = null;
    private $data = null;
    private $result = array();
    private $keysTables = array();
    
    public function __construct()
    {
        $this->data = &$this->result;
    }
    
    /**
     * Parses TOML string into a PHP value.
     * 
     * @param string  $value        A TOML string
     * 
     * @return mixed  A PHP value
     */
    public function parse($value)
    {
        $this->lexer = new Lexer($value);
        $this->lexer->getToken();
        
        while($this->lexer->getCurrentToken()->getType() !== Lexer::TOKEN_EOF)
        {
            switch($this->lexer->getCurrentToken()->getType())
            {
                case Lexer::TOKEN_HASH:
                    $this->processComment();        // #comment
                    break;
                case Lexer::TOKEN_LBRANK:
                    $this->processTables();         // [table] or [[array of tables]]
                    break;
                case Lexer::TOKEN_LITERAL:
                    $this->processKeyValue();       // key = value
                    break;
                case Lexer::TOKEN_NEWLINE:
                    $this->currentLine++;
                    break;
            }
            
            $this->lexer->getToken();
        }
        
        return empty($this->result) ? null : $this->result;
    }
    
    private function processComment()
    {   
        while($this->isTokenValidForComment($this->lexer->getToken()))
        {
            // do nothing
        }
    }
    
    private function isTokenValidForComment(Token $token)
    {
        return $token->getType() !== Lexer::TOKEN_NEWLINE && $token->getType() !== Lexer::TOKEN_EOF;
    }
    
    private function processTables()
    {
        if($this->lexer->getNextToken()->getType() === Lexer::TOKEN_LBRANK )
        {
            $this->processArrayOfTables();
        }
        else
        {
            $this->processTable();   
        }
        
        $finalTokenType = $this->lexer->getToken()->getType();
        
        switch($finalTokenType)
        {
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
        $tablename = '';
        
        $this->lexer->getToken();
        
        while($this->isTokenValidForTablename($this->lexer->getToken()))
        {
            $tablename .= $this->lexer->getCurrentToken()->getValue();
        }
        
        $this->setArrayOfTables($tablename);
        
        $currentTokenType = $this->lexer->getCurrentToken()->getType();
        $nextTokenType = $this->lexer->getToken()->getType();
        
        if($currentTokenType !== Lexer::TOKEN_RBRANK || $nextTokenType !== Lexer::TOKEN_RBRANK)
        {
            throw new ParseException(
                'Syntax error: expected close brank',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
    }
    
    private function processTable()
    {
        $tablename = '';
        
        while($this->isTokenValidForTablename($this->lexer->getToken()))
        {
            $tablename .= $this->lexer->getCurrentToken()->getValue();
        }
        
        $this->setTable($tablename);
        
        if($this->lexer->getCurrentToken()->getType() !== Lexer::TOKEN_RBRANK)
        {
            throw new ParseException(
                'Syntax error: expected close brank',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
    }
    
    private function isTokenValidForTablename(Token $token)
    {
        if($token->getType() === Lexer::TOKEN_HASH)
        {
            $this->lexer->setCommentOpen(false);
            
            return true;
        }
        
        return $token->getType() === Lexer::TOKEN_LITERAL;
    }
    
    private function setTable($tablename)
    {
        $keyParts = explode('.', $tablename);
        $this->data = &$this->result;
        
        if(in_array($tablename, $this->keysTables))
        {
            throw new ParseException(
                sprintf('Syntax error: the key %s has already been defined', $tablename), 
                $this->currentLine, $this->lexer->getCurrentToken()->getValue());
        }
        
        $this->keysTables[] = $tablename;
        
        foreach($keyParts as $keyPart)
        {
            if(strlen($keyPart) == 0 )
            {
                throw new ParseException('The key must not be empty', $this->currentLine, $tablename);
            }
            
            if(array_key_exists($keyPart, $this->data))
            {
                if(!is_array($this->data[$keyPart]))
                {
                    throw new ParseException(
                        sprintf('Syntax error: the key %s has already been defined', $tablename),
                        $this->currentLine, $this->lexer->getCurrentToken()->getValue());
                }
            }
            else
            {
                $this->data[$keyPart] = array();
            }
            
            $this->data = &$this->data[$keyPart];
        }
    }
    
    private function setArrayOfTables($tablename)
    {
        $keyParts = explode('.', $tablename);
        $endIndex = count($keyParts) - 1;
        $this->data = &$this->result;
        $this->keysTables[] = $tablename;
        
        foreach($keyParts as $index => $keyPart)
        {
            if(strlen($keyPart) == 0 )
            {
                throw new ParseException('The key must not be empty', $this->currentLine, $tablename);
            }
            
            if(!array_key_exists($keyPart, $this->data))
            {
                $this->data[$keyPart] = array();
                $this->data[$keyPart][] = array();
            }
            else if($endIndex == $index)
            {
                $this->data[$keyPart][] = array();
            }
            
            $this->data = &$this->getLastElementRef($this->data[$keyPart]);
        }
    }
    
    private function processKeyValue()
    {
        $key = $this->lexer->getCurrentToken()->getValue();
        
        while($this->isTokenValidForKey($this->lexer->getToken()))
        {
            $key = $key . $this->lexer->getCurrentToken()->getValue();
        }
        
        if($this->lexer->getCurrentToken()->getType() !== Lexer::TOKEN_EQUAL)
        {
            throw new ParseException(
                'Syntax error: expected equal',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
        
        $key = trim($key);
        
        if(array_key_exists($key, $this->data))
        {
            throw new ParseException(
                sprintf('Syntax error: the key %s has already been defined', $key),
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
        
        switch($this->lexer->getToken()->getType())
        {
            case Lexer::TOKEN_QUOTES:
                $this->data[$key] = $this->getStringValue();
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
        return $token->getType() !== Lexer::TOKEN_EQUAL 
            && $token->getType() !== Lexer::TOKEN_NEWLINE 
            && $token->getType() !== Lexer::TOKEN_EOF;
    }
    
    private function getStringValue()
    {
        $result = "";
        
        if($this->lexer->getToken()->getType() !== Lexer::TOKEN_STRING)
        {
            throw new ParseException(
                'Syntax error: expected string',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
        
        $result = (string) $this->lexer->getCurrentToken()->getValue();
        
        if($this->lexer->getToken()->getType()  !== Lexer::TOKEN_QUOTES)
        {
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
        
        while($this->lexer->getToken()->getType() != Lexer::TOKEN_RBRANK )
        {
            switch($this->lexer->getCurrentToken()->getType())
            {
                case Lexer::TOKEN_COMMA:
                    if($dataType == null)
                    {
                        throw new ParseException('Expected data type before comma', $this->currentLine, $value);
                    }
                    break;
                case Lexer::TOKEN_QUOTES:
                    $lastType = 'string';
                    $dataType = $dataType == null ? $lastType : $dataType;
                    $value = $this->getStringValue();
                    $result[] = $value;
                    break;
                case Lexer::TOKEN_LBRANK:
                    $lastType = 'array';
                    $dataType = $dataType == null ? $lastType : $dataType;
                    $result[] = $this->getArrayValue();
                    break;
                case Lexer::TOKEN_LITERAL:
                    $value = $this->getLiteralValue();
                    $lastType =  gettype($value);
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
            
            if($lastType != $dataType)
            {
                throw new ParseException('Data types cannot be mixed in an array', $this->currentLine, $value);
            }
        }
        
        return $result;
    }
    
    private function getLiteralValue()
    {
        $token = $this->lexer->getCurrentToken();
        
        if($this->isLiteralBoolean($token))
        {
            return $token->getValue() == 'true' ? true : false;
        }
        
        if($this->isLiteralInteger($token))
        {
            return (int) $token->getValue();
        }
        
        if($this->isLiteralFloat($token))
        {
            return (float) $token->getValue();
        }
        
        if($this->isLiteralISO8601($token))
        {
            return new \Datetime($token->getValue());
        }
        
        throw new ParseException('Unknown value type', $this->currentLine, $token->getValue());
    }
    
    private function isLiteralBoolean(Token $token)
    {
        $result = false;
        
        switch($token->getValue())
        {
            case 'true':
            case 'false':
                $result = true;
        }
        
        return $result;
    }
    
    private function isLiteralInteger(Token $token)
    {
        return  preg_match('/^\-?\d*?$/', $token->getValue());
    }
    
    private function isLiteralFloat(Token $token)
    {
        return preg_match('/^\-?\d+\.\d+$/', $token->getValue());
    }
    
    private function isLiteralISO8601(Token $token)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $token->getValue());
    }
    
    private function &getLastElementRef(&$array)
    {
        end($array);
        
        return $array[key($array)]; 
    }
}