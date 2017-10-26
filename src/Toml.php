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

use Yosymfony\ParserUtils\SyntaxErrorException;
use Yosymfony\Toml\Exception\ParseException;

/**
 * Parser for TOML format.
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Toml
{
    /**
     * Parses TOML into a PHP array.
     *
     * Usage:
     * <code>
     *  $array = Toml::parse('key = "[1,2,3]"');
     *  print_r($array);
     * </code>
     *
     * @param string $input A string containing TOML
     *
     * @return array The TOML converted to a PHP value
     *
     * @throws ParseException If the TOML is not valid
     */
    public static function parse(string $input) : array
    {
        $parser = new Parser(new Lexer());

        try {
            return $parser->parse($input);
        } catch (SyntaxErrorException $e) {
            $exception = new ParseException($e->getMessage());

            if ($token = $e->getToken()) {
                $exception->setParsedLine($token->getLine());
            }

            throw $exception;
        }
    }

    /**
     * Parses a TOML file into a PHP array.
     *
     * Usage:
     * <code>
     *  $array = Toml::parseFile('config.toml');
     *  print_r($array);
     * </code>
     *
     * @param string $input A string containing TOML
     *
     * @return array The TOML converted to a PHP value
     *
     * @throws ParseException If the TOML file is not valid
     */
    public static function parseFile(string $filename) : array
    {
        if (!is_file($filename)) {
            throw new ParseException(sprintf('File "%s" does not exist.', $filename));
        }

        if (!is_readable($filename)) {
            throw new ParseException(sprintf('File "%s" cannot be read.', $filename));
        }

        $parser = new Parser(new Lexer());

        try {
            return $parser->parse(file_get_contents($filename));
        } catch (SyntaxErrorException $e) {
            $exception = new ParseException($e->getMessage());
            $exception->setParsedFile($filename);

            if ($token = $e->getToken()) {
                $exception->setParsedLine($token->getLine());
            }

            throw $exception;
        }
    }
}
