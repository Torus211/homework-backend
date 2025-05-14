<?php
session_start();
// Подключение к базе данных
$host = 'localhost';
$dbname = 'u68643'; // Название базы данных
$username_db = 'u68643'; // Логин пользователя MySQL
$password_db = '7475566'; // Пароль пользователя MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Пользователь не найден.");
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование пользователя</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="email"], input[type="date"] { width: 100%; padding: 8px; margin-top: 5px; }
        select { width: 100%; padding: 8px; margin-top: 5px; }
        textarea { width: 100%; height: 100px; padding: 8px; margin-top: 5px; }
        input[type="submit"] { margin-top: 20px; padding: 10px; width: 100%; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h1>Редактирование пользователя</h1>
    <form action="update_user.php" method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">

        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio" value="<?= htmlspecialchars($user['fio']) ?>" required>

        <label for="phone">Телефон:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label for="birthdate">Дата рождения:</label>
        <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($user['birthdate']) ?>" required>

        <label for="gender">Пол:</label>
        <select id="gender" name="gender" required>
            <option value="male" <?= $user['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
            <option value="female" <?= $user['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
        </select>

        <label for="languages">Любимые языки программирования:</label>
        <input type="text" id="languages" name="languages" value="<?= htmlspecialchars($user['languages']) ?>" required>

        <label for="biography">Биография:</label>
        <textarea id="biography" name="biography"><?= htmlspecialchars($user['biography']) ?></textarea>

        <label for="contract_accepted">Контракт принят:</label>
        <input type="checkbox" id="contract_accepted" name="contract_accepted" <?= $user['contract_accepted'] ? 'checked' : '' ?>>

        <br><br>
        <input type="submit" value="Сохранить изменения">
    </form>
</div>

</body>
</html>
