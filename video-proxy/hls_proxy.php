<?php
error_reporting(0);
set_time_limit(0);

$userSetHost = '';
$proxyUrl = locateBaseURL() . "hls_proxy.php";

function locateBaseURL() {
    global $userSetHost;

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    $domain = isset($userSetHost) && !empty($userSetHost) ? $protocol . $userSetHost : $protocol . $_SERVER['HTTP_HOST'];

    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : trim($scriptDir, '/\\');

    $baseUrl = rtrim($domain, '/') . '/' . $scriptDir;
    $baseUrl = rtrim($baseUrl, '/') . '/'; 
	
    return $baseUrl;
}

// Function to fetch content from a URL with optional additional headers
function fetchContent($url, $additionalHeaders = []) {
    $decodedData = base64_decode($_GET['data']);
    $parts = explode('|', $decodedData);
    $maxRedirects = 5;
    $headers = [];

    foreach ($parts as $headerData) {
        if (strpos($headerData, '=') !== false) {
            list($header, $value) = explode('=', $headerData, 2);
            $headers[] = trim($header) . ": " . trim($value, "'\"");
        }
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        $headers[] = "Range: " . $_SERVER['HTTP_RANGE'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Do not follow redirects automatically
    curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $redirectCount = 0;
    $response = '';
    $finalUrl = $url;

    do {
        curl_setopt($ch, CURLOPT_URL, $finalUrl);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (in_array($statusCode, [301, 302, 303, 307, 308])) {
            $redirectCount++;
            $finalUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        } else {
            break;
        }
    } while ($redirectCount < $maxRedirects);

    curl_close($ch);

    return ['content' => $response, 'finalUrl' => $finalUrl];
}

function isMasterRequest($queryParams) {
    return isset($queryParams['url']) && !isset($queryParams['url2']);
}

function rewriteUrls($content, $baseUrl, $proxyUrl, $data) {
    $lines = explode("\n", $content);
    $rewrittenLines = [];
    $isNextLineUri = false;

    foreach ($lines as $line) {
        if (empty(trim($line)) || $line[0] === '#') {
            if (preg_match('/URI="([^"]+)"/i', $line, $matches)) {
                $uri = $matches[1];
                if (strpos($uri, 'hls_proxy.php') === false) {
                    $rewrittenUri = $proxyUrl . '?url=' . urlencode($uri) . '&data=' . urlencode($data);
					if (strpos($line, '#EXT-X-KEY') !== false) {
						$rewrittenUri .= '&key=true';
					}					
                    $line = str_replace($uri, $rewrittenUri, $line);
                }
            }
            $rewrittenLines[] = $line;

            if (strpos($line, '#EXT-X-STREAM-INF') !== false) {
                $isNextLineUri = true;
            }
            continue;
        }

        $urlParam = $isNextLineUri ? 'url' : 'url2';

        if (!filter_var($line, FILTER_VALIDATE_URL)) {
            $line = rtrim($baseUrl, '/') . '/' . ltrim($line, '/');
        }

        if (strpos($line, 'hls_proxy.php') === false) {
            $rewrittenLines[] = $proxyUrl . "?$urlParam=" . urlencode($line) . '&data=' . urlencode($data);

        } else {
            $rewrittenLines[] = $line;
        }

        $isNextLineUri = false;
    }
	return implode("\n", $rewrittenLines);
}

function fetchEncryptionKey($url, $data) {
    if (isset($_GET['key']) && $_GET['key'] === 'true') {      
        $decodedData = base64_decode($data);
        $parts = explode('|', $decodedData);
        $maxRedirects = 5;
        $headers = [];

        foreach ($parts as $headerData) {
            if (strpos($headerData, '=') !== false) {
                list($header, $value) = explode('=', $headerData, 2);
                $headers[] = trim($header) . ": " . trim($value, "'\"");
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);




	$etag = '"' . md5($response) . '"';
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
	
    header('Content-Type: application/octet-stream');
    header('Cache-Control: max-age=3600'); 
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    header('ETag: ' . $etag);
	
    echo $response;
    exit;
	
    }
}

// Main processing logic
$isMaster = isMasterRequest($_GET);
$data = $_GET['data'] ?? '';
$requestUrl = $isMaster ? ($_GET['url'] ?? '') : ($_GET['url2'] ?? '');
fetchEncryptionKey($requestUrl, $_GET['data']);
$result = fetchContent($requestUrl, $data);
$content = $result['content'];
$finalUrl = $result['finalUrl'];
$baseUrl = dirname($finalUrl);

if ($isMaster) {
    $content = rewriteUrls($content, $baseUrl, $proxyUrl, $data);
}
header('Content-Type: application/x-mpegURL');
echo $content;




?>

