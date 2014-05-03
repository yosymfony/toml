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
        return Lexer::TOKEN_NEWLINE !== $token->getType() && Lexer::TOKEN_EOF !== $token->getType();
    }
    
    private function processTables()
    {
        if(Lexer::TOKEN_LBRANK === $this->lexer->getNextToken()->getType())
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
        $key = '';
        
        $this->lexer->getToken();
        
        while($this->isTokenValidForTablename($this->lexer->getToken()))
        {
            $key .= $this->lexer->getCurrentToken()->getValue();
        }
        
        $this->setArrayOfTables($key);
        
        $currentTokenType = $this->lexer->getCurrentToken()->getType();
        $nextTokenType = $this->lexer->getToken()->getType();
        
        if(Lexer::TOKEN_RBRANK !== $currentTokenType || Lexer::TOKEN_RBRANK !== $nextTokenType)
        {
            throw new ParseException(
                'Syntax error: expected close brank',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
    }
    
    private function processTable()
    {
        $key = '';
        
        while($this->isTokenValidForTablename($this->lexer->getToken()))
        {
            $key .= $this->lexer->getCurrentToken()->getValue();
        }
        
        $this->setTable($key);
        
        if(Lexer::TOKEN_RBRANK !== $this->lexer->getCurrentToken()->getType())
        {
            throw new ParseException(
                'Syntax error: expected close brank',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
    }
    
    private function isTokenValidForTablename(Token $token)
    {
        if(Lexer::TOKEN_HASH === $token->getType())
        {
            $this->lexer->setCommentOpen(false);
            
            return true;
        }
        
        return Lexer::TOKEN_LITERAL === $token->getType();
    }
    
    private function setTable($key)
    {
        $nameParts = explode('.', $key);
        $this->data = &$this->result;
        
        if(in_array($key, $this->tableNames) || in_array($key, $this->arrayTableNames))
        {
            throw new ParseException(
                sprintf('Syntax error: the table %s has already been defined', $key), 
                $this->currentLine, $this->lexer->getCurrentToken()->getValue());
        }
        
        $this->tableNames[] = $key;
        
        foreach($nameParts as $namePart)
        {
            if(0 == strlen($namePart))
            {
                throw new ParseException('The name of the table must not be empty', $this->currentLine, $key);
            }
            
            if(array_key_exists($namePart, $this->data))
            {
                if(!is_array($this->data[$namePart]))
                {
                    throw new ParseException(
                        sprintf('Syntax error: the table %s has already been defined', $key),
                        $this->currentLine, $this->lexer->getCurrentToken()->getValue());
                }
            }
            else
            {
                $this->data[$namePart] = array();
            }
            
            $this->data = &$this->data[$namePart];
        }
    }
    
    private function setArrayOfTables($key)
    {
        $nameParts = explode('.', $key);
        $endIndex = count($nameParts) - 1;
        
        if(true == $this->isTableImplicit($nameParts))
        {
            $this->addInvalidArrayTablesName($nameParts);
            $this->setTable($key);
            
            return;
        }
        
        if(in_array($key, $this->invalidArrayTablesName))
        {
            throw new ParseException(
                sprintf('Syntax error: the array of tables %s has already been defined as previous table', $key), 
                $this->currentLine, $this->lexer->getCurrentToken()->getValue());
        }
        
        $this->data = &$this->result;
        $this->arrayTableNames[] = $key;
        
        foreach($nameParts as $index => $namePart)
        {
            if(0 == strlen($namePart))
            {
                throw new ParseException('The key must not be empty', $this->currentLine, $key);
            }
            
            if(false == array_key_exists($namePart, $this->data))
            {
                $this->data[$namePart] = array();
                $this->data[$namePart][] = array();
            }
            else if($endIndex == $index)
            {
                $this->data[$namePart][] = array();
            }
            
            $this->data = &$this->getLastElementRef($this->data[$namePart]);
        }
    }
    
    private function processKeyValue()
    {
        $key = $this->lexer->getCurrentToken()->getValue();
        
        while($this->isTokenValidForKey($this->lexer->getToken()))
        {
            $key = $key . $this->lexer->getCurrentToken()->getValue();
        }
        
        if(Lexer::TOKEN_EQUAL !== $this->lexer->getCurrentToken()->getType())
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
        return Lexer::TOKEN_EQUAL  !== $token->getType() 
            && Lexer::TOKEN_NEWLINE !== $token->getType()
            && Lexer::TOKEN_EOF !== $token->getType();
    }
    
    private function getStringValue()
    {
        $result = "";
        
        if(Lexer::TOKEN_STRING !== $this->lexer->getToken()->getType())
        {
            throw new ParseException(
                'Syntax error: expected string',
                $this->currentLine,
                $this->lexer->getCurrentToken()->getValue());
        }
        
        $result = (string) $this->lexer->getCurrentToken()->getValue();
        
        if(Lexer::TOKEN_QUOTES !== $this->lexer->getToken()->getType())
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
        
        while(Lexer::TOKEN_RBRANK != $this->lexer->getToken()->getType())
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
    
    private function isTableImplicit(array $tablenameParts)
    {
        if(count($tablenameParts) > 1)
        {
            array_pop($tablenameParts);
            
            $tablename = implode('.', $tablenameParts);
            
            if(false == in_array($tablename, $this->arrayTableNames))
            {
                return true;
            }
        }
        
        return false;
    }
    
    private function addInvalidArrayTablesName(array $tablenameParts)
    {
        foreach($tablenameParts as $part)
        {
            $this->invalidArrayTablesName[] = implode('.', $tablenameParts);
            array_pop($tablenameParts);
        }
    }
}