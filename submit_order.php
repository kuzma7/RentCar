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

// Получаем данные из формы
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$car = $_POST['car'] ?? '';

if (empty($name) || empty($phone) || empty($car)) {
    http_response_code(400);
    echo "Пожалуйста, заполните все поля.";
    exit;
}

// Проверка наличия автомобиля в базе
$stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE name = ?");
$stmt->execute([$car]);
$carExists = $stmt->fetchColumn() > 0;

// Сохраняем заявку в таблицу orders
$stmt = $pdo->prepare("INSERT INTO orders (name, phone, car_name) VALUES (?, ?, ?)");
$stmt->execute([$name, $phone, $car]);

// Ответ клиенту
if ($carExists) {
    echo "Заявка успешно отправлена!";
} else {
    echo "Такого автомобиля нет, но мы свяжемся с вами.";
}
?>
