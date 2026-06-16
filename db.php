<?php
// includes/db.php

$host = '127.0.0.1:3308'; 
$db_name = 'agri_social_db'; 
$username = 'root'; 
$password = '123456'; // ASIRRATTI PASSWORD GALCHUU KEE MIRKANEESSI

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Database connection error
    die("Database connection failed: " . $e->getMessage());
}
?>
    
          