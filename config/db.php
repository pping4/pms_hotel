<?php
$host = 'localhost';
$db   = 'pms_hotel';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Attempt to create database if it doesn't exist (for first run)
    try {
        $dsn_no_db = "mysql:host=$host;charset=$charset";
        $pdo_temp = new PDO($dsn_no_db, $user, $pass, $options);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect again
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e2) {
        throw new \PDOException($e2->getMessage(), (int)$e2->getCode());
    }
}
?>
