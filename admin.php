<?php
session_start();

// Настройка логина и пароля
$valid_username = 'admin';
$valid_password = '1234';

// Проверка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

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

// Обработка действий с автомобилями
if (isset($_GET['action']) && $_GET['action'] === 'cars') {
    // Удаление автомобиля
    if (isset($_GET['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: admin.php?action=cars");
        exit;
    }

    // Добавление/редактирование автомобиля
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'name' => $_POST['name'],
            'image_url' => $_POST['image_url'],
            'drive_type' => $_POST['drive_type'],
            'engine' => $_POST['engine'],
            'seats' => $_POST['seats']
        ];

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Редактирование
            $data['id'] = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE cars SET name = :name, image_url = :image_url, drive_type = :drive_type, engine = :engine, seats = :seats WHERE id = :id");
            $stmt->execute($data);
        } else {
            // Добавление
            $stmt = $pdo->prepare("INSERT INTO cars (name, image_url, drive_type, engine, seats) VALUES (:name, :image_url, :drive_type, :engine, :seats)");
            $stmt->execute($data);
        }
        
        header("Location: admin.php?action=cars");
        exit;
    }

    // Получение данных об автомобилях и количестве броней
    $cars = $pdo->query("
        SELECT c.*, COUNT(o.id) as bookings_count 
        FROM cars c 
        LEFT JOIN orders o ON o.car_name = c.name 
        GROUP BY c.id
    ")->fetchAll();

    // Получение данных одного автомобиля для редактирования
    $editCar = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editCar = $stmt->fetch();
    }

    // Отображение страницы управления автомобилями
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Админка: автомобили</title>
        <style>
            body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 1200px; margin: auto; }
            h2 { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 20px; }
            th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input, select { width: 100%; padding: 8px; box-sizing: border-box; }
            .btn { padding: 8px 12px; background: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
            .btn-danger { background: #dc3545; }
            .btn-danger:hover { background: #bb2d3b; }
            .nav { margin-bottom: 20px; }
            .nav a { margin-right: 15px; text-decoration: none; color: #007BFF; }
            .nav a:hover { text-decoration: underline; }
            .car-image { max-width: 100px; max-height: 60px; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="nav">
            <a href="admin.php">Заявки</a>
            <a href="admin.php?action=cars">Автомобили</a>
            <a href="?logout=1">Выйти</a>
        </div>
        
        <h2>Управление автомобилями</h2>
        
        <!-- Форма добавления/редактирования -->
        <form method="POST" style="margin-bottom: 30px; background: white; padding: 20px; border-radius: 4px;">
            <h3><?= $editCar ? 'Редактировать автомобиль' : 'Добавить новый автомобиль' ?></h3>
            <?php if ($editCar): ?>
                <input type="hidden" name="id" value="<?= $editCar['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Название:</label>
                <input type="text" name="name" value="<?= $editCar ? htmlspecialchars($editCar['name']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Ссылка на изображение:</label>
                <input type="text" name="image_url" value="<?= $editCar ? htmlspecialchars($editCar['image_url']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Тип привода:</label>
                <select name="drive_type" required>
                    <option value="">Выберите тип</option>
                    <option value="Передний" <?= $editCar && $editCar['drive_type'] === 'Передний' ? 'selected' : '' ?>>Передний</option>
                    <option value="Задний" <?= $editCar && $editCar['drive_type'] === 'Задний' ? 'selected' : '' ?>>Задний</option>
                    <option value="Полный" <?= $editCar && $editCar['drive_type'] === 'Полный' ? 'selected' : '' ?>>Полный</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Двигатель:</label>
                <select name="engine" required>
                    <option value="">Выберите тип</option>
                    <option value="Бензин" <?= $editCar && $editCar['engine'] === 'Бензин' ? 'selected' : '' ?>>Бензин</option>
                    <option value="Дизель" <?= $editCar && $editCar['engine'] === 'Дизель' ? 'selected' : '' ?>>Дизель</option>
                    <option value="Электро" <?= $editCar && $editCar['engine'] === 'Электро' ? 'selected' : '' ?>>Электро</option>
                    <option value="Гибрид" <?= $editCar && $editCar['engine'] === 'Гибрид' ? 'selected' : '' ?>>Гибрид</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Количество мест:</label>
                <input type="number" name="seats" min="2" max="9" value="<?= $editCar ? htmlspecialchars($editCar['seats']) : '5' ?>" required>
            </div>
            
            <button type="submit" class="btn"><?= $editCar ? 'Обновить' : 'Добавить' ?></button>
            <?php if ($editCar): ?>
                <a href="admin.php?action=cars" class="btn">Отмена</a>
            <?php endif; ?>
        </form>
        
        <!-- Таблица автомобилей -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Привод</th>
                    <th>Двигатель</th>
                    <th>Мест</th>
                    <th>Броней</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cars as $car): ?>
                    <tr>
                        <td><?= $car['id'] ?></td>
                        <td>
                            <?php if ($car['image_url']): ?>
                                <img src="<?= htmlspecialchars($car['image_url']) ?>" alt="<?= htmlspecialchars($car['name']) ?>" class="car-image">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($car['name']) ?></td>
                        <td><?= htmlspecialchars($car['drive_type']) ?></td>
                        <td><?= htmlspecialchars($car['engine']) ?></td>
                        <td><?= $car['seats'] ?></td>
                        <td><?= $car['bookings_count'] ?></td>
                        <td>
                            <a href="admin.php?action=cars&edit=<?= $car['id'] ?>" class="btn">Редактировать</a>
                            <a href="admin.php?action=cars&delete=<?= $car['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить этот автомобиль?')">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Обработка заявок (ваш существующий код)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: admin.php");
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка: заявки</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        select, button { padding: 5px; }
        form { display: inline-block; margin: 0; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; text-decoration: none; color: #007BFF; }
        .nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="admin.php">Заявки</a>
        <a href="admin.php?action=cars">Автомобили</a>
        <a href="?logout=1">Выйти</a>
    </div>
    
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