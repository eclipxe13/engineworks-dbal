# Engineworks\DBAL - Database Abstraction Layer

This library was created to abstract the interactions with a relational database.
At the time it was created the PDO extension does not exists.
If possible try to use PDO instead of this library, mostly because prepared statements.

I'm maintaining this library becouse I have several applications depending on this
and I had shared this to other people, so they can also maintain their own projects.

# Installation

To install this library you must add this to composer:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://gitlab.com/eclipxe13/engineworks-dbal"
        }
    ],
    "require": {
        "engineworks/dbal": "dev-master"
    }
}
```

# DBAL

Main connection object, has several query methods to get the results just as needed.
It also contains sql methods to translate SQL Dialects from different drivers.

# Recordset

The Recordset class mimics the main methods of the recordset:

- Open the recordset using a SQL Statement
- Walk the recordset using the current cursor
- Access values using the values array, stores original values
- Use of magic methods Update and Delete
- Convert from/to database types to common types

# Pager

The Pager class uses Recordset to access a limited page of a query, it does not load
all the records but only the requested ones

# Drivers

It support Mysqli and Sqlite3 drivers, you are free to create your own and share it with me.

# TODO

- DBAL::sqlQuote could be a final public method
- Include prepared (or simulated prepared) statements
- Include PDO Driver
- Evolve Recordset to allow PDO or DBAL
- Create ReadOnlyRecordSet as a light object
- Evolve Pager to stop using Recordset and depends on Result or another light object
- Create test (take test from other projects and insert it in here)
