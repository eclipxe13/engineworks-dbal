# eclipxe13/engineworks-dbal - Database Abstraction Layer

[![Source Code][badge-source]][source]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Scrutinizer][badge-quality]][quality]
[![Coverage Status][badge-coverage]][coverage]
[![Total Downloads][badge-downloads]][downloads]
[![SensioLabsInsight][badge-sensiolabs]][sensiolabs]

This library was created to abstract the interactions with a relational database.
At the time it was created the PDO extension does not exists.
If possible try to use PDO instead of this library, mostly because prepared statements.

I'm maintaining this library because I have several applications depending on this
and I had shared this to other people, so they can also maintain their own projects.

## Installation

To install this library you can use:
```bash
composer require eclipxe/engineworks-dbal
```

## EngineWorks\DBAL\DBAL

Main connection object, has several query methods to get the results just as needed.
It also contains sql methods to translate SQL Dialects from different drivers.

## EngineWorks\DBAL\Recordset

The Recordset class mimics the main methods of the recordset:

- Open the recordset using a SQL Statement
- Walk the recordset using the current cursor
- Access values using the values array, stores original values
- Use of magic methods Update and Delete
- Convert from/to database types to common types

Some drivers does not know how to get the primary keys on a query,
in that case you can specify the entity to affect and also the primary keys.

Mysql driver support this feature, it will check for primary keys,
auto incrementing fields or unique indexes.

## EngineWorks\DBAL\Pager

The Pager class uses Recordset to access a limited page of a query, it does not load
all the records but only the requested ones

## About drivers

It support Mysqli, Mssql, Sqlsrv and Sqlite3 drivers, you are free to create your own and (please) share it with me.

### Mysqli

- This is the most tested driver on production.

### Sqlsrv

- This driver is based on Microsoft PHP Driver for SQL Server.
- Result does not know the entity or primary keys of the query.
  Use overrideEntity and overrideKeys when create a Recordset for update or delete.

### Sqlite3

- Result does not know the entity or primary keys of the query.
  Use overrideEntity and overrideKeys when create a Recordset for update or delete.
- When a result is empty (nothing to fetch) it is not possible to know the type
  of the fields, this make this driver unstable to update using Recordset.
- The method SQLite3Result::fetchArray put the cursor in the first position
  when called after end of list. This behavior is corrected on Result and fetch
  returns always false.

### Mssql

- This driver is based on PDO dblib, you will need FreeTDS.
- Result does not know the entity or primary keys of the query.
  Use overrideEntity and overrideKeys when create a Recordset for update or delete.
- The function to quote (PDO::quote) fail with multibyte strings, we are
  using simple replacements of `'` to `''`
- This driver is not really compatible with PHP 7, use Sqlsrv instead

## Compatibility

This class will be compatible according to [PHP Supported versions](http://php.net/supported-versions.php),
Security Support.

- PHP 5.6, version **1.x**, until 2018-12-31
- PHP 7.0, version **2.x**, until 2018-12-03


## Contributing

Contributions are welcome! Please read [CONTRIBUTING][] for details
and don't forget to take a look in the [TODO][] and [CHANGELOG][] files.

## License

The eclipxe13/engineworks-dbal library is copyright © [Carlos C Soto](https://eclipxe.com.mx/)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/eclipxe13/engineworks-dbal/blob/master/CONTRIBUTING.md
[changelog]: https://github.com/eclipxe13/engineworks-dbal/blob/master/CHANGELOG.md
[todo]: https://github.com/eclipxe13/engineworks-dbal/blob/master/TODO.md

[source]: https://github.com/eclipxe13/engineworks-dbal
[release]: https://github.com/eclipxe13/engineworks-dbal/releases
[license]: https://github.com/eclipxe13/engineworks-dbal/blob/master/LICENSE
[build]: https://travis-ci.org/eclipxe13/engineworks-dbal?branch=master
[quality]: https://scrutinizer-ci.com/g/eclipxe13/engineworks-dbal/
[sensiolabs]: https://insight.sensiolabs.com/projects/26f47360-dc06-4387-b258-b619ff1bca50
[coverage]: https://scrutinizer-ci.com/g/eclipxe13/engineworks-dbal/code-structure/master/code-coverage
[downloads]: https://packagist.org/packages/eclipxe/engineworks-dbal

[badge-source]: http://img.shields.io/badge/source-eclipxe13/engineworks--dbal-blue.svg?style=flat-square
[badge-release]: https://img.shields.io/github/release/eclipxe13/engineworks-dbal.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-build]: https://img.shields.io/travis/eclipxe13/engineworks-dbal/master.svg?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/eclipxe13/engineworks-dbal/master.svg?style=flat-square
[badge-sensiolabs]: https://insight.sensiolabs.com/projects/26f47360-dc06-4387-b258-b619ff1bca50/mini.png
[badge-coverage]: https://img.shields.io/scrutinizer/coverage/g/eclipxe13/engineworks-dbal/master.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/eclipxe/engineworks-dbal.svg?style=flat-square
