<?php

// Database configuration
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "honeypot_admin";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data and sanitize it
$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

// Query to check the user
$sql = "SELECT * FROM admin_data WHERE username='$username'";
$result = $conn->query($sql);

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Plain text password comparison (NOT RECOMMENDED for production)
    if ($password === $row['password']) {
        session_start();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admindashboard");
        exit();
    } else {
        echo "Invalid password.";
    }
} else {
    echo "User not found.";
}
?>