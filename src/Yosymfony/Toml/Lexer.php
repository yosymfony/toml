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

use Yosymfony\Toml\Exception\LexerException;

/**
 * Lexer for Toml strings.
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Lexer
{
    const TOKEN_LBRANK = 0;
    const TOKEN_RBRANK = 1;
    const TOKEN_NEWLINE = 2;
    const TOKEN_COMMA = 3;
    const TOKEN_QUOTES = 4;
    const TOKEN_STRING = 5;
    const TOKEN_EQUAL = 6;
    const TOKEN_COMMENT = 7;
    const TOKEN_DASH = 8;
    const TOKEN_DOT = 9;
    const TOKEN_EOF = 10;
    const TOKEN_HASH = 11;
    const TOKEN_LITERAL = 12;
    
    private static $tokensNames = array(
        'LBRACK',
        'RBRACK',
        'NEWLINE',
        'COMMA',
        'QUOTES',
        'STRING',
        'EQUAL',
        'COMMENT',
        'DASH',
        'DOT',
        'EOF',
        'HASH',
        'LITERAL',
        );
    
    private $input;
    private $position = -1;
    private $current;
    private $currentToken;
    private $backToken;
    private $inputLength = 0;
    
    private $beginQuoteOpen = false;
    private $endQuoteOpen = false;
    private $commentOpen = false;
    
    public function __construct($input)
    {
        $this->input = $input;
        $this->inputLength = strlen($input);
    }
    
    /**
     * Get token from Toml string
     * 
     * @return Token 
     * 
     * @throws LexerException if a special character is not valid 
     */
    public function getToken()
    {
        $this->backToken = $this->currentToken;
        $this->currentToken = $this->consumeToken();
        
        return $this->currentToken;
    }
    
    /**
     * Get the current token of the stream
     * 
     * @return Token or null
     */
    public function getCurrentToken()
    {
        return $this->currentToken;
    }
    
    /**
     * Get the next token of the stream.
     * This operation does not alter the current pointer of the stream.
     * 
     * @return Token or null
     */
    public function getNextToken()
    {
        $currentPosition = $this->position;
        $nextToken = $this->consumeToken();
        $subPositions = $this->position - $currentPosition;
        $this->goBack($subPositions);
        
        return $nextToken;
    }
    
    /**
     * Get the back token of the stream.
     * This operation does not alter the current pointer of the stream.
     * 
     * @return Token or null
     */
    public function getBackToken()
    {
        return $this->backToken;
    }
    
    /**
     * Set the Comment Open status
     * 
     * @param bool $value
     */
    public function setCommentOpen($value)
    {
        $this->commentOpen = (bool) $value;
    }
    
    private function getCurrent()
    {
        return $this->current;
    }
    
    private function consumeToken()
    {
        if($this->beginQuoteOpen)
        {
            $this->consume();
            
            return $this->getTokenString();
        }
        
        if($this->commentOpen)
        {
            $this->consume();
            
            return $this->getTokenComment();
        }
        
        while($this->consume())
        {
            switch($this->getCurrent())
            {
                case ' ':
                case "\t":
                case "\r":
                    continue;
                case "\n":
                    return new Token(self::TOKEN_NEWLINE, $this->getNemo(self::TOKEN_NEWLINE), '');
                case '[':
                    return new Token(self::TOKEN_LBRANK, $this->getNemo(self::TOKEN_LBRANK), $this->getCurrent());
                case ']':
                    return new Token(self::TOKEN_RBRANK, $this->getNemo(self::TOKEN_RBRANK), $this->getCurrent());
                case '=':
                    return new Token(self::TOKEN_EQUAL, $this->getNemo(self::TOKEN_EQUAL), $this->getCurrent());
                case '#':
                    $this->commentOpen = true;
                    return new Token(self::TOKEN_HASH, $this->getNemo(self::TOKEN_HASH), $this->getCurrent());
                case ',':
                    return new Token(self::TOKEN_COMMA, $this->getNemo(self::TOKEN_COMMA), $this->getCurrent());
                case '"':
                    if(!$this->beginQuoteOpen && !$this->endQuoteOpen)
                    {
                        $this->beginQuoteOpen = true;
                        $this->endQuoteOpen = true;
                    }
                    else if(!$this->beginQuoteOpen && $this->endQuoteOpen)
                    {
                        $this->endQuoteOpen = false;
                    }
                    return new Token(self::TOKEN_QUOTES, $this->getNemo(self::TOKEN_QUOTES), $this->getCurrent());
                default:
                    return $this->getLiteralToken();
            }
        }
        
        return new Token(self::TOKEN_EOF, $this->getNemo(self::TOKEN_EOF), '');
    }
    
    private function getNext($val = 1, $count = 1)
    {
        $result = null;
        
        if($this->position + $val < $this->inputLength)
        {
            $result = substr($this->input, $this->position + $val, $count);
            
            if(false === $result)
            {
                $result = null;
            }
        }
        
        return $result;
    }
    
    private function getBack($val = 1, $count = 1)
    {
        $result = null;
        
        if($this->position - $val >= 0)
        {
            $result = substr($this->input, $this->position - $val, $count);
            
            if(false === $result)
            {
                $result = null;
            }
        }
        
        return $result;
    }
    
    private function goBack($val = 1)
    {
        $this->current = $this->getBack($val);
        $this->position -= $val;
        
        if($this->current === null)
        {
            $this->position = -1;
        }
    }
    
    private function consume()
    {
        $tmpVal = $this->getNext();
        
        if(null !== $tmpVal)
        {
            $this->position++;
            $this->current = $tmpVal;
            
            return true;
        }
        
        return false;
    }
    
    private function getTokenString()
    {
        $buffer = '';
        $isNotTheEnd = true;
        
        if($this->isValidForString())
        {
            do
            {
                $buffer .= $this->getCurrent();
                $isNotTheEnd = $this->consume();
            } while($isNotTheEnd && $this->isValidForString());
        }
        
        $this->beginQuoteOpen = false;
        
        if($isNotTheEnd)
        {
            $this->goBack();
        }
        
        return new Token(self::TOKEN_STRING, $this->getNemo(self::TOKEN_STRING), $this->transformSpecialCharacter($buffer));
    }
    
    private function isValidForString()
    {
        $result = true;
        
        if($this->getCurrent() == "\n" || ($this->getCurrent() == '"' && $this->getBack() != '\\'))
        {
            $result = false;
        }
        
        return $result;
    }
    
    private function transformSpecialCharacter($val)
    {
        $allowed = array(
            '\\b' => "\b",
            '\\t' => "\t",
            '\\n' => "\n",
            '\\f' => "\f",
            '\\r' => "\r", 
            '\\"' => '"',
            '\\/' => '/',
		);
        
        $noSpecialCharacter = str_replace('\\\\', '', $val);
        $noSpecialCharacter = str_replace(array_keys($allowed), '', $noSpecialCharacter);
        $noSpecialCharacter = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '', $noSpecialCharacter);
        
        $pos = strpos($noSpecialCharacter, '\\');
        
        if(false !== $pos)
        {
            $snippet = substr($noSpecialCharacter, $pos, 8);
            throw new LexerException('Invalid special character near: ' . $snippet . '.');
        }
        
        $result = str_replace('\\\\', '[\\\\]', $val);
        $result = strtr($result, $allowed);
        
        $result = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/', 
            function($maches) {
                return json_decode('"'.$maches[0].'"'); // json directly support \uxxxx sintax
            }, 
            $result);
        $result = str_replace('[\\\\]', '\\', $result);
        
        return $result;
    }
    
    private function getTokenComment()
    {
        $buffer = '';
        $isNotTheEnd = true;
        
        do
        {
            $buffer .= $this->getCurrent();
            $isNotTheEnd = $this->consume();
        } while($isNotTheEnd && $this->getCurrent() != "\n" && $this->getCurrent() != '=');
        
        $this->commentOpen = false;
        
        if($isNotTheEnd)
        {
            $this->goBack();
        }
        
        return new Token(self::TOKEN_COMMENT, $this->getNemo(self::TOKEN_COMMENT), $buffer);
    }
    
    private function getLiteralToken()
    {
        $buffer = '';
        $isNotTheEnd = true;
        
        do
        {
            $buffer .= $this->getCurrent();
            $isNotTheEnd = $this->consume(); 
        } while($isNotTheEnd && $this->isValidForLiteral());
        
        if($isNotTheEnd)
        {
            $this->goBack();
        }
        
        return new Token(self::TOKEN_LITERAL, $this->getNemo(self::TOKEN_LITERAL), trim($buffer));
    }
    
    private function isValidForLiteral()
    {        
        switch($this->getCurrent())
        {
            case "\n":
            case "\t":
            case "\r":
            case '"':
            case '#':
            case '[':
            case ']':
            case '=':
            case ',': 
                return false;
        }
        
        return true;
    }
    
    private function getNemo($type)
    {
        return Lexer::$tokensNames[$type];
    }
}