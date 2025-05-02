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

    // Форма авторизации (остается без изменений)
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
        $pdo->beginTransaction();
        try {
            // Сначала удаляем детали
            $stmt = $pdo->prepare("DELETE FROM car_details WHERE car_id = ?");
            $stmt->execute([$_GET['delete']]);
            
            // Затем сам автомобиль
            $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            $pdo->commit();
            header("Location: admin.php?action=cars");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Ошибка при удалении автомобиля: " . $e->getMessage());
        }
    }

    // Добавление/редактирование автомобиля
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();
        try {
            $carData = [
                'name' => $_POST['name'],
                'image_url' => $_POST['image_url'],
                'drive_type' => $_POST['drive_type'],
                'engine' => $_POST['engine'],
                'seats' => $_POST['seats']
            ];

            $carDetailsData = [
                'price_per_hour' => $_POST['price_per_hour'],
                'engine_type' => $_POST['engine_type'],
                'transmission' => $_POST['transmission'],
                'acceleration' => $_POST['acceleration'],
                'power' => $_POST['power'],
                'description' => $_POST['description']
            ];

            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Редактирование
                $carData['id'] = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE cars SET name = :name, image_url = :image_url, drive_type = :drive_type, engine = :engine, seats = :seats WHERE id = :id");
                $stmt->execute($carData);
                
                $carDetailsData['car_id'] = $_POST['id'];
                $stmt = $pdo->prepare("
                    UPDATE car_details SET 
                        price_per_hour = :price_per_hour,
                        engine_type = :engine_type,
                        transmission = :transmission,
                        acceleration = :acceleration,
                        power = :power,
                        description = :description
                    WHERE car_id = :car_id
                ");
                $stmt->execute($carDetailsData);
            } else {
                // Добавление
                $stmt = $pdo->prepare("INSERT INTO cars (name, image_url, drive_type, engine, seats) VALUES (:name, :image_url, :drive_type, :engine, :seats)");
                $stmt->execute($carData);
                $carId = $pdo->lastInsertId();
                
                $carDetailsData['car_id'] = $carId;
                $stmt = $pdo->prepare("
                    INSERT INTO car_details 
                        (car_id, price_per_hour, engine_type, transmission, acceleration, power, description) 
                    VALUES 
                        (:car_id, :price_per_hour, :engine_type, :transmission, :acceleration, :power, :description)
                ");
                $stmt->execute($carDetailsData);
            }
            
            $pdo->commit();
            header("Location: admin.php?action=cars");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Ошибка при сохранении автомобиля: " . $e->getMessage());
        }
    }

    // Получение данных об автомобилях и количестве броней
    $cars = $pdo->query("
        SELECT c.*, cd.price_per_hour, COUNT(o.id) as bookings_count 
        FROM cars c 
        LEFT JOIN car_details cd ON c.id = cd.car_id
        LEFT JOIN orders o ON o.car_name = c.name 
        GROUP BY c.id
    ")->fetchAll();

    // Получение данных одного автомобиля для редактирования
    $editCar = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare("
            SELECT c.*, cd.* 
            FROM cars c 
            JOIN car_details cd ON c.id = cd.car_id 
            WHERE c.id = ?
        ");
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
            input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
            textarea { min-height: 100px; resize: vertical; }
            .btn { padding: 8px 12px; background: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
            .btn-danger { background: #dc3545; }
            .btn-danger:hover { background: #bb2d3b; }
            .nav { margin-bottom: 20px; }
            .nav a { margin-right: 15px; text-decoration: none; color: #007BFF; }
            .nav a:hover { text-decoration: underline; }
            .car-image { max-width: 100px; max-height: 60px; }
            .price { font-weight: bold; color: #28a745; }
            .form-section { background: white; padding: 20px; border-radius: 4px; margin-bottom: 30px; }
            .form-section h3 { margin-top: 0; }
            .form-row { display: flex; gap: 20px; }
            .form-row .form-group { flex: 1; }
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
        <div class="form-section">
            <form method="POST">
                <h3><?= $editCar ? 'Редактировать автомобиль' : 'Добавить новый автомобиль' ?></h3>
                <?php if ($editCar): ?>
                    <input type="hidden" name="id" value="<?= $editCar['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Название:</label>
                        <input type="text" name="name" value="<?= $editCar ? htmlspecialchars($editCar['name']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ссылка на изображение:</label>
                        <input type="text" name="image_url" value="<?= $editCar ? htmlspecialchars($editCar['image_url']) : '' ?>">
                    </div>
                </div>
                
                <div class="form-row">
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
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Цена за час (₽):</label>
                        <input type="number" step="0.01" min="0" name="price_per_hour" value="<?= $editCar ? htmlspecialchars($editCar['price_per_hour']) : '0' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Тип двигателя (детально):</label>
                        <input type="text" name="engine_type" value="<?= $editCar ? htmlspecialchars($editCar['engine_type']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Трансмиссия:</label>
                        <select name="transmission" required>
                            <option value="">Выберите тип</option>
                            <option value="Автомат" <?= $editCar && $editCar['transmission'] === 'Автомат' ? 'selected' : '' ?>>Автомат</option>
                            <option value="Механика" <?= $editCar && $editCar['transmission'] === 'Механика' ? 'selected' : '' ?>>Механика</option>
                            <option value="Робот" <?= $editCar && $editCar['transmission'] === 'Робот' ? 'selected' : '' ?>>Робот</option>
                            <option value="Вариатор" <?= $editCar && $editCar['transmission'] === 'Вариатор' ? 'selected' : '' ?>>Вариатор</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Разгон 0-100 км/ч (сек):</label>
                        <input type="number" step="0.1" min="0" name="acceleration" value="<?= $editCar ? htmlspecialchars($editCar['acceleration']) : '0' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Мощность (л.с.):</label>
                        <input type="number" min="0" name="power" value="<?= $editCar ? htmlspecialchars($editCar['power']) : '0' ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" required><?= $editCar ? htmlspecialchars($editCar['description']) : '' ?></textarea>
                </div>
                
                <button type="submit" class="btn"><?= $editCar ? 'Обновить' : 'Добавить' ?></button>
                <?php if ($editCar): ?>
                    <a href="admin.php?action=cars" class="btn">Отмена</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Таблица автомобилей -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Цена/час</th>
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
                        <td class="price"><?= number_format($car['price_per_hour'], 2) ?> ₽</td>
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

// Обработка заявок (остается без изменений)
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