<?php
/**
 * test_db.php
 * Run this file in your browser to find the correct database settings.
 */

$host = '127.0.0.1';
$port = '3308';

$test_configs = [
    ['user' => 'root', 'pass' => '', 'desc' => 'Default XAMPP/WAMP'],
    ['user' => 'root', 'pass' => 'root', 'desc' => 'Default MAMP'],
    ['user' => 'root', 'pass' => 'password', 'desc' => 'Common Default'],
];

echo "<h2>MySQL Connection Tester (Server: $host:$port)</h2>";

foreach ($test_configs as $config) {
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        
        echo "<div style='color:green; padding:10px; border:2px solid green; margin-bottom:10px;'>";
        echo "<b>SUCCESS!</b><br>";
        echo "Settings found: User: <b>{$config['user']}</b>, Password: <b>'{$config['pass']}'</b> ({$config['desc']})<br>";
        echo "<b>Action:</b> Update these settings in your <b>includes/db.php</b> file.";
        echo "</div>";
        exit;
    } catch (PDOException $e) {
        echo "<div style='color:gray; padding:5px; border:1px solid #ccc; margin-bottom:5px;'>";
        echo "Tried User: {$config['user']}, Pass: '{$config['pass']}' ... <span style='color:red;'>Failed</span>";
        echo "</div>";
    }
}

echo "<div style='color:red; margin-top:20px;'>";
echo "<b>None of the common defaults worked on port $port.</b><br>";
echo "If you set a custom password when installing XAMPP/MySQL, please find it and enter it in <b>includes/db.php</b>.";
echo "</div>";
?>
