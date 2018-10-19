# Future breaking changes

- Change minimal version to PHP 7.2
- Parameter to negate DBAL::sqlIsNull will be deprecated
- Parameter to negate DBAL::sqlIn will be deprecated

# Version 2.2.0 2018-10-19

- Fix parameter name on DBAL::sqlFieldEscape from $tableName to $fieldName
- Add DBAL::sqlIsNotNull as a negative to DBAL::sqlIsNull
- Add notice if using DBAL::sqlIsNull with negation
- Add DBAL::sqlNotIn as a negative to DBAL::sqlIn but if elements are empty then return a true condition
- Add notice if using DBAL::sqlIn with negation
- Move test from driver specification to DbalCommonSqlTrait on testSqlIsNull & testSqlIfNull
- Add DBAL::sqlBetweenQuote

# Version 2.1.3 2018-10-05

- Fix bug when comparing if values had changed when comparing two strings in a recordset
  it have issues when updating values like "1" to "01". To fix it uses strict comparison when
  original value and current value are strings.

# Version 2.1.2 2018-08-28

- Improve `DBAL` docblocks
- Add `DBAL::createQueryException` to create a new `QueryException` with DBAL::getLastMessage() or `Database error`
- Fix issues found by pedantic phpstan 0.10.3 (with love!)
- Fix composer dependences:
    - phpunit compatible with version 7.0 and 7.3
    - phpstan-shim compatible with PHP version 7.0 and 7.3
- Fix `phpunit.xml.dist` removing `syntaxCheck` attribute and add `testsuite@name` attribute

# Version 2.1.1 2018-08-01

- `DBAL\Exceptions\QueryException` does not exposes the query in the exception message,
  if you need the query check the `getQuery()` method.

# Version 2.1.0 2018-08-01

- Add strict methods to `DBAL` as the first attempt to remove *returning false* practice
    - `DBAL::createRecordset(...): Recordset`
    - `DBAL::createPager(...): Pager`
- Introduce `DBAL\Exceptions\QueryException` and is used when `createRecordset` or `createPager` found an error.

# Version 2.0.2 2018-08-01

- Sqlsrv\DBAL::queryDriver allow to aks only for affected rows
  When ask for affected rows it execute instead of prepare statement and execute,  performance increased!
- Make sure that queryAffectedRows always return false or integer greater or equal than zero in all drivers
- Tests:
    - Rename WithSqlsrvDatabaseTestCase to SqlsrvWithDatabaseTestCase
    - Fix environment config for Sqlsrv
    - Test behavior of NOCOUNT setting into MS Sql Server drivers


# Version 2.0.1 2018-07-29

- Add `connect-timeout` option to `Sqlsrv` driver
- Fix issue when test suit end with segfault because the pdo statement in `Sqlsrv\Result` was not really destructed
- Add `Settings::exists(string $name): bool` method to the interface
- Composer: fix require, require-dev & suggest sections
- Composer: rename scripts names (prefix with "dev:" and add descriptions)


# version 2.0.0 2018-07-27

- Set minimal php version to PHP 7.0
- Add type declarations to arguments and returns
- Add `DBAL::sqlIn` to better queries using `IN` and empty arrays
- `DBAL::sqlQuoteIn` now throws an `\RuntimeException` if empty array is received
- Add support for **Microsoft Sql Server driver (sqlsrv)** on `EngineWorks\DBAL\Tests\Sqlsrv`
- Add `$overrideTypes` argument to `DBAL::queryValues` and `DBAL::queryArrayValues`
- `DBAL::queryArrayOne` returns `false` if the specified field name does not exists
- sqlite: `DBAL::queryRecordset` return `false` if query fails (as other drivers)
- include phpstan as dependence
- clear all phpstan issues
- Add to composer commands: build, coverage, style & test
- Refactor methods for better reading
- Testing improvements:
    - Improve code coverage
    - Use `assertTrue`, `assertFalse` & `assertNull` instead of `assertSame`
    - Use `DbalQueriesTrait` and `DbalCommonSqlTrait` for unified testing on `DBAL` implementations
    - Organize files: Tests TestCases, TesterCases, TesterTraits, etc...
- Replace parallel-lint with phplint
- readme: fix badges and coverage link

# version 1.7.1 2018-04-20
- Remove duplicated verification for creating the PDO object in `\EngineWorks\DBAL\Mssql\DBAL::connect()`
- Initialize `$vars` array in `\EngineWorks\DBAL\Mssql\DBAL::getPDOConnectionString`
- Move logic to parse a number `DBAL::sqlQuote` to `EngineWorks\DBAL\Internal\NumericParser`
    - Now it removes tabulator also
    - It does not remove anymore currency name (like USD), it never work very well.
- Test sqlQuote to number using different locales: `C, en_US, en_US.utf-8, pt_BR`
- Test populate database inside a transaction (run faster)
- Fix some simple phpstan issues

# version 1.7.0 2018-01-23
- Add feature to prevent final (higher) commit by `DBAL::transPreventCommit()`.
- Add new setting for mssql `freetds-version` that defaults to `7.0` it was hardcoded before.
- (DEV) Create BaseTestCase that includes `checkPhpUnitVersion`.
  It will mark the test as incomplete if the php unit version is lower than required.
  I'm not using annotation `@requires` because it does not work with composite traits.

# version 1.6.9 2018-01-10
- `Mysqli\DBAL::queryResult` now will warning if the query does not perform a result 
- Fix issues discovered by scrutinizer
- Testing locally using mssql server has been improved by not creating the database but using `tempdb`

# version 1.6.8 2018-01-10
- Run phpstan and fix all errors in folder sources, fix possible bugs
- Change a test for different messages on sqlite
- Dependences: Use version numbers instead of @stable
- Travis: Add PHP 7.1
- Scrutinizer: Update config file to recommended content

# version 1.6.7 2017-06-28
- Fix bug where a boolean must be quote as an integer and true does not return 1

# version 1.6.6 2017-06-26
- Override docblock Result::getIterator() extended from \IteratorAggregate to explicity return an \Iterator object
- Override docblock count extended from \Countable for coherence

# version 1.6.5 2017-06-26
- Fix typo in docblock, class name is ResultIterator
- Fix docblock ResultImplementsIterator::getIterator() : \Iterator
- Improve testing fail message on connect at createDatabase
- Remove development dependence on scrutinizer/ocular, only install on travis

# version 1.6.4 2017-05-04
- Trigger a E_USER_NOTICE when rollback or commit without a transaction
- Create TransactionsWithExceptionsTestTrait to probe previous behavior,
  the testers cannot test exceptions since all the execution runs as only one test. 

# version 1.6.3 2017-03-31
- Remove autocommit disabled. If it is disabled dbal will not store data unless is inside a transaction.

# version 1.6.2 2017-03-23
- Minor fixes mostly on dockblocks by Scrutinizer recommendations
- Add validations before using Recordset::originalValues
- Add additional test for nested transactions with begin-begin-begin-commit-rollback-commit
- Add information to run test mssql using docker in CONTRIBUTING.md

# version 1.6.1 2017-03-22
- Mysqli set autocommit to false
- Support for nested transactions using savepoints
- Internal API change due transactions refactory
- Test transactions using a tester
- DBAL::execute now has a new parameter to throw an exception instead of return false
- Fix several docblocks
- Implement overrideTypes on mssql and mysqli

# version 1.5.2 2017-03-01
- Remove TODO comment
- Continuous Integration
    - add the missing .scrutinizer.yml file
- Documentation
    - where were the badges again?

# version 1.5.1 2017-02-28
- Follow scrutinizer recommendations like change logical operators and avoid error suppressing when not necesary
- Documentation:
    - add keywords to composer.json
    - correct license years
    - add badges
    - document known problems in todo 
- Remove coveralls

# version 1.5.0 2017-02-24
- Add `$overrideTypes` parameter for `DBAL::queryResult` and `DBAL::queryRecordset` methods.
  The driver Sqlite does not recognize properly the commontypes of fields
- Deprecate `DBAL::query` method
- Improve test, add Mysqli and environment configuration for mysql
- Use new validation rules of php-cs-fixer
- Do not build using hhvm, include php 7.1, add mysql
- Add compatibility on php 7.1 by fixing contructor on `EngineWorks\DBAL::Settings`

# version 1.4.1 2017-02-23
- There were some errors using the recordset on weird table and field names, this version make the following changes:
    - `sqlField(a, b) => a as "b"`: New method, only escape the alias
    - `sqlFieldEscape(a, b) => "a" as "b"`: New method, escape both, the name and the alias
    - `sqlTable(a, b) => "suffix_a" as "b"`: Not changed
    - `sqlTableEscape(a, b) => "a" as "b"`: Changed from protected to public
- Change Recordset to use this methods when building the sql sentences.

# version 1.3.1 2017-01-31
- Fix bug when sqlQuote receives a stringable object but it does not take value to parse it as int or float

# version 1.3.0  2016-11-14
- Add Mssql driver
- Allow pass entity and primary keys to Recordset
- Sqlite uses fetch behavior to reset the query
- Improve tests
- Add Travis-CI support
- Improve documentation

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
