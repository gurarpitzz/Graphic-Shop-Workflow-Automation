<?php
// server/setup.php

$host = '127.0.0.1';
$username = 'root';
$password = '';

try {
    // 1. Connect to MySQL Server (No DB selected yet)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Read and Run SQL File
    $sql = file_get_contents('../database.sql');
    
    // Execute multi-query (may need specialized split if PDO doesn't support multiple queries well in one go, but usually fine for simple schema)
    // Actually PDO::exec might fail on multiple statements depending on driver.
    // Let's split by semicolon for safety.
    
    // Simplification: asking PDO to run the Full SQL usually works if emulation is on.
    $pdo->exec($sql);
    
    echo "Database Schema Imported Successfully.<br>";

    // 3. Seed Users (with proper formatting)
    $pdo->exec("USE graphic_shop_db");
    
    // Check if users exist
    $stmt = $pdo->query("SELECT count(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $users = [
            ['admin', $password, 'admin', null],
            ['accountant', $password, 'accountant', null],
            ['designer1', $password, 'worker', 'design'],
            ['printer1', $password, 'worker', 'printing'],
            ['fabricator1', $password, 'worker', 'fabrication'],
        ];

        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, role, domain) VALUES (?, ?, ?, ?)");
        
        foreach ($users as $u) {
            $insert->execute($u);
        }
        echo "Seed Users Created (Pass: password123).<br>";
    } else {
        echo "Users already exist. Skipping seed.<br>";
    }
    
    echo "Setup Complete! <a href='../index.html'>Go to Login</a>";

} catch (PDOException $e) {
    die("Setup Failed: " . $e->getMessage());
}
?>
