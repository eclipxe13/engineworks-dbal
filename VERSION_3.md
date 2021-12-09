# EngineWorks\DBAL Version 3.x

This version will introduce this fundamental changes:

- [] Remove `Mssql` driver, now it only uses `Sqlsrv` to connect to MS SQL Server
- [] Change minimal version to PHP 8.0
- [] Parameter to negate `DBAL::sqlIsNull` will be deprecated
- [] Parameter to negate `DBAL::sqlIn` will be deprecated
- [] Add return types `void` and nullable `?` when possible
- [] Change builds to use `phive`
