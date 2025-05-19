<?php
$conn = new mysqli("localhost", "root", "", "demka");
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
