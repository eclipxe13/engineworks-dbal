# Contributing

Contributions are welcome. We accept pull requests on [GitHub](https://github.com/eclipxe13/engineworks-dbal).

This project adheres to a [Contributor Code of Conduct](https://github.com/eclipxe13/engineworks-dbal/blob/master/CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold this code.

## Team members

* [Carlos C Soto](https://github.com/eclipxe13) - original author and maintainer
* [GitHub constributors](https://github.com/eclipxe13/engineworks-dbal/graphs/contributors)

## Communication Channels

You can find help and discussion in the following places:

* GitHub Issues: <https://github.com/eclipxe13/engineworks-dbal/issues>
* Wiki: <https://github.com/eclipxe13/engineworks-dbal/wiki>

## Reporting Bugs

Bugs are tracked in our project's [issue tracker](https://github.com/eclipxe13/engineworks-dbal/issues).

When submitting a bug report, please include enough information for us to reproduce the bug. A good bug report includes the following sections:

* Expected outcome
* Actual outcome
* Steps to reproduce, including sample code
* Any other information that will help us debug and reproduce the issue, including stack traces, system/environment information, and screenshots

**Please do not include passwords or any personally identifiable information in your bug report and sample code.**

## Fixing Bugs

We welcome pull requests to fix bugs!

If you see a bug report that you'd like to fix, please feel free to do so. Following the directions and guidelines described in the "Adding New Features" section below, you may create bugfix branches and send us pull requests.

## Adding New Features

If you have an idea for a new feature, it's a good idea to check out
our [issues](https://github.com/eclipxe13/engineworks-dbal/issues)
or active [pull requests](https://github.com/eclipxe13/engineworks-dbal/pulls)
first to see if the feature is already being worked on.
If not, feel free to submit an issue first, asking whether the feature is beneficial to the project.
This will save you from doing a lot of development work only to have your feature rejected.
We don't enjoy rejecting your hard work, but some features just don't fit with the goals of the project.

When you do begin working on your feature, here are some guidelines to consider:

* Your pull request description should clearly detail the changes you have made.
* We following the **[PSR-2 coding standard](http://www.php-fig.org/psr/psr-2/)**. Please ensure your code does, too.
* Please **write tests** for any new features you add.
* Please **ensure that tests pass** before submitting your pull request. We have Travis CI automatically running tests for pull requests. However, running the tests locally will help save time.
* **Use topic/feature branches.** Please do not ask us to pull from your master branch.
* **Submit one feature per pull request.** If you have multiple features you wish to submit, please break them up into separate pull requests.
* **Send coherent history**. Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

If you are having issues with PSR-2 try to use `squizlabs/php_codesniffer` with `phpcs` and `phpcbf` tools

This project include a `phpcs.xml` file that extends the PSR-2 rules.

```bash
# find issues
vendor/bin/phpcs -sp --colors src/ tests/
# fix sources and tests
vendor/bin/phpcbf src/ tests/
```

## Running Tests

The following tests must pass before we will accept a pull request. If any of these do not pass,
it will result in a complete build failure. Before you can run these, be sure to `composer install`.

```
./vendor/bin/phplint
./vendor/bin/phpcs src tests --encoding=utf-8 --standard=psr2 -sp
./vendor/bin/phpunit --coverage-text
./vendor/bin/phpstan analyse --level max src/ tests/
```

### Testing PHP 5.6

Notice that `composer.json` allows versions `^5.7` or `^6.5`.
Composer already knows that `^6.5` is not compatible with PHP 5.6 so it will try to install `^5.7`.
Some tests are skipped of PHPUnit is lower than version `6.0`.

### Testing Mssql

Ensure that you have a file with the configuration on `tests/.env`, you can use `tests/.env.example` as start point.
In the configuration file setup your Mssql instance.

If you don't have one you can use Docker with the image `microsoft/mssql-server-linux` of Microsoft SQL Server vNext.

```bash
# install/update the microsoft image
docker pull microsoft/mssql-server-linux
# run an instance of mssql
docker run --name dbal-mssql -e 'ACCEPT_EULA=Y' -e 'SA_PASSWORD=Password-123456' -p 1433:1433 -d microsoft/mssql-server-linux
# access the instance and run mssql
docker exec -it dbal-mssql /bin/bash
/opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P Password-123456
# stop the instance
docker stop dbal-mssql
# remove the instance 
docker rm dbal-mssql
```

### Testing Mysql

Ensure that you have a file with the configuration on `tests/.env`, you can use `tests/.env.example` as start point.
In the configuration file setup your Mysql instance. Installation instructions depends on your OS.
