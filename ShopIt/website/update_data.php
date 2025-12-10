<?php
$host = 'localhost';
$dbname = 'honeypot';
$username = 'root';     
$password = '';         

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$plain_password = $_POST['password'];

$sql = "UPDATE users_data SET first_name = ?, last_name = ?, password = ? WHERE id = ?";

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
