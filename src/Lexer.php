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

use Yosymfony\ParserUtils\BasicLexer;
use Yosymfony\ParserUtils\LexerInterface;
use Yosymfony\ParserUtils\TokenStream;

/**
 * Lexer for Toml strings.
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Lexer implements LexerInterface
{
    private $basicLexer;

     const T_EQUAL = 1;
     const T_BOOLEAN = 2;
     const T_DATE_TIME = 3;
     const T_EOS = 4;
     const T_INTEGER = 5;
     const T_3_QUOTATION_MARK = 6;
     const T_QUOTATION_MARK = 7;
     const T_3_APOSTROPHE = 8;
     const T_APOSTROPHE = 9;
     const T_NEWLINE = 10;
     const T_SPACE = 11;
     const T_LEFT_SQUARE_BRAKET = 12;
     const T_RIGHT_SQUARE_BRAKET = 13;
     const T_LEFT_CURLY_BRACE = 14;
     const T_RIGHT_CURLY_BRACE = 15;
     const T_COMMA = 16;
     const T_DOT = 17;
     const T_UNQUOTED_KEY = 18;
     const T_ESCAPED_CHARACTER = 19;
     const T_ESCAPE = 20;
     const T_BASIC_UNESCAPED = 21;
     const T_FLOAT = 22;
     const T_HASH = 23;
     const T_LAST_TOKEN = 23;
    
     
     static private $nameSet = [
         'T_BAD_TOKEN', //0
         'T_EQUAL',//1
         'T_BOOLEAN',//2
         'T_DATE_TIME',//3
         'T_EOS',//4
         'T_INTEGER',//5
         'T_3_QUOTATION_MARK',//6
         'T_QUOTATION_MARK',//7
         'T_3_APOSTROPHE',//8
         'T_APOSTROPHE',//9
         'T_NEWLINE',//10
         'T_SPACE',//11
         'T_LEFT_SQUARE_BRAKET',//12
         'T_RIGHT_SQUARE_BRAKET',//13
         'T_LEFT_CURLY_BRACE',//14
         'T_RIGHT_CURLY_BRACE',//15
         'T_COMMA',//16
         'T_DOT',//17
         'T_UNQUOTED_KEY',//18
         'T_ESCAPED_CHARACTER',//19
         'T_ESCAPE',//20
         'T_BASIC_UNESCAPED',//21
         'T_FLOAT',//22
         'T_HASH',//23
     ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->basicLexer = new BasicLexer([
            '/^(=)/' => Lexer::T_EQUAL,
            '/^(true|false)/' => Lexer::T_BOOLEAN,
            '/^(\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d{6})?(Z|-\d{2}:\d{2})?)?)/' => Lexer::T_DATE_TIME,
            '/^([+-]?((((\d_?)+[\.]?(\d_?)*)[eE][+-]?(\d_?)+)|((\d_?)+[\.](\d_?)+)))/' => Lexer::T_FLOAT,
            '/^([+-]?(\d_?)+)/' => Lexer::T_INTEGER,
            '/^(""")/' => Lexer::T_3_QUOTATION_MARK,
            '/^(")/' => Lexer::T_QUOTATION_MARK,
            "/^(''')/" => Lexer::T_3_APOSTROPHE,
            "/^(')/" => Lexer::T_APOSTROPHE,
            '/^(#)/' => Lexer::T_HASH,
            '/^(\s+)/' => Lexer::T_SPACE,
            '/^(\[)/' => Lexer::T_LEFT_SQUARE_BRAKET,
            '/^(\])/' => Lexer::T_RIGHT_SQUARE_BRAKET,
            '/^(\{)/' => Lexer::T_LEFT_CURLY_BRACE,
            '/^(\})/' => Lexer::T_RIGHT_CURLY_BRACE,
            '/^(,)/' => Lexer::T_COMMA,
            '/^(\.)/' => Lexer::T_DOT,
            '/^([-A-Z_a-z0-9]+)/' => Lexer::T_UNQUOTED_KEY,
            '/^(\\\(b|t|n|f|r|"|\\\\|u[0-9AaBbCcDdEeFf]{4,4}|U[0-9AaBbCcDdEeFf]{8,8}))/' => Lexer::T_ESCAPED_CHARACTER,
            '/^(\\\)/' => Lexer::T_ESCAPE,
            '/^([\x{20}-\x{21}\x{23}-\x{26}\x{28}-\x{5A}\x{5E}-\x{10FFFF}]+)/u' => Lexer::T_BASIC_UNESCAPED,

        ]);

        $this->basicLexer
            ->generateNewlineTokens()
            ->generateEosToken();
        
        $this->basicLexer->setNewlineTokenName(Lexer::tokenName(Lexer::T_NEWLINE), Lexer::T_NEWLINE);
        $this->basicLexer->setEosTokenName(Lexer::tokenName(Lexer::T_EOS), Lexer::T_EOS);
    }

    /**
     * {@inheritdoc}
     */
    public function tokenize(string $input) : TokenStream
    {
        return $this->basicLexer->tokenize($input);
    }
    
    static public function tokenName(int $tokenId) : string {
        if ($tokenId > Lexer::T_LAST_TOKEN || $tokenId < 0) {
            $tokenId = 0;
        }
        return self::$nameSet[$tokenId];
    }
}
