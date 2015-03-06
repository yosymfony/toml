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
 * Parser and dump for Toml format.
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 *
 * @api
 */
class Toml
{
    /**
     * Parse TOML into a PHP array.
     *
     * Usage:
     * <code>
     *  $array = Toml::parse('config.toml');
     *  print_r($array);
     *
     *  $array = Toml::parse('key = "[1,2,3]"');
     *  print_r($array);
     * </code>
     *
     * @return array The TOML converted to a PHP array
     */
    public static function parse($input)
    {
        $file = '';

        if (is_file($input)) {
            if (!is_readable($input)) {
                throw new ParseException(sprintf('Unable to parse "%s" as the file is not readable.', $input));
            }

            $file = $input;
            $input = file_get_contents($input);
        }

        $parser = new Parser();

        try {
            return $parser->parse($input);
        } catch (ParseException $e) {
            if ($file) {
                $e->setParsedFile($file);
            }

            throw $e;
        }
    }
}
