# version 1.2.3 2016-09-01
- Rename project to eclipxe/engineworks-dbal
- Move from gitlab to github
- Changes on README
- Introduce CoC, Contributing, TODO, LICENSE

# version 1.2.2 2016-08-31
- Small fix on docblock of DBAL::sqlConcatenate variadic method
- Small fix must not use numRows but resultCount()
- Small fix inpections and warnings reported by PhpStorm
- Rename ruleset.xml to phpcs.xml
- Increase coverage on Sqlite\Result

# version 1.2.1 2016-08-09

- Implement generic Iterators for Result and Recordset
- Result and Recordset now implements IteratorAggregate and Countable interfaces
- Fix moveTo on Sqlite/Result
- Test Sqlite/Result
- Improve code style

# version 1.1.3 2016-07-31

- Fix Pager with no query for count
- Add test for pager
- Add a sqlite database for testing
- Improve coding standards

# version 1.1.2 2016-07-25

- Move sqlLimit to a trait
- Fix docblock on DBAL::queryRecordset

# version 1.1.1 2016-06-16

- Fix bug in sdqlQuoteParseNumbers when `LC_NUMERIC` is `C`
- Add support for PHP CS Fixer and .php_cs file
- On tests ArrayLogger uses AbstractLogger

# version 1.1.0 2016-06-09

- Create CommonTypes interface with common types constants
- Move some methods to Traits
- Sqlite\DBAL::sqlString uses str_replace instead of \SQLite3::escapeString
- Sqlite\Settings now allow enable-exceptions
- Mysql\Settings now allow connect-timeout
- Mysql\DBAL::connect now uses set_charset instad of query SET NAMES
- Code style improvements
- Docblocks improvements

# version 1.0.0 2016-06-08

- Put this code as a library
