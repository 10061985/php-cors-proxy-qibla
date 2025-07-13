<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  echo json_encode(["status" => "ok", "message" => "CORS preflight"]);
  exit();
}

// Get raw POST body
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Validate input
if (!isset($data['url'])) {
  http_response_code(400);
  echo json_encode(["error" => "Missing 'url' parameter"]);
  exit();
}

$url = $data['url'];
unset($data['url']); // remove before forwarding

// Setup cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
}

// Execute
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Error handling
if ($curlError) {
  http_response_code(500);
  echo json_encode(["error" => $curlError]);
  exit();
}

if (stripos($contentType, "html") !== false || stripos($response, "<!DOCTYPE") !== false) {
  http_response_code($httpCode);
  echo json_encode([
    "error" => "Unexpected HTML response",
    "details" => substr($response, 0, 200)
  ]);
  exit();
}

// Return original JSON
http_response_code($httpCode);
echo $response;
