<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Пользователь не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = intval($_POST['friend_id']);

if ($user_id === $friend_id) {
    echo json_encode(['message' => 'Нельзя добавить самого себя!']);
    exit;
}

// Проверяем, не добавлен ли уже друг
$stmt = $pdo->prepare("
    SELECT * FROM friends 
    WHERE user_id = :user_id AND friend_id = :friend_id
");
$stmt->execute([':user_id' => $user_id, ':friend_id' => $friend_id]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['message' => 'Этот пользователь уже ваш друг!']);
    exit;
}

// Добавляем друга
$stmt = $pdo->prepare("
    INSERT INTO friends (user_id, friend_id) 
    VALUES (:user_id, :friend_id)
");
$stmt->execute([':user_id' => $user_id, ':friend_id' => $friend_id]);

echo json_encode(['message' => 'Пользователь добавлен в друзья!']);
?>
