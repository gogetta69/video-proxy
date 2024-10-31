<?php
error_reporting(0);
set_time_limit(0);
ob_end_clean();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Authorization, Accept");

if (isset($_GET['data']) && !empty($_GET['data']) && isset($_GET['url']) && !empty($_GET['url'])) {
    $decodedData = base64_decode($_GET['data']);
    $parts = explode('|', $decodedData);
    $url = $_GET['url'];

    $httpOptions = [
        'http' => [
            'method' => 'GET',
            'follow_location' => 1,
            'max_redirects' => 5 
        ]
    ];

    foreach ($parts as $headerData) {
        list($header, $value) = explode('=', $headerData);
        $httpOptions['http']['header'][] = "$header: $value";
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        $httpOptions['http']['header'][] = "Range: " . $_SERVER['HTTP_RANGE'];
    }

    $context = stream_context_create($httpOptions);

    $headers = get_headers($url, 1, $context);

    header($headers[0]);
    if (isset($headers['Content-Type'])) {
        header('Content-Type: ' . $headers['Content-Type']);
    }
    if (isset($headers['Content-Length'])) {
        header('Content-Length: ' . $headers['Content-Length']);
    }
    if (isset($headers['Accept-Ranges'])) {
        header('Accept-Ranges: ' . $headers['Accept-Ranges']);
    }
    if (isset($headers['Content-Range'])) {
        header('Content-Range: ' . $headers['Content-Range']);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
        exit;
    }

    $fp = fopen($url, 'rb', false, $context);
    while (!feof($fp)) {
        echo fread($fp, 1024 * 256);
        flush();
    }
    fclose($fp);
} else {
    echo "Missing the data or url parameter.";
}

?>
