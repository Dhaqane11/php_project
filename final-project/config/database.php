<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // Change to your MySQL username
define('DB_PASS', '');       // Change to your MySQL password
define('DB_NAME', 'soccer_team_management');

// Create a database connection
function connectDB() {
    try {
        $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
} 