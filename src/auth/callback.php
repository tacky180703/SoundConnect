<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// .env経由などで取得推奨
$client_id = $_ENV['SPOTIFY_CLIENT_ID'];
$client_secret = $_ENV['SPOTIFY_CLIENT_SECRET'];
$redirect_uri = $_ENV['REDIRECT_URI'];

var_dump($_ENV['SPOTIFY_CLIENT_ID']);  // 値が出ればOK

if (isset($_GET['code'])) {
    // Spotify から返ってきた state を確認
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        echo "State mismatch. Possible CSRF attack.";
        exit();
    }

    $code = $_GET['code'];

    $token_url = 'https://accounts.spotify.com/api/token';
    $data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($token_url, false, $context);

    if ($response === false) {
        echo "Error: Failed to connect to Spotify API";
        exit();
    }

    $response_data = json_decode($response, true);

    if (isset($response_data['access_token'])) {
        $_SESSION['access_token'] = $response_data['access_token'];
        $_SESSION['refresh_token'] = $response_data['refresh_token'];
        $_SESSION['expires_at'] = time() + $response_data['expires_in'];

        header('Location: ../../public/home.php');
        exit();
    } else {
        echo "Error retrieving access token: " . $response_data['error_description'] ?? 'Unknown error';
    }
} else {
    echo "Error: no code received";
}
