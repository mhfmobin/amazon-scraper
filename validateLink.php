<?php
session_start();
header('Content-Type: application/json');

// Generate a secure token
function generateToken() {
    return bin2hex(random_bytes(32));
}

// Verify the token
function verifyToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_token') {
    $token = generateToken();
    $_SESSION['csrf_token'] = $token;
    echo json_encode(['token' => $token]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';
$token = $input['token'] ?? '';

if (!verifyToken($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$result = validateAndShortenAmazonLink($url);

if ($result['isValid']) {
    $command = "python3 main.py " . escapeshellarg($result['shortUrl']);
    $output = shell_exec($command);
    $pythonResult = json_decode($output, true);

    if ($pythonResult === null) {
        echo json_encode(['error' => 'Error processing the URL']);
    } else {
        echo json_encode([
            'valid' => true,
            'originalUrl' => $result['originalUrl'],
            'shortUrl' => $result['shortUrl'],
            'asin' => $result['asin'],
            'error' => $pythonResult['error'],
            'price' => $pythonResult['price'],
            'title' => $pythonResult['title'],
            'elapsedTime' => $pythonResult['elapsed_time']
        ]);
    }
} else {
    echo json_encode([
        'valid' => false,
        'originalUrl' => $result['originalUrl'],
        'error' => 'Invalid Amazon.ae URL'
    ]);
}

function validateAndShortenAmazonLink($url) {
    // General pattern for Amazon URLs
    // $pattern = '/https?:\/\/(www\.)?amazon\.([a-z\.]{2,6})\/(.*\/)?dp\/([A-Z0-9]{10})/i';

    // Pattern for amazon.ae URLs
    $pattern = '/https?:\/\/(www\.)?amazon\.ae\/(.*\/)?dp\/([A-Z0-9]{10})/i';
    
    if (preg_match($pattern, $url, $matches)) {
        $asin = $matches[3];

        $shortUrl = "https://www.amazon.ae/dp/{$asin}/";
        return [
            'isValid' => true,
            'originalUrl' => $url,
            'shortUrl' => $shortUrl,
            'asin' => $asin
        ];
    } else {
        return [
            'isValid' => false,
            'originalUrl' => $url,
            'shortUrl' => null,
            'asin' => null
        ];
    }
}


