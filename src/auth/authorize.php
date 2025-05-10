<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

// .envファイルの読み込み（2階層上がる）
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 環境変数の取得
$client_id = $_ENV['SPOTIFY_CLIENT_ID'];
$redirect_uri = $_ENV['REDIRECT_URI'];
$scope = 'streaming user-read-playback-state user-modify-playback-state user-read-currently-playing user-read-recently-played user-library-read user-read-playback-position playlist-read-private playlist-read-collaborative';

// CSRF対策：ランダムな state を生成してセッションに保存
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// 認可URL生成
$params = http_build_query([
    'client_id' => $client_id,
    'response_type' => 'code',
    'redirect_uri' => $redirect_uri,
    'scope' => $scope,
    'state' => $state,
]);

$auth_url = 'https://accounts.spotify.com/authorize?' . $params;
header('Location: ' . $auth_url);
exit();
