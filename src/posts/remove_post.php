<?php
try {
    include "connection.php";
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $stmt = $pdo->prepare("DELETE FROM Posts WHERE post_id = :post_id;");
    $stmt->bindValue(":post_id", $_POST['post_id'], PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
