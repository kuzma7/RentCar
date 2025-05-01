<?php
// Подключение к базе данных
$host = 'localhost';
$port = '3325';              // Указан нестандартный порт
$db = 'car_rental';          // имя вашей БД
$user = 'root';              // логин
$pass = 'root';              // пароль
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
    die("Пожалуйста, заполните все поля.");
}

// Сохраняем в БД
$stmt = $pdo->prepare("INSERT INTO orders (name, phone, car_name) VALUES (?, ?, ?)");
$stmt->execute([$name, $phone, $car]);

// Сообщение об успехе
echo "Заявка успешно отправлена!";
?>
