document.getElementById("price-action").onclick = function (){
    if (document.getElementById("name").value === "") {
        alert('Заполните поле "Имя"');
    } else if (document.getElementById("phone").value === "") {
        alert('Заполните поле "Телефон"');
    }else if (document.getElementById("car").value === "") {
        alert('Заполните поле "Автомобиль"');
    } else {
        alert("Спасибо за заявку! Мы свяжемся с Вами в ближайшее время.");
    }
}
