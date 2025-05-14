<?php
session_start();

// Подключение к БД
$host = 'localhost';
$dbname = 'u68643';
$username_db = 'u68643';
$password_db = '7475566';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Если пользователь уже авторизован — отправляем в профиль
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
}

$login_error = '';
$generated_login = null;
$generated_password = null;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['login'] ?? '';
    $password = $_POST['pass'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: profile.php');
        exit();
    } else {
        $_SESSION['login_error'] = 'Неверный логин или пароль.';
        header('Location: login.php');
        exit();
    }
}

// Проверяем наличие кук с данными после регистрации
if (isset($_COOKIE['login']) && isset($_COOKIE['pass'])) {
    $generated_login = $_COOKIE['login'];
    $generated_password = $_COOKIE['pass'];

    // Удаляем куки после первого отображения
    setcookie('login', '', time() - 3600);
    setcookie('pass', '', time() - 3600);
}

$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        .container { max-width: 400px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .info, .error { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .info { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; margin-top: 5px; }
        input[type="submit"] { margin-top: 20px; padding: 10px; width: 100%; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h1>Вход</h1>

    <?php if ($generated_login && $generated_password): ?>
        <div class="info">
            <p>Ваш логин: <strong><?= htmlspecialchars($generated_login) ?></strong></p>
            <p>Ваш пароль: <strong><?= htmlspecialchars($generated_password) ?></strong></p>
            <p>Сохраните эти данные для дальнейшего входа.</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($login_error)): ?>
        <div class="error"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label for="login">Логин:</label>
        <input type="text" id="login" name="login" required>

        <label for="pass">Пароль:</label>
        <input type="password" id="pass" name="pass" required>

        <input type="submit" value="Войти">
    </form>
</div>

</body>
</html>
