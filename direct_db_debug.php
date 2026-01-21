<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; 
$db   = 'graphic_shop_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, username, role, domain FROM users");
    $users = $stmt->fetchAll();
    foreach ($users as $u) {
        echo "ID: {$u['id']} | User: {$u['username']} | Role: {$u['role']} | Domain: [{$u['domain']}]\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
