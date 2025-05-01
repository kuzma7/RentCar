<?php
$carValue = isset($_GET['car']) ? htmlspecialchars($_GET['car']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Бронирование</title>
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
                <li>+7(920)-078-95-28</li>
            </ul>
        </nav>
    </div>
</header>

<section class="price" id="price">
    <div class="container">
        <h2 class="sub-title">Узнать цену и забронировать</h2>
        <div class="price-text">
            Заполните данные, и мы перезвоним вам для уточнения всех деталей бронирования
        </div>
        <form id="orderForm" class="price-form">
            <input type="text" class="price-input" name="name" placeholder="Ваше имя" required>
            <input type="text" class="price-input" name="phone" placeholder="Ваш телефон" required>
            <input type="text" class="price-input" name="car" placeholder="Автомобиль, который вас интересует"
                   value="<?= $carValue ?>" required>
            <button class="button" type="submit">Узнать цену</button>
        </form>

        <div id="popup" class="popup">Заявка успешно отправлена!</div>

        <div class="video">
            <video loop muted autoplay class="video_item" src="img/video.mp4"></video>
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
    document.getElementById("orderForm").addEventListener("submit", function(event) {
        event.preventDefault();

        var name = document.querySelector('[name="name"]').value;
        var phone = document.querySelector('[name="phone"]').value;
        var car = document.querySelector('[name="car"]').value;

        if (!name || !phone || !car) {
            alert("Пожалуйста, заполните все поля.");
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "submit_order.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById("popup").style.display = "block";
                document.getElementById("orderForm").reset();
                setTimeout(function() {
                    document.getElementById("popup").style.display = "none";
                }, 3000);
            } else {
                alert("Произошла ошибка при отправке данных.");
            }
        };
        xhr.send("name=" + encodeURIComponent(name) + "&phone=" + encodeURIComponent(phone) + "&car=" + encodeURIComponent(car));
    });
</script>
</body>
</html>
