<?php

/*
 * This file is part of the Yosymfony\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Yosymfony\Toml\Tests;
 
use Yosymfony\Toml\Lexer;
use Yosymfony\Toml\Token;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetToken()
    {
        $lexer = new Lexer('title = "TOML Example"');
        
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_LITERAL);
    }
    
    public function testGetNextToken()
    {
        $lexer = new Lexer('[[fruit]]');
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_LBRANK);
        $this->assertEquals($lexer->getNextToken()->getType(), Lexer::TOKEN_LBRANK);
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_LBRANK);
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_LITERAL);
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_RBRANK);
        $this->assertEquals($lexer->getNextToken()->getType(), Lexer::TOKEN_RBRANK);
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_RBRANK);
    }
    
    public function testGetBackToken()
    {
        $lexer = new Lexer('[[fruit]]');
        $lexer->getToken();
        $lexer->getToken();
        
        $this->assertEquals($lexer->getBackToken()->getType(), Lexer::TOKEN_LBRANK);
        $this->assertEquals($lexer->getToken()->getType(), Lexer::TOKEN_LITERAL);
    }
    
    public function testTitle()
    {
        $lexer = new Lexer('title = "TOML Example"');
        
        $token = $lexer->getToken();
        
        $this->isInstanceOf('Token', '::getToken() return a Token instance');
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EQUAL);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testMultipleSpaces()
    {
        $lexer = new Lexer('title   =   "TOML Example"');
        
        $token = $lexer->getToken();
        
        $this->isInstanceOf('Token', '::getToken() return a Token instance');
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EQUAL);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testComment()
    {
        $lexer = new Lexer('# I am a comment. Hear me roar. Roar. ');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_HASH);
        
        $token = $lexer->getToken();

        $this->assertEquals($token->getType(), Lexer::TOKEN_COMMENT);
        $this->assertEquals($token->getValue(), ' I am a comment. Hear me roar. Roar. ');
        
        $token = $lexer->getToken();

        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testString()
    {
        $lexer = new Lexer('"I\'m a string. \/slash \r \u000A Jos\u0082 \"You can quote me\". Tab \t newline \n you get it."');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        $this->assertEquals($token->getValue(), "I'm a string. /slash \r ".json_decode('"\u000A"')." Jos".json_decode('"\u0082"')." \"You can quote me\". Tab \t newline \n you get it.");
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testStringUnicodeSyntax()
    {
        $lexer = new Lexer('"Jos\u0082"');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        $this->assertEquals($token->getValue(), 'Jos'.json_decode('"\u0082"'));
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testStringPath()
    {
        $lexer = new Lexer('"C:\\\\Users\\\\nodejs\\\\templates"');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        $this->assertEquals($token->getValue(), 'C:\\Users\\nodejs\\templates');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringBadPath()
    {
        $lexer = new Lexer('"C:\Users\nodejs\templates"');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringUnicodeSyntaxBad()
    {
        $lexer = new Lexer('"Jos\u8"');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->fail('An excepted LexerException has not been raised.');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringUnicodeSyntaxBadHexValue()
    {
        $lexer = new Lexer('"Jos\u008Z"');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->fail('An excepted LexerException has not been raised.');
    }
    
    /**
     * @expectedException \Yosymfony\Toml\Exception\LexerException
     */
    public function testStringInvalidSpecialCharacter()
    {
        $lexer = new Lexer('"Invalid\a"');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->fail('An excepted LexerException has not been raised.');
    }
    
    public function testNegativeInteger()
    {
        $lexer = new Lexer('-23');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        $this->assertEquals($token->getValue(), '-23');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testNegativeFloat()
    {
        $lexer = new Lexer('-0.01');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        $this->assertEquals($token->getValue(), '-0.01');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testDateTimeFloat()
    {
        $lexer = new Lexer('1979-05-27T07:32:00Z');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        $this->assertEquals($token->getValue(), '1979-05-27T07:32:00Z');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testArray()
    {
        $lexer = new Lexer('[ 1, 2 ]');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LBRANK);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        $this->assertEquals($token->getValue(), '1');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_COMMA);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LITERAL);
        $this->assertEquals($token->getValue(), '2');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_RBRANK);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
    
    public function testArrayStrings()
    {
        $lexer = new Lexer('[ "red", "yellow" ]');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_LBRANK);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        $this->assertEquals($token->getValue(), 'red');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_COMMA);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_STRING);
        $this->assertEquals($token->getValue(), 'yellow');
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_QUOTES);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_RBRANK);
        
        $token = $lexer->getToken();
        
        $this->assertEquals($token->getType(), Lexer::TOKEN_EOF);
    }
}
