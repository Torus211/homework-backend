<?php
session_start();

// Соединение с базой данных
$host = 'localhost';
$dbname = 'u68643'; // Название базы данных
$username = 'u68643'; // Логин пользователя MySQL
$password = '7475566'; // Пароль пользователя MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}


// Проверяем, если пользователь уже авторизован
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
}

// Получаем сообщения об ошибках и предыдущие значения
$errors = $_SESSION['errors'] ?? [];
$values = $_SESSION['values'] ?? [];

// Очищаем сессию
$_SESSION['errors'] = [];
$_SESSION['values'] = [];

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма регистрации</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Регистрация</h1>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="form.php" method="POST">
        <label for="fio">ФИО:</label>
        <input type="text" name="fio" id="fio" value="<?= htmlspecialchars($values['fio'] ?? '') ?>" required maxlength="150">

        <label for="phone">Телефон:</label>
        <input type="tel" name="phone" id="phone" pattern="^\+?[0-9]{1,15}$" value="<?= htmlspecialchars($values['phone'] ?? '') ?>">

        <label for="email">E-mail:</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>" required>

        <label for="birthdate">Дата рождения:</label>
        <input type="date" name="birthdate" id="birthdate" value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>" required>

        <label for="gender">Пол:</label>
        <input type="radio" name="gender" value="male" id="male" <?= isset($values['gender']) && $values['gender'] === 'male' ? 'checked' : '' ?> required> Мужской
        <input type="radio" name="gender" value="female" id="female" <?= isset($values['gender']) && $values['gender'] === 'female' ? 'checked' : '' ?>> Женский

        <label for="languages">Любимый язык программирования:</label>
        <select name="languages[]" id="languages" multiple required>
            <option value="1" <?= in_array('1', $values['languages'] ?? []) ? 'selected' : '' ?>>Pascal</option>
            <option value="2" <?= in_array('2', $values['languages'] ?? []) ? 'selected' : '' ?>>C</option>
            <option value="3" <?= in_array('3', $values['languages'] ?? []) ? 'selected' : '' ?>>C++</option>
            <option value="4" <?= in_array('4', $values['languages'] ?? []) ? 'selected' : '' ?>>JavaScript</option>
            <option value="5" <?= in_array('5', $values['languages'] ?? []) ? 'selected' : '' ?>>PHP</option>
            <option value="6" <?= in_array('6', $values['languages'] ?? []) ? 'selected' : '' ?>>Python</option>
            <option value="7" <?= in_array('7', $values['languages'] ?? []) ? 'selected' : '' ?>>Java</option>
            <option value="8" <?= in_array('8', $values['languages'] ?? []) ? 'selected' : '' ?>>Haskell</option>
            <option value="9" <?= in_array('9', $values['languages'] ?? []) ? 'selected' : '' ?>>Clojure</option>
            <option value="10" <?= in_array('10', $values['languages'] ?? []) ? 'selected' : '' ?>>Prolog</option>
            <option value="11" <?= in_array('11', $values['languages'] ?? []) ? 'selected' : '' ?>>Scala</option>
            <option value="12" <?= in_array('12', $values['languages'] ?? []) ? 'selected' : '' ?>>Go</option>
        </select>

        <label for="biography">Биография:</label>
        <textarea name="biography" id="biography" rows="5"><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>

        <label for="contract_accepted">С контрактом ознакомлен:</label>
        <input type="checkbox" name="contract_accepted" id="contract_accepted" <?= isset($values['contract_accepted']) ? 'checked' : '' ?> required>

        <input type="submit" value="Сохранить">
    </form>
</div>

</body>
</html>
