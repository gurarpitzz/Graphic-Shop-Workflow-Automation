<?php
// server/install.php
require 'db_connect.php';

echo "<h2>Graphic Shop Operations System Installer (v2)</h2>";

try {
    // 1. Read SQL file
    $sql = file_get_contents('../database.sql');
    $pdo->exec($sql);
    echo "✅ Database Schema Imported.<br>";

    // 2. Add Default Accounts
    $users = [
        ['admin', 'admin', null],
        ['manish', 'accountant', null],
        ['harshita', 'worker', 'Designing, Machine Operations, Stamp'],
        ['kavita', 'worker', 'Designing'],
        ['rajesh', 'worker', 'Fabrication, Installation'],
        ['narinder', 'worker', 'Designing'],
        ['dev', 'worker', 'Fabrication, Installation'],
        ['bablu', 'worker', 'Welding'],
        ['vickey', 'worker', 'Machine Operations'],
        ['arvind', 'worker', 'Fabrication, Installation']
    ];

    foreach ($users as $u) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $check->execute([$u[0]]);
        if (!$check->fetch()) {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, domain) VALUES (?, ?, ?, ?)");
            $stmt->execute([$u[0], $hash, $u[1], $u[2]]);
            echo "✅ Created User: {$u[0]} ({$u[1]})<br>";
        }
    }

    echo "<br><b>SUCCESS! System v2 is ready.</b><br>";
    echo "<a href='../index.html'>Go to Login</a>";

} catch (Exception $e) {
    echo "❌ Error during installation: " . $e->getMessage();
}
?>
