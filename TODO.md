# eclipxe13/engineworks-dbal To Do List

## Urgent!

- Since now is using PHP 7.0 use type declarations in all project
- Move Sqlite3 to PDO
- Deprecate (or remove in branch 2) Mssql driver (pdo_dblib)

## To do

- Travis: bionic does not have support for PHP 7.0, upgrade to bionic when minimal version is higher
- DBAL::sqlQuote could be a final public method
- Include prepared (or simulated prepared) statements
- ~~Include Generic PDO Driver (is not a good idea)~~
- Evolve Recordset to allow PDO or DBAL
- Create ReadOnlyRecordSet as a light object
- Evolve Pager to stop using Recordset and depends on Result or another light object
- Test with more than 96% coverage
- DBAL::sqlQuote could be a final public method

## Known problems

Method `EngineWorks\DBAL\Sqlite\Result::getIdFields` always return false since there is no way
to get the ID Fields from the `SQLite3Result` object.
It would be great if we can figure it out how to get this information.
In the mean time use recordset override keys parameter.

Method `EngineWorks\DBAL\Mssql\Result::getIdFields` always return false since there is no way
to get the ID Fields from the `PDOStatement` object. This affects `Mssql` and `Sqlsrv`
It would be great if we can figure it out how to get this information.  
In the mean time use recordset override keys parameter.
