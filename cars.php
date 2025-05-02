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

// Получаем список автомобилей
$stmt = $pdo->query("SELECT * FROM cars");
$cars = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Автомобили</title>
    <style>
        .phone-number {
            white-space: nowrap;
            font-weight: bold;
            margin-left: 15px;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="logo">
            <img src="img/logo.png" alt="Логотип">
        </div>
        <nav class="menu">
            <ul>
                <li class="menu-items"><a href="index.html">Главная</a></li>
                <li class="menu-items"><a href="cars.php">Автомобили</a></li>
                <li class="menu-items no-wrap"><a href="order.php">Бронирование авто</a></li>
                <li class="menu-items"><a href="reviews.php">Отзывы</a></li>
                <li class="phone-number">+7(920)-078-95-28</li>
            </ul>
        </nav>
    </div>
</header>

<section class="car" id="cars">
    <div class="container">
        <h2 class="sub-title">Наш автопарк</h2>
        <div class="car-items">
            <?php foreach ($cars as $car): ?>
                <div class="car-item">
                    <div class="car-item-img">
                        <img src="<?= htmlspecialchars($car['image_url']) ?>" alt="<?= htmlspecialchars($car['name']) ?>">
                    </div>
                    <div class="car-item-title"><?= htmlspecialchars($car['name']) ?></div>
                    <div class="car-item-info">
                        <div class="car-item-point">
                            <img src="img/gear.png" alt="Gear">
                            <div>Привод</div>
                            <div><?= htmlspecialchars($car['drive_type']) ?></div>
                        </div>
                        <div class="car-item-point">
                            <img src="img/Group.png" alt="Engine">
                            <div>Двигатель</div>
                            <div><?= htmlspecialchars($car['engine']) ?></div>
                        </div>
                        <div class="car-item-point">
                            <img src="img/belt.png" alt="Seats">
                            <div>Кол-во мест</div>
                            <div><?= (int)$car['seats'] ?></div>
                        </div>
                    </div>
                    <div class="car-item-action">
                        <a href="order.php?car=<?= urlencode($car['name']) ?>" class="button car-button">Забронировать</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="logo">
            <img src="img/logo.png" alt="Логотип">
        </div>
        <div class="rights">Все права защищены</div>
    </div>
</footer>

<script>
    function redirect_order() {
        window.location = 'order.php';
    }
</script>
</body>
</html>