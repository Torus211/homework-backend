<?php
session_start();

$isAuthenticated = isset($_SESSION['user_id']);

if (!$isAuthenticated && isset($_POST['login']) && isset($_POST['password'])) {
    $host = 'localhost';
    $dbname = 'u68649';
    $username = 'u68649';
    $password = '9841747';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE login = ?");
        $stmt->execute([$_POST['login']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($_POST['password'], $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $stmt = $pdo->prepare("SELECT a.id AS application_id, a.fio, a.phone, a.email, a.birthdate, a.gender, a.biography, a.contract_accepted 
                                 FROM application a JOIN users u ON u.application_id = a.id WHERE u.id = ?");
            $stmt->execute([$user['id']]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['application_id'] = $user_data['application_id'];
            $_SESSION['fio'] = $user_data['fio'];
            $_SESSION['phone'] = $user_data['phone'];
            $_SESSION['email'] = $user_data['email'];
            $_SESSION['birthdate'] = $user_data['birthdate'];
            $_SESSION['gender'] = $user_data['gender'];
            $_SESSION['biography'] = $user_data['biography'];
            $_SESSION['contract_accepted'] = $user_data['contract_accepted'];
            $stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
            $stmt->execute([$_SESSION['application_id']]);
            $_SESSION['languages'] = serialize($stmt->fetchAll(PDO::FETCH_COLUMN));
            $isAuthenticated = true;
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверный логин или пароль.';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных: ' . $e->getMessage();
    }
}

if ($isAuthenticated && isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Регистрация</h1>

    <?php if (isset($_COOKIE['success'])): ?>
        <div class="success">
            <?php echo htmlspecialchars($_COOKIE['success']); ?>
            <?php if (isset($_COOKIE['credentials'])): ?>
                <?php $credentials = json_decode($_COOKIE['credentials'], true); ?>
                <p>Ваш логин: <?php echo htmlspecialchars($credentials['login']); ?></p>
                <p>Ваш пароль: <?php echo htmlspecialchars($credentials['password']); ?></p>
                <?php setcookie('credentials', '', time() - 3600, '/'); ?>
            <?php endif; ?>
            <?php setcookie('success', '', time() - 3600, '/'); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="errors">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_COOKIE['errors'])): ?>
        <div class="errors">
            <?php 
            $errors = unserialize($_COOKIE['errors']);
            foreach ($errors as $error) {
                echo "<p>" . htmlspecialchars($error) . "</p>";
            }
            setcookie('errors', '', time() - 3600, '/');
            ?>
        </div>
    <?php endif; ?>

    <?php if (!$isAuthenticated): ?>
        <form action="index.php" method="POST" style="margin-bottom: 20px;">
            <label for="login">Логин:</label>
            <input type="text" name="login" id="login" required>
            <label for="password">Пароль:</label>
            <input type="password" name="password" id="password" required>
            <input type="submit" value="Войти">
        </form>
    <?php endif; ?>

    <form action="form.php" method="POST">
        <?php if ($isAuthenticated): ?>
            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($_SESSION['application_id'] ?? ''); ?>">
        <?php endif; ?>
        <label for="fio">ФИО:</label>
        <input type="text" name="fio" id="fio" value="<?php echo htmlspecialchars($_COOKIE['form']['fio'] ?? $_SESSION['fio'] ?? ''); ?>" required>

        <label for="phone">Телефон:</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($_COOKIE['form']['phone'] ?? $_SESSION['phone'] ?? ''); ?>" pattern="^\+?[0-9]{1,15}$">

        <label for="email">E-mail:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_COOKIE['form']['email'] ?? $_SESSION['email'] ?? ''); ?>" required>

        <label for="birthdate">Дата рождения:</label>
        <input type="date" name="birthdate" id="birthdate" value="<?php echo htmlspecialchars($_COOKIE['form']['birthdate'] ?? $_SESSION['birthdate'] ?? ''); ?>" required>

        <label for="gender">Пол:</label>
        <input type="radio" name="gender" value="male" <?php echo (isset($_COOKIE['form']['gender']) && $_COOKIE['form']['gender'] == 'male') || (isset($_SESSION['gender']) && $_SESSION['gender'] == 'male') ? 'checked' : ''; ?>> Мужской
        <input type="radio" name="gender" value="female" <?php echo (isset($_COOKIE['form']['gender']) && $_COOKIE['form']['gender'] == 'female') || (isset($_SESSION['gender']) && $_SESSION['gender'] == 'female') ? 'checked' : ''; ?>> Женский

        <label for="languages">Любимый язык программирования:</label>
        <select name="languages[]" id="languages" multiple required>
            <?php
            $languages = [1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript', 5 => 'PHP', 6 => 'Python', 7 => 'Java', 8 => 'Haskell', 9 => 'Clojure', 10 => 'Prolog', 11 => 'Scala', 12 => 'Go'];
            $selected_languages = isset($_COOKIE['form']['languages']) ? unserialize($_COOKIE['form']['languages']) : (isset($_SESSION['languages']) ? unserialize($_SESSION['languages']) : []);
            foreach ($languages as $id => $name) {
                $selected = in_array($id, $selected_languages) ? 'selected' : '';
                echo "<option value=\"$id\" $selected>$name</option>";
            }
            ?>
        </select>

        <label for="biography">Биография:</label>
        <textarea name="biography" id="biography" rows="5" required><?php echo htmlspecialchars($_COOKIE['form']['biography'] ?? $_SESSION['biography'] ?? ''); ?></textarea>

        <label for="contract_accepted">С контрактом ознакомлен:</label>
        <input type="checkbox" name="contract_accepted" id="contract_accepted" <?php echo (isset($_COOKIE['form']['contract_accepted']) && $_COOKIE['form']['contract_accepted']) || (isset($_SESSION['contract_accepted']) && $_SESSION['contract_accepted']) ? 'checked' : ''; ?> required>

        <input type="submit" value="Сохранить">
        <?php if ($isAuthenticated): ?>
            <input type="submit" name="logout" value="Выйти">
        <?php endif; ?>
    </form>
</div>
</body>
</html>
