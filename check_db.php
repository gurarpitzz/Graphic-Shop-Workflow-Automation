<?php
require_once 'server/db_connect.php';
try {
    $orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stages = $pdo->query("SELECT COUNT(*) FROM order_stages")->fetchColumn();
    echo "Orders: $orders\n";
    echo "Customers: $customers\n";
    echo "Users: $users\n";
    echo "Stages: $stages\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
