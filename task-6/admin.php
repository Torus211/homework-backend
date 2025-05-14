<?php
require_once 'config.php';

session_start();

// HTTP-авторизация
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || !validateAdmin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Необходима авторизация.";
    exit;
}

// Обработка действий
$pdo = getDatabaseConnection();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE a, u, al FROM application a LEFT JOIN users u ON a.id = u.application_id LEFT JOIN application_languages al ON a.id = al.application_id WHERE a.id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: admin.php');
    exit;
}

if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
    $errors = [];
    if (empty($_POST['fio']) || !preg_match("/^[а-яА-Яa-zA-Z\s]+$/u", $_POST['fio'])) {
        $errors[] = 'Неверный формат ФИО.';
    }
    if (!empty($_POST['phone']) && !preg_match("/^\+?[0-9]{1,15}$/", $_POST['phone'])) {
        $errors[] = 'Неверный формат телефона.';
    }
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат e-mail.';
    }
    if (empty($_POST['birthdate']) || strtotime($_POST['birthdate']) === false) {
        $errors[] = 'Неверный формат даты рождения.';
    }
    if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female', 'other'])) {
        $errors[] = 'Выберите пол.';
    }
    if (empty($_POST['languages']) || !is_array($_POST['languages'])) {
        $errors[] = 'Выберите хотя бы один язык.';
    } else {
        $valid_languages = range(1, 12);
        foreach ($_POST['languages'] as $language) {
            if (!in_array($language, $valid_languages)) {
                $errors[] = 'Некорректный выбор языка.';
                break;
            }
        }
    }
    if (empty($_POST['biography']) || strlen(trim($_POST['biography'])) < 10) {
        $errors[] = 'Биография должна содержать хотя бы 10 символов.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE application SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, biography = ?, contract_accepted = ? WHERE id = ?");
        $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['biography'], 1, $_POST['edit_id']]);
        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$_POST['edit_id']]);
        $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $language_id) {
            $stmt->execute([$_POST['edit_id'], $language_id]);
        }
        $pdo->commit();
    }
}

$stmt = $pdo->prepare("SELECT a.*, u.login FROM application a LEFT JOIN users u ON a.id = u.application_id");
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT al.language_id, COUNT(*) as count FROM application_languages al GROUP BY al.language_id");
$stmt->execute();
$language_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .edit-form { display: none; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Админ-панель</h1>

    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Список пользователей</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Телефон</th>
            <th>E-mail</th>
            <th>Дата рождения</th>
            <th>Пол</th>
            <th>Биография</th>
            <th>Логин</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($applications as $app): ?>
            <tr>
                <td><?php echo htmlspecialchars($app['id']); ?></td>
                <td><?php echo htmlspecialchars($app['fio']); ?></td>
                <td><?php echo htmlspecialchars($app['phone'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($app['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($app['birthdate'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($app['gender'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($app['biography'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($app['login'] ?? ''); ?></td>
                <td>
                    <a href="?delete=<?php echo $app['id']; ?>" onclick="return confirm('Удалить запись?');">Удалить</a>
                    <button onclick="showEditForm(<?php echo $app['id']; ?>)">Редактировать</button>
                </td>
            </tr>
            <tr id="edit-form-<?php echo $app['id']; ?>" class="edit-form">
                <td colspan="9">
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="edit_id" value="<?php echo $app['id']; ?>">
                        <label>ФИО:</label><input type="text" name="fio" value="<?php echo htmlspecialchars($app['fio']); ?>" required><br>
                        <label>Телефон:</label><input type="tel" name="phone" value="<?php echo htmlspecialchars($app['phone'] ?? ''); ?>" pattern="^\+?[0-9]{1,15}$"><br>
                        <label>E-mail:</label><input type="email" name="email" value="<?php echo htmlspecialchars($app['email'] ?? ''); ?>" required><br>
                        <label>Дата рождения:</label><input type="date" name="birthdate" value="<?php echo htmlspecialchars($app['birthdate'] ?? ''); ?>" required><br>
                        <label>Пол:</label>
                        <input type="radio" name="gender" value="male" <?php echo $app['gender'] == 'male' ? 'checked' : ''; ?>> Мужской
                        <input type="radio" name="gender" value="female" <?php echo $app['gender'] == 'female' ? 'checked' : ''; ?>> Женский
                        <input type="radio" name="gender" value="other" <?php echo $app['gender'] == 'other' ? 'checked' : ''; ?>> Другой<br>
                        <label>Языки:</label>
                        <select name="languages[]" multiple required>
                            <?php
                            $languages = [1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript', 5 => 'PHP', 6 => 'Python', 7 => 'Java', 8 => 'Haskell', 9 => 'Clojure', 10 => 'Prolog', 11 => 'Scala', 12 => 'Go'];
                            $stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                            $stmt->execute([$app['id']]);
                            $user_languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($languages as $id => $name) {
                                $selected = in_array($id, $user_languages) ? 'selected' : '';
                                echo "<option value=\"$id\" $selected>$name</option>";
                            }
                            ?>
                        </select><br>
                        <label>Биография:</label><textarea name="biography" rows="5" required><?php echo htmlspecialchars($app['biography'] ?? ''); ?></textarea><br>
                        <input type="submit" value="Сохранить">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Статистика по языкам</h2>
    <ul>
        <?php
        $languages = [1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript', 5 => 'PHP', 6 => 'Python', 7 => 'Java', 8 => 'Haskell', 9 => 'Clojure', 10 => 'Prolog', 11 => 'Scala', 12 => 'Go'];
        foreach ($languages as $id => $name) {
            $count = $language_stats[$id] ?? 0;
            echo "<li>$name: $count пользователей</li>";
        }
        ?>
    </ul>
</div>

<script>
function showEditForm(id) {
    var form = document.getElementById('edit-form-' + id);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'table-row';
    } else {
        form.style.display = 'none';
    }
}
</script>
</body>
</html>
