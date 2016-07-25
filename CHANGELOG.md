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
