
<?php
// db.php

$host = 'localhost';  // Хост базы данных
$dbname = 'users';  // Название базы данных
$username = 'root';  // Имя пользователя
$password = '';  // Пароль

// Попытка установить соединение с базой данных
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Включение исключений для ошибок
} catch (PDOException $e) {
    // Если ошибка, выводим сообщение
    die("Ошибка подключения: " . $e->getMessage());
}
?>