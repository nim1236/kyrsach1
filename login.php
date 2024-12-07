<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));

    $stmt = $pdo->prepare("SELECT id, Password FROM user WHERE Email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {
        $sessionId = bin2hex(random_bytes(16));
        $pdo->prepare("UPDATE user SET session_id = :session_id WHERE id = :id")
            ->execute([':session_id' => $sessionId, ':id' => $user['id']]);

        header("Location: account.php?session_id=$sessionId");
        exit;
    } else {
        $error = "Неверный email или пароль.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
</head>
<body>
    <h2>Вход</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
</body>
</html>
