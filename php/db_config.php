<?php
$host = 'localhost'; // Usually localhost, adjust if needed
$dbname = 'hillardcorp_bailsafe'; // Your database name
$username = 'hillardcorp_bailsafeuser'; // From cPanel > MySQL Databases
$password = '.$wuB-Q=n5!{'; // From cPanel > MySQL Databases

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>