# eclipxe13/engineworks-dbal To Do List

- DBAL::sqlQuote could be a final public method
- Include prepared (or simulated prepared) statements
- Include PDO Driver
- Evolve Recordset to allow PDO or DBAL
- Create ReadOnlyRecordSet as a light object
- Evolve Pager to stop using Recordset and depends on Result or another light object
- Test with more than 96% coverage

## Known problems

Method `EngineWorks\DBAL\Sqlite\Result::getIdFields` always return false since there is no way
to get the ID Fields from the `SQLite3Result` object.
It would be great if we can figure it out how to get this information.
In the mean time use recordset override keys parameter.

Method `EngineWorks\DBAL\Mssql\Result::getIdFields` always return false since there is no way
to get the ID Fields from the `PDOStatement` object.
It would be great if we can figure it out how to get this information.  
In the mean time use recordset override keys parameter.
