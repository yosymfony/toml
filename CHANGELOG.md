CHANGELOG
=========
0.3.3 (2015-08-24)
------------------
* Fixed bug #10: Cannot parse quote (") in table name.

0.3.2 (2015-03-07)
------------------
* Fixed issue #9: Only double-quoted strings work in arrays, contrary to spec.

0.3.1 (2015-03-07)
------------------
* Added support to literal strings in TomlBuilder.

0.3.0 (2015-03-06)
------------------
* Support for TOML 0.4.0.
* CS fixes.

0.2.0 (2014-05-03)
--------------------
* Support for TOML 0.2.0.
* New tests for arrays of tables.
* TomlBuilder: new methods addTable
* TomlBuilder: Deprecated methods addGroup.
* Fixtures folder in tests renamed to fixtures.
* Fixtures reorganized.

0.1.1 (2013-12-14)
------------------
* Fixed bug with empty string value parse error.
* Fixed exception default timezone unset in unit tests.
* Added travis configuration file.
* Fixed some issues in README.md.

0.1.0 (2013-05-12)
------------------
* Initial release.
* Support for TOML 0.1.0.
* BurntSushi test suite included.
* Included TomlBuilder for create inline TOML strings.
