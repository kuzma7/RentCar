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
