<?php
// Database credentials
$host = 'localhost';
$dbname = 'honeypot'; // Change this
$username = 'root';       // Change if needed
$password = '';           // Change if needed

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data
$id = $_POST['id'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$plain_password = $_POST['password'];

// SQL query to update data
$sql = "UPDATE users_data SET first_name = ?, last_name = ?, password = ? WHERE id = ?";

// Prepare and execute
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $first_name, $last_name, $hashed_password, $id);

if ($stmt->execute()) {
    echo "Record updated successfully.";
} else {
    echo "Error updating record: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
