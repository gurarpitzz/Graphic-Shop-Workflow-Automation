<?php
require 'db_connect.php';
$stmt = $pdo->query("SELECT id, username, role, domain FROM users");
$users = $stmt->fetchAll();
header('Content-Type: text/plain');
foreach ($users as $u) {
    echo "ID: {$u['id']} | User: {$u['username']} | Role: {$u['role']} | Domain: [{$u['domain']}]\n";
}
?>
