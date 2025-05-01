<?php
session_start();

// Настройка логина и пароля (можно вынести в .env или config)
$valid_username = 'admin';
$valid_password = '1234';

// Проверка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Если пользователь ещё не авторизован
if (!isset($_SESSION['authenticated'])) {
    // Обработка формы входа
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        if ($_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
            $_SESSION['authenticated'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }

    // Форма авторизации
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Вход в админку</title>
        <style>
    body {
        font-family: Arial;
        background: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    form {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        width: 300px;
        box-sizing: border-box;
    }

    input {
        display: block;
        margin-bottom: 10px;
        padding: 8px;
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button {
        padding: 8px 12px;
        width: 100%;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }

    .error {
        color: red;
        margin-bottom: 10px;
    }

    h2 {
        margin-top: 0;
    }
</style>

    </head>
    <body>
    <form method="POST">
        <h2>Вход в админку</h2>
        <?php if (!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>
        <input type="text" name="username" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
    </body>
    </html>
    <?php
    exit;
}
?>



<?php
// Подключение к базе данных
$host = 'localhost';
$port = '3325';
$db = 'car_rental';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Обновление статуса заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: admin.php");
    exit;
}

// Получение всех заявок
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка: заявки</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 20px; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        select, button { padding: 5px; }
        form { display: inline-block; margin: 0; }
        .container { max-width: 1000px; margin: auto; }
    </style>
</head>
<body>
<div class="container">
    <p><a href="?logout=1">Выйти</a></p>
    <h2>Управление заявками</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Телефон</th>
                <th>Автомобиль</th>
                <th>Статус</th>
                <th>Дата создания</th>
                <th>Изменить статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['id']) ?></td>
                    <td><?= htmlspecialchars($order['name']) ?></td>
                    <td><?= htmlspecialchars($order['phone']) ?></td>
                    <td><?= htmlspecialchars($order['car_name']) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status">
                                <?php
                                $statuses = ['Активно', 'В обработке', 'Завершено', 'Отменено'];
                                foreach ($statuses as $status):
                                ?>
                                    <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                        <?= $status ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Обновить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
</body>
</html>
