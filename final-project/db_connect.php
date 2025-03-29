<?php
// Database credentials
$db_host = "localhost";
$db_name = "soccer_team_management"; // Update this to match your database name
$db_user = "root"; // Update with your username
$db_pass = ""; // Update with your password (XAMPP default password is blank)

try {
    // Create a PDO instance
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Only show error message if not in production
    die("Connection failed: " . $e->getMessage());
}
?> 