<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));

    if (!empty($username) && !empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Этот email уже зарегистрирован.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO user (Nick, Email, Password) VALUES (:username, :email, :password)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);

                $userId = $pdo->lastInsertId();
                $sessionId = bin2hex(random_bytes(16)); // Генерируем уникальный идентификатор
                $pdo->prepare("UPDATE user SET session_id = :session_id WHERE id = :id")
                    ->execute([':session_id' => $sessionId, ':id' => $userId]);

                // Перенаправляем с идентификатором
                header("Location: account.php?session_id=$sessionId");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Ошибка базы данных: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Пожалуйста, заполните все поля!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background">
        <!-- Стили фоновых элементов -->
    </div>
    <div class="container">
        <h1>Регистрация</h1>
        <form action="index.php" method="POST">
            <label for="username">Никнейм</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Почта</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Зарегистрироваться</button>

            <?php if (!empty($error)): ?>
                <p style="color: red; margin-top: 10px;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </form>
        <p>Уже есть аккаунт?</p>
        <a href="login.php" class="secondary-button">Войти</a>
    </div>
</body>
</html>
