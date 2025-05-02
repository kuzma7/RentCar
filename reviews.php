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

// Получаем список автомобилей для выпадающего списка
$stmt = $pdo->query("SELECT id, name FROM cars ORDER BY name");
$cars = $stmt->fetchAll();

$carValue = isset($_GET['car']) ? htmlspecialchars($_GET['car']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Отзывы об автомобилях</title>
    <style>
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            font-size: 18px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .review-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 8px;
            box-sizing: border-box;
        }
        
        .review-input, .review-textarea, .car-select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .car-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            background-color: white;
        }
        
        .car-select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .review-textarea {
            height: 150px;
            resize: vertical;
        }
        
        .rating-container {
            margin: 20px 0;
            direction: rtl;
            text-align: center;
        }
        
        .rating-container label {
            color: #ddd;
            font-size: 30px;
            cursor: pointer;
            transition: color 0.2s;
            margin: 0 3px;
            display: inline-block;
        }
        
        .rating-container input[type="radio"] {
            display: none;
        }
        
        .rating-container label:hover,
        .rating-container label:hover ~ label,
        .rating-container input[type="radio"]:checked ~ label {
            color: #FFD700;
        }
        
        .rating-text {
            margin-top: 10px;
            font-size: 16px;
            color: #666;
            text-align: center;
        }
        
        .reviews-list {
            margin-top: 40px;
            padding: 0 10px;
        }
        
        .review-item {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .review-author {
            font-weight: bold;
            font-size: 18px;
        }
        
        .review-car {
            color: #555;
            font-style: italic;
        }
        
        .review-rating {
            color: #FFD700;
            font-size: 18px;
            margin-left: 10px;
        }
        
        .review-date {
            color: #888;
            font-size: 14px;
        }
        
        .review-content {
            line-height: 1.6;
            padding: 0 5px;
        }
        
        .phone-number {
            white-space: nowrap;
            font-weight: bold;
            margin-left: 15px;
        }
        
        .button {
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #45a049;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        
        .rating-container input[type="radio"]:checked + label {
            animation: pulse 0.5s;
        }
        
        @media (max-width: 768px) {
            .review-form {
                padding: 15px;
                max-width: 95%;
            }
            
            .rating-container label {
                font-size: 25px;
                margin: 0 2px;
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
                <li class="menu-items"><a href="index.html">Главная</a></li>
                <li class="menu-items"><a href="cars.php">Автомобили</a></li>
                <li class="menu-items"><a href="order.php">Бронирование авто</a></li>
                <li class="menu-items"><a href="reviews.php">Отзывы</a></li>
                <li class="phone-number">+7(920)-078-95-28</li>
            </ul>
        </nav>
    </div>
</header>

<section class="price" id="price">
    <div class="container">
        <h2 class="sub-title">Оставить отзыв об автомобиле</h2>
        <div class="price-text">
            Поделитесь своим опытом использования автомобиля. Ваш отзыв поможет другим клиентам.
        </div>
        
        <form id="reviewForm" class="review-form">
            <input type="text" class="review-input" name="name" placeholder="Ваше имя" required>
            <input type="text" class="review-input" name="phone" placeholder="Ваш телефон" required>
            
            <select class="car-select" name="car" required>
                <option value="">-- Выберите автомобиль --</option>
                <?php foreach ($cars as $car): ?>
                    <option value="<?= htmlspecialchars($car['name']) ?>" 
                            <?= ($carValue === $car['name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($car['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div class="rating-container">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5" title="Отлично"><i class="fas fa-star"></i></label>
                
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4" title="Хорошо"><i class="fas fa-star"></i></label>
                
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3" title="Удовлетворительно"><i class="fas fa-star"></i></label>
                
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2" title="Плохо"><i class="fas fa-star"></i></label>
                
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1" title="Ужасно"><i class="fas fa-star"></i></label>
                
                <div class="rating-text">Оцените автомобиль</div>
            </div>
            
            <textarea class="review-textarea" name="review" placeholder="Ваш отзыв" required></textarea>
            <button class="button" type="submit">Отправить отзыв</button>
        </form>

        <div id="popup" class="popup">Отзыв успешно отправлен!</div>

        <div class="reviews-list" id="reviewsList">
            <h3>Последние отзывы</h3>
            <!-- Отзывы будут загружаться здесь -->
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
    // Функция для загрузки отзывов
    function loadReviews() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "get_reviews.php", true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById("reviewsList").innerHTML = '<h3>Последние отзывы</h3>' + xhr.responseText;
            }
        };
        xhr.send();
    }

    // Загружаем отзывы при загрузке страницы
    document.addEventListener("DOMContentLoaded", loadReviews);

    // Обработка отправки формы отзыва
    document.getElementById("reviewForm").addEventListener("submit", function(event) {
        event.preventDefault();

        var formData = new FormData(this);

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "submit_review.php", true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById("popup").style.display = "block";
                document.getElementById("reviewForm").reset();
                loadReviews();
                setTimeout(function() {
                    document.getElementById("popup").style.display = "none";
                }, 3000);
            } else {
                alert("Произошла ошибка при отправке отзыва.");
            }
        };
        xhr.send(new URLSearchParams(formData));
    });
</script>
</body>
</html>