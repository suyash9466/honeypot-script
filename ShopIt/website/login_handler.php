<?php

session_start();

// Security headers
header_remove('X-Powered-By');
header("Content-Security-Policy: default-src 'self'");
ini_set('expose_php', 'off');
error_reporting(0);

define('GEOIP_DB', 'C:\\xampp\\geoip\\GeoLite2-City.mmdb');
define('LOG_DIR', 'C:\\xampp\\logs\\');
define('PYTHON_EXE', 'C:\\Users\\sarth\\AppData\\Local\\Programs\\Python\\Python313\\python.exe');

function clean_input($data) {
    return htmlspecialchars(
        preg_replace('/[^-\w@.\- ]/', '', trim($data)),
        ENT_QUOTES, 'UTF-8'
    );
}

// --- Grab the form payload (clean for logs) ---
$email_post = isset($_POST['email']) ? $_POST['email'] : '';
$password_post = isset($_POST['password']) ? $_POST['password'] : '';

// Logging: capture request for all attempts
$request = [
    'timestamp' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'headers' => function_exists('getallheaders') ? getallheaders() : [],
    'payload' => [
        'get' => $_GET,
        'post' => array_map('clean_input', $_POST),
        'files' => $_FILES
    ],
    'server' => [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct'
    ]
];

// GeoIP lookup
if (file_exists(GEOIP_DB)) {
    $geo_cmd = sprintf('"%s" "C:\\xampp\\htdocs\ShopIt\\honeypot\\honeypot.py" geoip "%s"', PYTHON_EXE, $request['ip']);
    $request['geo'] = json_decode(shell_exec($geo_cmd), true) ?? ['error' => 'Lookup failed'];
} else {
    $request['geo'] = ['error' => 'GeoIP DB missing'];
}

// Write log (all attempts)
if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);
file_put_contents(
    LOG_DIR.'raw_payloads.log',
    json_encode($request, JSON_PRETTY_PRINT)."\n",
    FILE_APPEND | LOCK_EX
);

$ip_log_dir = LOG_DIR.'attacks\\by_ip\\';
if (!is_dir($ip_log_dir)) mkdir($ip_log_dir, 0755, true);
file_put_contents(
    $ip_log_dir.$request['ip'].'.log',
    json_encode($request)."\n",
    FILE_APPEND
);

if (isset($request['geo']['country_code'])) {
    $country_log_dir = LOG_DIR.'attacks\\by_country\\';
    if (!is_dir($country_log_dir)) mkdir($country_log_dir, 0755, true);
    file_put_contents(
        $country_log_dir.$request['geo']['country_code'].'.log',
        json_encode($request)."\n",
        FILE_APPEND
    );
}

$python_cmd = sprintf(
    'start /B "" "%s" "C:\\xampp\\htdocs\\ShopIt\\honeypot\\honeypot.py" analyze "%s"',
    PYTHON_EXE,
    escapeshellarg(json_encode($request))
);
pclose(popen($python_cmd, 'r'));

// --- Main Login Logic (after logging) ---

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "honeypot";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    $_SESSION['error'] = "❌ Internal server error.";
    header("Location: login");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $email_post;
    $password = $password_post;

    // Check if username exists
    $stmt = $conn->prepare("SELECT id, password FROM users_data WHERE id = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Verify credentials
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $storedPassword);
        $stmt->fetch();

        if ($password === $storedPassword) { // Plaintext! (not for prod)
            $_SESSION['user_id'] = $id;
            $_SESSION['success'] = "✅ Login successful!";
            $stmt->close();
            $conn->close();
            header("Location: dashboard");
            exit;
        } else {
            $_SESSION['error'] = "❌ Incorrect password.";
            $stmt->close();
            $conn->close();
            header("Location: login");
            exit;
        }
    } else {
        $_SESSION['error'] = "❌ Username not found.";
        $stmt->close();
        $conn->close();
        header("Location: login");
        exit;
    }
}

$conn->close();
?>
