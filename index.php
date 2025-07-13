<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  echo json_encode(["status" => "ok", "message" => "CORS preflight"]);
  exit();
}

if (!isset($_POST['url'])) {
  http_response_code(400);
  echo json_encode(["error" => "Missing 'url' parameter"]);
  exit();
}

$url = $_POST['url'];
unset($_POST['url']);

// Préparation de la requête
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false); // pas d'en-têtes HTTP bruts
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // sécurité timeout

// Support POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
}

// Exécution
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// En cas d’erreur réseau
if ($curlError) {
  http_response_code(500);
  echo json_encode(["error" => $curlError]);
  exit();
}

// Si la réponse semble être du HTML (erreur Google Apps Script par ex)
if (stripos($contentType, "html") !== false || stripos($response, "<!DOCTYPE") !== false) {
  http_response_code($httpCode);
  echo json_encode([
    "error" => "Unexpected HTML response",
    "details" => substr($response, 0, 200) // on affiche une partie seulement
  ]);
  exit();
}

// Si la réponse est déjà JSON, on la renvoie directement
http_response_code($httpCode);
echo $response;

