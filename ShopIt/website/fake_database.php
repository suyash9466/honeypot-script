<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "database_honeypot";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'><tr>";

    // Print table headers
    while ($field = $result->fetch_field()) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";

    // Print rows
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $data) {
            echo "<td>" . htmlspecialchars($data) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No records found.";
}

$conn->close();
?>
