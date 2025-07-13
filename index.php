<?php
// Authorize all origins
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

if (!isset($_POST['url'])) {
  http_response_code(400);
  echo json_encode(["error" => "Missing 'url' parameter"]);
  exit();
}

$url = $_POST['url'];
unset($_POST['url']);

// Forward request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true); // Pour récupérer les headers aussi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$error = curl_error($ch);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

http_response_code($httpCode);

// Empêche les erreurs HTTP/2 PROTOCOL_ERROR avec Transfer-Encoding: chunked
if (stripos($headers, "Transfer-Encoding: chunked") !== false) {
  header("Connection: close");
  header("Content-Length: " . strlen($body));
}

if ($error) {
  echo json_encode(["error" => $error]);
} else {
  echo $body;
}
?>

