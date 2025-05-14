<?php
// HTTP-авторизация
if (
    empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] != 'admin' ||
    md5($_SERVER['PHP_AUTH_PW']) != md5('0000')
) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="My site"');
    print('<h1>401 Требуется авторизация</h1>');
    exit();
}

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

// Получение всех пользователей
function getUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получение статистики по языкам программирования
function getLanguageStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            languages AS language,
            COUNT(*) AS count
        FROM users
        GROUP BY languages
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Удаление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ панель</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .container { width: 80%; margin: 0 auto; background-color: white; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .actions a:hover {
            text-decoration: underline;
        }
        .actions form {
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Админ панель</h1>

    <!-- Список пользователей -->
    <h2>Пользователи</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки программирования</th>
                <th>Биография</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (getUsers($pdo) as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['fio']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['birthdate']) ?></td>
                    <td><?= htmlspecialchars($user['gender']) ?></td>
                    <td><?= htmlspecialchars($user['languages']) ?></td>
                    <td><?= htmlspecialchars($user['biography']) ?></td>
                    <td class="actions">
                        <a href="edit_user.php?id=<?= $user['id'] ?>">Редактировать</a>
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" onclick="return confirm('Вы уверены?')">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Таблица статистики по языкам программирования -->
    <h2>Статистика по языкам программирования</h2>
    <table>
        <thead>
            <tr>
                <th>Язык программирования</th>
                <th>Количество пользователей</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (getLanguageStats($pdo) as $stat): ?>
                <tr>
                    <td><?= htmlspecialchars($stat['language']) ?></td>
                    <td><?= htmlspecialchars($stat['count']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
