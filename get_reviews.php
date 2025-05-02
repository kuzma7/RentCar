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
    
    // Получаем последние 10 отзывов
    $stmt = $pdo->query("SELECT * FROM car_reviews ORDER BY created_at DESC LIMIT 10");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviews)) {
        echo '<p>Пока нет отзывов. Будьте первым!</p>';
    } else {
        foreach ($reviews as $review) {
            echo '<div class="review-item">';
            echo '<div class="review-header">';
            echo '<div>';
            echo '<span class="review-author">' . htmlspecialchars($review['name']) . '</span>';
            echo ' о ';
            echo '<span class="review-car">' . htmlspecialchars($review['car_model']) . '</span>';
            echo '</div>';
            echo '<div class="review-rating">' . str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) . '</div>';
            echo '</div>';
            echo '<div class="review-date">' . date('d.m.Y H:i', strtotime($review['created_at'])) . '</div>';
            echo '<div class="review-content">' . nl2br(htmlspecialchars($review['review'])) . '</div>';
            echo '</div>';
        }
    }
} catch (\PDOException $e) {
    echo '<p>Ошибка при загрузке отзывов: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>