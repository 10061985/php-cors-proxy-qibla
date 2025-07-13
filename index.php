<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");

// OPTIONS request (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifie si l'URL cible est définie
$targetUrl = $_POST['url'] ?? $_GET['url'] ?? null;
if (!$targetUrl) {
    echo json_encode(["error" => "Missing 'url' parameter."]);
    http_response_code(400);
    exit();
}

// Initialise la session cURL
$ch = curl_init();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_diff_key($_POST, ['url' => ''])));
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $putData);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($putData));
        break;

    case 'GET':
        $query = http_build_query(array_diff_key($_GET, ['url' => '']));
        $targetUrl .= strpos($targetUrl, '?') === false ? "?$query" : "&$query";
        break;

    default:
        echo json_encode(["error" => "Unsupported method: $method"]);
        http_response_code(405);
        exit();
}

curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // suit les redirections
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Ajoute les headers d'origine
$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) !== 'host') {
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Exécute la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    http_response_code(500);
} else {
    http_response_code($httpCode);
    echo $response;
}

curl_close($ch);
