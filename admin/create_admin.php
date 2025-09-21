<?php
require 'includes/config.php';

// First check if admin already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute(['admin', 'admin@example.com']);
$existing = $stmt->fetch();

if ($existing) {
    echo "Admin user already exists!\n";
    exit;
}

$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

// Use the correct column name based on your schema
$pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, TRUE)")
    ->execute([$username, $email, $hash]);

echo "Admin created!\n";
