<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "honeypot";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $retypePassword = $_POST['retype-password'];

    if ($password !== $retypePassword) {
        $_SESSION['error'] = "❌ Passwords do not match.";
        header("Location: registration");
        exit;
    }

    // Store password as plain text (not recommended)
    $stmt = $conn->prepare("INSERT INTO users_data (first_name, last_name, id, username, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $username, $password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Registration successful! You can now log in.";
        header("Location: login");
        exit;
    } else {
        $_SESSION['error'] = "❌ Error: " . $stmt->error;
        header("Location: registration");
        exit;
    }

    $stmt->close();
}

$conn->close();
?>
