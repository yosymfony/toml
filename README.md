Toml component
==============

A PHP parser for [TOML](https://github.com/mojombo/toml) compatible with [TOML v0.1.0](https://github.com/mojombo/toml/blob/master/versions/toml-v0.1.0.md).

Installation
------------

Use [Composer](http://getcomposer.org/) to install Yosyfmony Toml package:

Add the following to your `composer.json` and run `composer update`.

    "require": {
        "yosymfony/toml": "dev-master"
    }

More informations about the package on [Packagist](https://packagist.org/packages/yosymfony/toml).

Usage
-----
You can use this package to parse TOML string inline or from a file with only one method:

    use Yosymfony\Toml\Toml;
    
    $array = Toml::Parse('key = [1,2,3]');
    
    print_r($array);

From a file:

    use Yosymfony\Toml\Toml;
    
    $array = Toml::Parse('example.toml');
    
    print_r($array);

Unit tests
----------
This package are in compliance with BurntSushi [test suite for TOML parsers](https://github.com/BurntSushi/toml-test).

You can run the unit tests with the following command:

    $ cd your-path/vendor/yosymfony/toml
    $ composer.phar install --dev
    $ phpunit