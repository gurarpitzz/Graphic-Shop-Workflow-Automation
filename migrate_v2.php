<?php
// server/migrate_v2.php
require 'db_connect.php';

echo "<h3>Migrating to v2...</h3>";

try {
    // 1. Create Customers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cust_id VARCHAR(20) UNIQUE,
        name VARCHAR(100) NOT NULL,
        mobile_number VARCHAR(20) NOT NULL UNIQUE,
        is_recurring BOOLEAN DEFAULT FALSE,
        blacklist_status BOOLEAN DEFAULT FALSE,
        payment_pattern_score INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Customers table verified.<br>";

    // 2. Update Orders Table
    $orderCols = [
        "customer_id INT",
        "order_id_code VARCHAR(20) UNIQUE",
        "source ENUM('WhatsApp', 'In-Person') DEFAULT 'In-Person'",
        "whatsapp_link VARCHAR(255)",
        "pancake_link VARCHAR(255)",
        "telegram_link VARCHAR(255)",
        "mail_link VARCHAR(255)",
        "material_used VARCHAR(100)",
        "sizes VARCHAR(100)",
        "bill_number VARCHAR(50)",
        "bill_date DATE",
        "tally_invoice_path VARCHAR(255)"
    ];

    foreach ($orderCols as $col) {
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN $col");
            echo "✅ Added order column: $col <br>";
        } catch(Exception $e) {}
    }

    // 3. Update Order Stages
    $stageCols = [
        "responsible_person_id INT",
        "start_date DATE",
        "finish_date DATE",
        "extra_notes TEXT",
        "is_required BOOLEAN DEFAULT TRUE",
        "completed_by INT"
    ];

    foreach ($stageCols as $col) {
        try {
            $pdo->exec("ALTER TABLE order_stages ADD COLUMN $col");
            echo "✅ Added stage column: $col <br>";
        } catch(Exception $e) {}
    }

    echo "<br><b>Migration Complete!</b>";

} catch (Exception $e) {
    echo "❌ Migration Error: " . $e->getMessage();
}
?>
