<?php

// Security header setup
header_remove('X-Powered-By');
header("Content-Security-Policy: default-src 'self'");
ini_set('expose_php', 'off');
error_reporting(0);

// Constants
define('GEOIP_DB', 'C:\\xampp\\geoip\\GeoLite2-City.mmdb');
define('LOG_DIR', 'C:\\xampp\\logs\\admin\\');
define('PYTHON_EXE', 'C:\\Users\\sarth\\AppData\\Local\\Programs\\Python\\Python313\\python.exe');

if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);

// Security trap
if (!empty($_POST['security_check'])) {
    file_put_contents(
        LOG_DIR.'honeypot_traps.log',
        date('[Y-m-d H:i:s]')." Bot detected - security_check field used\n",
        FILE_APPEND
    );
    header("HTTP/1.0 403 Forbidden");
    exit();
}

// Collect and sanitize POST data
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Prepare request meta/log
$request = [
    'timestamp' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'headers' => function_exists('getallheaders') ? getallheaders() : [],
    'payload' => [
        'username' => $username,
        'password' => $password
    ],
    'server' => [
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
        'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct'
    ]
];

// Geo IP lookup
if (file_exists(GEOIP_DB)) {
    $geo_cmd = sprintf(
        '"%s" "C:\\xampp\\htdocs\\ShopIt\\honeypot\\honeypot.py" geoip "%s" 2>&1',
        PYTHON_EXE,
        $request['ip']
    );
    $geo_result = shell_exec($geo_cmd);
    $request['geo'] = $geo_result ? json_decode($geo_result, true) : ['error' => 'Lookup failed'];
} else {
    $api_response = @file_get_contents("http://ip-api.com/json/{$request['ip']}?fields=66846719");
    $request['geo'] = $api_response ? json_decode($api_response, true) : ['error' => 'API lookup failed'];
}

// Log all admin requests
file_put_contents(
    LOG_DIR.'admin_requests.log',
    json_encode($request, JSON_PRETTY_PRINT)."\n",
    FILE_APPEND | LOCK_EX
);

// Suspicious username logging
$suspicious_users = ['admin', 'administrator', 'root'];
if (in_array(strtolower($username), $suspicious_users)) {
    file_put_contents(
        LOG_DIR.'suspicious_logins.log',
        date('[Y-m-d H:i:s]')." Suspicious admin login attempt: {$username} from {$request['ip']}\n",
        FILE_APPEND
    );
}

// Fire off analysis script (non-blocking)
$python_cmd = sprintf(
    'start /B "" "%s" "C:\\xampp\\htdocs\\ShopIt\\honeypot\\honeypot.py" admin_analyze "%s"',
    PYTHON_EXE,
    escapeshellarg(json_encode($request))
);
pclose(popen($python_cmd, 'r'));

// Database connection and login process
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "honeypot_admin";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    // Log DB error for admin later (not public)
    header("Location: adminlogin.html");
    exit();
}

// Prevent SQL injection
$safe_username = mysqli_real_escape_string($conn, $username);
$query = "SELECT * FROM admin_data WHERE username='$safe_username'";
$result = $conn->query($query);

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if ($password === $row['password']) { // Not safe for production
        session_start();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $conn->close();
        header("Location: adminlogin.html");
        exit();
    } else {
        $conn->close();
        header("Location: adminlogin.html");
        exit();
    }
} else {
    $conn->close();
    header("Location: adminlogin.html");
    exit();
}
?>
