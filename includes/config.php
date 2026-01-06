<?php
// Database connection configuration for RDS PostgreSQL
define('DB_HOST', getenv("DB_HOST"));
define('DB_PORT', '5432');
define('DB_NAME', getenv("DB_NAME"));
define('DB_USER', getenv("DB_USER"));
define('DB_PASS', getenv("DB_PASS"));

// Connect to PostgreSQL
try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session start
session_start();

// Base URL configuration
define('BASE_URL', 'http://your-ec2-public-ip-or-domain/');
?>
