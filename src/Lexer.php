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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->basicLexer = new BasicLexer([
            '/^(=)/' => 'T_EQUAL',
            '/^(true|false)/' => 'T_BOOLEAN',
            '/^(\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d{6})?(Z|-\d{2}:\d{2})?)?)/' => 'T_DATE_TIME',
            '/^([+-]?((((\d_?)+[\.]?(\d_?)*)[eE][+-]?(\d_?)+)|((\d_?)+[\.](\d_?)+)))/' => 'T_FLOAT',
            '/^([+-]?(\d_?)+)/' => 'T_INTEGER',
            '/^(""")/' => 'T_3_QUOTATION_MARK',
            '/^(")/' => 'T_QUOTATION_MARK',
            "/^(''')/" => 'T_3_APOSTROPHE',
            "/^(')/" => 'T_APOSTROPHE',
            '/^(#)/' => 'T_HASH',
            '/^(\s+)/' => 'T_SPACE',
            '/^(\[)/' => 'T_LEFT_SQUARE_BRAKET',
            '/^(\])/' => 'T_RIGHT_SQUARE_BRAKET',
            '/^(\{)/' => 'T_LEFT_CURLY_BRACE',
            '/^(\})/' => 'T_RIGHT_CURLY_BRACE',
            '/^(,)/' => 'T_COMMA',
            '/^(\.)/' => 'T_DOT',
            '/^([-A-Z_a-z0-9]+)/' => 'T_UNQUOTED_KEY',
            '/^(\\\(b|t|n|f|r|"|\\\\|u[0-9AaBbCcDdEeFf]{4,4}|U[0-9AaBbCcDdEeFf]{8,8}))/' => 'T_ESCAPED_CHARACTER',
            '/^(\\\)/' => 'T_ESCAPE',
            '/^([\x{20}-\x{21}\x{23}-\x{26}\x{28}-\x{5A}\x{5E}-\x{10FFFF}]+)/u' => 'T_BASIC_UNESCAPED',

        ]);

        $this->basicLexer
            ->generateNewlineTokens()
            ->generateEosToken();
    }

    /**
     * {@inheritdoc}
     */
    public function tokenize(string $input) : TokenStream
    {
        return $this->basicLexer->tokenize($input);
    }
}
