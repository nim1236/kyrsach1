<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Пользователь не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.id, u.Nick 
    FROM friends f
    JOIN user u ON u.id = f.friend_id 
    WHERE f.user_id = :user_id
");
$stmt->execute([':user_id' => $user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['friends' => $friends]);
