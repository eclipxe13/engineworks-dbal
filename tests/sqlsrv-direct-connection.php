<?php

//
// This script does not depends on anything, it only check connection to ms sql server
//

exit(call_user_func(function ($arguments): int {
    $host = $arguments[1] ?? '';
    $user = $arguments[2] ?? '';
    $pass = $arguments[3] ?? '';

    try {
        $pdo = new PDO(
            sprintf('sqlsrv:Server=%s;Database=%s', $host, 'master'),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $sql = 'SELECT @@VERSION;';
        $version = implode(' ', $pdo->query($sql)->fetch(PDO::FETCH_NUM) ?? []);
        echo PHP_EOL, $sql, PHP_EOL, $version, PHP_EOL;
    } catch (\Throwable $exception) {
        file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL, FILE_APPEND);
        return 1;
    }
    return 0;
}, $argv));
