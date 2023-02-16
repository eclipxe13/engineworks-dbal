<?php

declare(strict_types=1);

//
// This script does not depend on anything, it only checks connection to ms sql server
// php sqlsrv-direct-connection.php server user password database
//

exit(call_user_func(function (string ...$arguments): int {
    $host = $arguments[1] ?? 'localhost';
    $user = $arguments[2] ?? '';
    $pass = $arguments[3] ?? '';
    $name = $arguments[4] ?? 'master';

    try {
        $pdo = new PDO(
            sprintf('sqlsrv:Server=%s;Database=%s', $host, $name),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $sql = 'SELECT @@VERSION;';
        $statement = $pdo->query($sql);
        if (false === $statement) {
            throw new RuntimeException("Cannot create statement to query: $sql");
        }
        /** @var array<int, scalar> $result */
        $result = $statement->fetch(PDO::FETCH_NUM) ?: [];
        $version = implode(' ', $result);
        echo PHP_EOL, $sql, PHP_EOL, $version, PHP_EOL;
    } catch (Throwable $exception) {
        file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL, FILE_APPEND);
        return 1;
    }
    return 0;
}, ...$argv));
