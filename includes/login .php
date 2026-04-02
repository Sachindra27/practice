<?php
session_start();
header("Content-Type: application/json"); // return JSON
include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST requests allowed"]);
    exit();
}

// Get JSON body if sent as raw JSON
$data = json_decode(file_get_contents("php://input"), true);

// Fallback to form data
$email = $data['email'] ?? $_POST['email'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Email and password are required"]);
    exit();
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Invalid credentials"]);
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Invalid credentials"]);
    exit();
}

// Login success: set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];

// Return success JSON
echo json_encode([
    "message" => "Login successful",
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $email
    ]
]);
?>