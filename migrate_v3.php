<?php
// server/migrate_v3.php
require 'db_connect.php';

echo "<h3>Migrating to v3 (Vault & Analytics)...</h3>";

try {
    // 1. Order Media Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type ENUM('Design', 'Bill', 'Reference', 'Other') DEFAULT 'Other',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
    echo "✅ Order Media table created.<br>";

    // 2. Add is_archived to Orders
    try {
        @$pdo->exec("ALTER TABLE orders ADD COLUMN is_archived BOOLEAN DEFAULT FALSE");
        echo "✅ Checked is_archived.<br>";
    } catch(Exception $e) {}

    // 3. Add Noise tracking to Customers
    try {
        @$pdo->exec("ALTER TABLE customers ADD COLUMN rate_request_count INT DEFAULT 0");
    } catch(Exception $e) {}
    try {
        @$pdo->exec("ALTER TABLE customers ADD COLUMN actual_order_count INT DEFAULT 0");
    } catch(Exception $e) {}
    echo "✅ Checked noise tracking columns.<br>";

    echo "<br><b>Database Sync Complete!</b>";

} catch (Exception $e) {
    echo "❌ Migration Error: " . $e->getMessage();
}
?>
