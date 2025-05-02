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
    <style>
        /* Стили для страницы автомобиля */
        .car-detail {
            padding: 40px 0;
        }

        .car-detail-top {
            display: flex;
            gap: 40px;
            margin-bottom: 50px;
            align-items: flex-start;
        }

        .car-detail-image {
            flex: 0 0 55%;
            max-width: 350px;
            max-height: 400px;
            object-fit: cover;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .car-detail-image:hover {
            transform: translateY(-5px);
        }

        .car-detail-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .car-detail-info {
            flex: 1;
            padding: 20px;
            position: sticky;
            top: 20px;
        }

        .car-detail-price {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f1f1;
        }

        .car-detail-price::before {
            content: "Цена: ";
            font-size: 20px;
            color: #7f8c8d;
            font-weight: normal;
        }

        .car-detail-action .button {
            width: 100%;
            max-width: 280px;
            padding: 18px;
            font-size: 18px;
            border-radius: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
            text-decoration: none;
        }

        .car-detail-action .button:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .car-detail-subtitle {
            font-size: 32px;
            margin: 50px 0 30px;
            color: #2c3e50;
            position: relative;
            padding-bottom: 15px;
        }

        .car-detail-subtitle::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 4px;
            background: #e74c3c;
            border-radius: 2px;
        }

        .car-specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .car-spec-item {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #e74c3c;
        }

        .car-spec-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .spec-name {
            font-weight: bold;
            color: #7f8c8d;
            font-size: 16px;
        }

        .spec-value {
            color: #2c3e50;
            font-weight: 600;
            font-size: 17px;
        }

        .car-detail-description {
            margin-top: 50px;
            line-height: 1.8;
            font-size: 17px;
            color: #34495e;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .car-detail-description p {
            margin-bottom: 20px;
        }

        .car-not-found {
            text-align: center;
            padding: 100px 0;
        }

        .car-not-found h3 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .car-not-found p {
            margin-bottom: 30px;
            font-size: 18px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .car-detail-top {
                flex-direction: column;
            }
            
            .car-detail-image {
                width: 100%;
                max-width: 100%;
            }
            
            .car-specs-grid {
                grid-template-columns: 1fr;
            }
            
            .car-detail-info {
                position: static;
                width: 100%;
            }
            
            .car-detail-action .button {
                max-width: 100%;
            }
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
                        <h3 class="car-detail-subtitle">Технические характеристики</h3>
                        <div class="car-specs-grid">
                            <div class="car-spec-item">
                                <span class="spec-name">Модель:</span>
                                <span class="spec-value"><?= htmlspecialchars($car['name']) ?></span>
                            </div>
                            <div class="car-spec-item">
                                <span class="spec-name">Тип двигателя:</span>
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
                        <h3 class="car-detail-subtitle">Описание автомобиля</h3>
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