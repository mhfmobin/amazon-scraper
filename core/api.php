<?php
session_start();
header('Content-Type: application/json');

function generateToken() {
    return bin2hex(random_bytes(32));
}

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

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL is required']);
    exit;
}

$result = validateAndShortenAmazonLink($url);

if ($result['isValid']) {
    $command = "python3 main.py " . escapeshellarg($result['shortUrl']);
    
    // Execute the Python script and wait for it to complete
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        // Read the entire output
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        
        // Close all pipes
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // Close the process
        $return_value = proc_close($process);
        
        if ($return_value !== 0) {
            echo json_encode(['error' => 'Error executing Python script: ' . $errors]);
            exit;
        }
        
        $pythonResult = json_decode($output, true);

        if ($pythonResult === null) {
            echo json_encode(['error' => 'Error processing the URL: ' . $output]);
        } else {
            echo json_encode([
                'valid' => true,
                'originalUrl' => $result['originalUrl'],
                'shortUrl' => $result['shortUrl'],
                'asin' => $result['asin'],
                'error' => $pythonResult['error'] ?? null,
                'price_aed' => $pythonResult['price'] ?? null,
                'price_toman' => tomanPrice($pythonResult['price']) ?? null,
                'title' => $pythonResult['title'] ?? null,
                'images' => $pythonResult['images'] ?? null,
                'elapsedTime' => $pythonResult['elapsed_time'] ?? null
            ]);
        }
    } else {
        echo json_encode(['error' => 'Failed to execute Python script']);
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

function number($str) {
    $number = str_replace(',', '', $str);
    return floatval($number);
}

function tomanPrice($price) {
    $toman = ceil(number($price) * 16000);
    return number_format($toman, 0, '.', ',')
}