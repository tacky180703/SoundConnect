<?php
session_start();

// 認証済みかどうか
if (!isset($_SESSION['access_token'])) {
  header('Location: ../src/auth/authorize.php');
  exit();
}

// トークン期限切れならリフレッシュ
if (isset($_SESSION['expires_at']) && time() >= $_SESSION['expires_at']) {
  header('Location: ../src/auth/refresh_token.php');
  exit();
}

// 正常ならホーム画面へ
header('Location: home.php');
exit();
