<?php
// server/assign_workers.php
require 'db_connect.php';

$workers = [
    ['admin', 'admin', null],
    ['manish', 'accountant', null],
    ['harshita', 'worker', 'designer1, printing, stampw1'],
    ['kavita', 'worker', 'designer 2'],
    ['rajesh', 'worker', 'fabricator1, installer1'],
    ['narinder', 'worker', 'designer 3'],
    ['dev', 'worker', 'fabricator2, installer2'],
    ['bablu', 'worker', 'fabricator'],
    ['vickey', 'worker', 'printing'],
    ['arvind', 'worker', 'fabricator3, installer3, printing']
];

echo "<h3>Updating Workforce Identities...</h3>";

try {
    foreach ($workers as $w) {
        $username = $w[0];
        $role = $w[1];
        $domain = $w[2];
        
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        $existing = $check->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE users SET role = ?, domain = ? WHERE id = ?");
            $stmt->execute([$role, $domain, $existing['id']]);
            echo "✅ Updated: $username ($role) -> [$domain]<br>";
        } else {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, domain) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $domain]);
            echo "✨ Created: $username ($role) -> [$domain]<br>";
        }
    }
    echo "<br><b>Workforce ready. Default password for all: password123</b>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
