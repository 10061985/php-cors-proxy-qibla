<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

if (!isset($_GET['url'])) {
  http_response_code(400);
  echo json_encode(["error" => "Missing 'url' parameter."]);
  exit;
}

$url = $_GET['url'];
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$body = file_get_contents('php://input');

$ch = curl_init();
curl_setopt_array($ch, [
   CURLOPT_URL => $url,
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_FOLLOWLOCATION => true,
   CURLOPT_CUSTOMREQUEST => $method,
   CURLOPT_HTTPHEADER => array_filter(array_map(
     fn($k, $v) => "$k: $v",
     array_keys($headers),
     array_values($headers)
   )),
   CURLOPT_POSTFIELDS => ($method === 'POST' || $method === 'PUT') ? $body : null,
]);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

http_response_code($info['http_code']);
header("Content-Type: " . ($info['content_type'] ?? 'application/json'));
echo $response;
