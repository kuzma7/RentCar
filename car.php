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
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для получения данных автомобиля
function getCarById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.name,
                c.image_url,
                c.drive_type,
                c.engine,
                c.seats,
                cd.price_per_hour,
                cd.engine_type,
                cd.transmission,
                cd.acceleration,
                cd.power,
                cd.drive_type AS detailed_drive_type,
                cd.description
            FROM 
                cars c
            JOIN 
                car_details cd ON c.id = cd.car_id
            WHERE 
                c.id = :id
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $car = $stmt->fetch();
        
        if ($car) {
            // Объединяем данные из обеих таблиц
            if (!empty($car['detailed_drive_type'])) {
                $car['drive_type'] = $car['detailed_drive_type'];
            }
            
            // Форматируем цену для отображения (цена за сутки)
            $car['price'] = $car['price_per_hour'] * 24;
            
            return $car;
        }
        
        return null;
    } catch (PDOException $e) {
        die("Ошибка при получении данных автомобиля: " . $e->getMessage());
    }
}

// Получаем ID автомобиля из URL
$carId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные об автомобиле
$car = getCarById($pdo, $carId);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $car ? htmlspecialchars($car['name']) : 'Автомобиль не найден' ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="img/logo.png" alt="Логотип">
            </div>
            <nav class="menu">
                <ul>
                    <li class="menu-items">
                        <a href="index.php">Главная</a>
                    </li>
                    <li class="menu-items">
                        <a href="cars.php">Автомобили</a>
                    </li>
                    <li class="menu-items">
                        <a href="order.php">Бронирование авто</a>
                    </li>
                    <li class="menu-items">
                        <a href="reviews.php">Отзывы</a>
                    </li>
                    <li>
                        +7(920)-078-95-28
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="car">
        <div class="container">
            <?php if ($car): ?>
                <div class="car-detail">
                    <div class="car-detail-top">
                        <div class="car-detail-image">
                            <img src="<?= htmlspecialchars($car['image_url']) ?>" alt="<?= htmlspecialchars($car['name']) ?>">
                        </div>
                        <div class="car-detail-info">
                            <div class="car-detail-price">
                                <?= number_format($car['price'], 0, '', ' ') ?> ₽/сутки
                            </div>
                            <div class="car-detail-action">
                                <a href="order.php?car=<?= urlencode($car['name']) ?>" class="button">Забронировать</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="car-detail-specs">
                        <h3 class="car-detail-subtitle">Характеристики</h3>
                        <div class="car-specs-grid">
                            <div class="car-spec-item">
                                <span class="spec-name">Модель:</span>
                                <span class="spec-value"><?= htmlspecialchars($car['name']) ?></span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Двигатель:</span>
                                <span class="spec-value"><?= htmlspecialchars($car['engine_type']) ?></span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Привод:</span>
                                <span class="spec-value"><?= htmlspecialchars($car['drive_type']) ?></span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Количество мест:</span>
                                <span class="spec-value"><?= (int)$car['seats'] ?></span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Трансмиссия:</span>
                                <span class="spec-value"><?= htmlspecialchars($car['transmission']) ?></span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Мощность:</span>
                                <span class="spec-value"><?= (int)$car['power'] ?> л.с.</span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Разгон 0-100 км/ч:</span>
                                <span class="spec-value"><?= htmlspecialchars($car['acceleration']) ?> сек</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="car-detail-description">
                        <h3 class="car-detail-subtitle">Описание</h3>
                        <p><?= nl2br(htmlspecialchars($car['description'])) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="car-not-found">
                    <h3>Автомобиль не найден</h3>
                    <p>К сожалению, запрашиваемый автомобиль не найден в нашем автопарке.</p>
                    <a href="cars.php" class="button">Вернуться к списку автомобилей</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="logo">
                <img src="img/logo.png" alt="Логитип">
            </div>
            <div class="rights">Все права защищены</div>
        </div>
    </footer>
</body>
</html>