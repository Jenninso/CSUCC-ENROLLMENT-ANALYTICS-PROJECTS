<?php

$host = '127.0.0.1';
$port = '5432';
$user = 'postgres';
$pass = 'jennel';
$dbName = 'enrollment_analytics';

echo "PHP " . PHP_VERSION . PHP_EOL;

if (! extension_loaded('pdo_pgsql')) {
    fwrite(STDERR, "pdo_pgsql is not enabled in php.ini.\n");
    exit(1);
}

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Cannot connect to PostgreSQL: " . $e->getMessage() . "\n");
    exit(1);
}

$exists = $pdo->query("SELECT 1 FROM pg_database WHERE datname = " . $pdo->quote($dbName))->fetchColumn();
if (! $exists) {
    $pdo->exec('CREATE DATABASE ' . $dbName);
    echo "Created database {$dbName}.\n";
} else {
    echo "Database {$dbName} already exists.\n";
}

echo "PostgreSQL connection OK.\n";
