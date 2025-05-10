<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 安全な方法で読み込む（.env 使用を推奨）
$client_id = $_ENV['SPOTIFY_CLIENT_ID'];
$client_secret = $_ENV['SPOTIFY_CLIENT_SECRET'];
$refresh_token = $_SESSION['refresh_token'];

function getNewAccessToken($client_id, $client_secret, $refresh_token)
{
    $token_url = 'https://accounts.spotify.com/api/token';
    $data = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
    ];

    $auth_header = base64_encode("$client_id:$client_secret");

    $options = [
        'http' => [
            'header' => [
                "Authorization: Basic $auth_header",
                "Content-Type: application/x-www-form-urlencoded"
            ],
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($token_url, false, $context);

    if ($response === false) {
        return ['success' => false, 'message' => 'Connection failed: ' . print_r(error_get_last(), true)];
    }

    $tokens = json_decode($response, true);

    if (isset($tokens['access_token'])) {
        $_SESSION['access_token'] = $tokens['access_token'];
        $_SESSION['expires_at'] = time() + $tokens['expires_in'];
        if (isset($tokens['refresh_token'])) {
            $_SESSION['refresh_token'] = $tokens['refresh_token'];
        }
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => $tokens['error_description'] ?? 'Unknown error'];
    }
}

$result = getNewAccessToken($client_id, $client_secret, $refresh_token);

if (!$refresh_token) {
    echo "refresh_token がセッションに存在しません";
    exit;
}
if ($result['success']) {
    header('Location: ../../public/home.php');
    exit;
} else {
    echo "アクセストークン取得失敗<br>";
    echo "エラーメッセージ: " . htmlspecialchars($result['message']);
}
