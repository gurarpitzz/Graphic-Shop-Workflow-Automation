<?php
// server/fix_db.php
require 'db_connect.php';

echo "<h3>Repairing Database Schema...</h3>";

try {
    // Add missing columns one by one
    $columns = [
        "thumbnail_path VARCHAR(255)",
        "design_description TEXT",
        "total_amount DECIMAL(10,2) DEFAULT 0.00",
        "advance_paid DECIMAL(10,2) DEFAULT 0.00"
    ];

    foreach ($columns as $col) {
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN $col");
            echo "✅ Added column: $col <br>";
        } catch (Exception $e) {
            // Likely already exists
            echo "ℹ️ Column already exists or skipped: $col <br>";
        }
    }

    echo "<br><b>Database Fixed!</b> Try saving the order again.";
    echo "<br><a href='../index.html'>Back to Website</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
