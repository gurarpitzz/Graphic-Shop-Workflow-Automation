<?php
// server/test_db.php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Try empty first

echo "<h3>MySQL Connection Test</h3>";

try {
    $dsn = "mysql:host=$host;charset=utf8";
    try {
        $pdo = new PDO($dsn, $user, $pass);
    } catch (PDOException $e1) {
        $pass = 'root';
        $pdo = new PDO($dsn, $user, $pass);
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Success! PHP connected to MySQL.<br>";
    echo "Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br><br>";
    
    $pdo->query("CREATE DATABASE IF NOT EXISTS graphic_shop_db");
    echo "✅ Database 'graphic_shop_db' exists/created.<br>";
    
    echo "<br><a href='install.php'>Now run Install.php</a>";

} catch (PDOException $e) {
    echo "❌ <b>FAILED TO CONNECT</b><br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "Error Message: " . $e->getMessage() . "<br><br>";
    
    if ($e->getCode() == 1045) {
        echo "<b>TIP:</b> Access denied. This means 'root' with empty password failed. <br>";
        echo "Check your XAMPP configuration for the MySQL password.";
    }
    if ($e->getCode() == 2002) {
        echo "<b>TIP:</b> Target machine refused it. This means <b>MySQL is NOT STARTED</b> in XAMPP.";
    }
}
?>
