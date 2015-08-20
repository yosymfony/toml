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

/**
 * Token of Toml.
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Token
{
    private $type;
    private $nemo;
    private $value;

    public function __construct($type, $nemo, $value)
    {
        $this->type = $type;
        $this->nemo = $nemo;
        $this->value = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return '['.$this->nemo.'] : '.$this->value;
    }
}
