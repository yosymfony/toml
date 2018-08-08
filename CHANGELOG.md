CHANGELOG
=========
1.0.4 (2018-08-08)
------------------
* Several corrections and refactorings in `TomBuilder` class. The problem described in the PR #25 "fixed a bug when used the function 'in_array'" has been solved.
* The test file `TomlBuilderTest` has been refactored for readability. Added some tests.
* The `README.md` file has been updated with the `TomlBuilder` class limitations.

1.0.3 (2018-07-31)
------------------
* `TomlBuilder` does not throw a `DumpException` anymore when the character "#" appears in a quoted key.
* The method `addArrayTables` from the class `TomlBuilder` has been declared as deprecated. Use the method `addArrayOfTable` instead.
* Fixed the bug #24: "Wrong array of tables implementation".
* A new class `TomlArray` has been added to handle the Toml array generation. 

1.0.2 (2018-06-29)
------------------
* Fixed the bug #23: "Unable to parse ArrayTables that contain Tables".
* A new class `KeyStore` has been added to deal with the logic of the keys (keys, tables and array of tables).
* Package `yosymfony/parser-utils` has been updated to 2.0.0.

1.0.1 (2018-02-05)
------------------
* Fixed a bug related to integer keys: now, it's possible to create keys using an integer. Reported by @betrixed.
* Merged the pull request #17: "removing `is_string` check".
* Minor fixes in README file.

1.0.0 (2017-11-18)
------------------
* The code has been rewritten from scratch for PHP 7.1.
* The method `parse` from `Toml` class must only be applied to TOML strings.
  In case of parsing a TOML filename use the new method `parseFile`.
* Methods `parse` and `parseFile` from `Toml` class accept a new argument `resultAsObject`
  (optional) to return the parsed input as an object (an instance of `stdClass`).
* The method `addGroup` of `TomlBuilder` class has been deleted.
* The exceptions have been refactored, so the classes `ExceptionInterface`,
  `LexerException` and `RuntimeException` have been removed.
* Added the inner exception when a `ParseException` is thrown in method `parse` of class `Toml`.
* Fixed bug #13: "Inline sub-tables don't work".
* Fixed bug #12: "Does not parse a table with an array of tables".
* Better support for dates as specified in the latest TOML spec. See PR #11.

0.3.3 (2015-08-24)
------------------
* Fixed bug #10: Cannot parse quote (") in table name.

0.3.2 (2015-03-07)
------------------
* Fixed issue #9: Only double-quoted strings work in arrays, contrary to spec.

0.3.1 (2015-03-07)
------------------
* Added support to literal strings in `TomlBuilder`.

0.3.0 (2015-03-06)
------------------
* Support for TOML 0.4.0.
* CS fixes.

0.2.0 (2014-05-03)
--------------------
* Support for TOML 0.2.0.
* New tests for arrays of tables.
* TomlBuilder: new methods `addTabl`e
* TomlBuilder: Deprecated methods `addGroup`.
* Fixtures folder in tests renamed to fixtures.
* Fixtures reorganized.

0.1.1 (2013-12-14)
------------------
* Fixed bug with empty string value parse error.
* Fixed exception default timezone unset in unit tests.
* Added Travis configuration file.
* Fixed some issues in README.md.

0.1.0 (2013-05-12)
------------------
* Initial release.
* Support for TOML 0.1.0.
* BurntSushi test suite included.
* Included TomlBuilder to create inline TOML strings.
