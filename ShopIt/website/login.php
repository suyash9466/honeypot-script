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
    $username = $_POST['email'];
    $password = $_POST['password'];

    // Check if username exists
    $stmt = $conn->prepare("SELECT id, password FROM users_data WHERE id = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Verify credentials
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $storedPassword);
        $stmt->fetch();

        if ($password === $storedPassword) {
            // Login successful
            $_SESSION['user_id'] = $id;
            $_SESSION['success'] = "✅ Login successful!";
            header("Location: dashboard");
            exit;
        } else {
            $_SESSION['error'] = "❌ Incorrect password.";
            header("Location: login");
            exit;
        }
    } else {
        $_SESSION['error'] = "❌ Username not found.";
        header("Location: login");
        exit;
    }

    $stmt->close();
}

$conn->close();
?>
