<?php
header('Content-Type: application/json');

// Проверяем, что запрос является POST-запросом
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

// Получаем данные из формы
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$car = isset($_POST['car']) ? trim($_POST['car']) : '';
$review = isset($_POST['review']) ? trim($_POST['review']) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

// Проверяем обязательные поля
if (empty($name) || empty($phone) || empty($car) || empty($review) || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Пожалуйста, заполните все поля правильно']);
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
    
    // Подготавливаем SQL-запрос
    $stmt = $pdo->prepare("INSERT INTO car_reviews (name, phone, car_model, review, rating) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $car, $review, $rating]);
    
    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>