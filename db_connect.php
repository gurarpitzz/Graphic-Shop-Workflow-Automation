<?php
// server/db_connect.php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default
$db   = 'graphic_shop_db';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
} catch (PDOException $e) {
    // Try one common XAMPP variant
    try {
        $pass = 'root';
        $pdo = new PDO("mysql:host=$host", $user, $pass);
    } catch(PDOException $e2) {
        // Fail if both fail
        if (strpos($_SERVER['REQUEST_URI'], 'test_db.php') === false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'MySQL Connection Failed: ' . $e2->getMessage()]);
            exit;
        }
    }
}

// 2. Set Attributes
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// 3. Select / Create Database
try {
    $pdo->query("CREATE DATABASE IF NOT EXISTS $db");
    $pdo->query("USE $db");
} catch (PDOException $e) {
    // If we can't create the DB, only fail if not in installer
    if (strpos($_SERVER['REQUEST_URI'], 'install.php') === false && strpos($_SERVER['REQUEST_URI'], 'test_db.php') === false) {
        die("Database Selection Failed: " . $e->getMessage());
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();
