<?php
require_once 'connect.php';
require_once 'helpers.php';
$title = 'Регистрация';
include 'header.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (mb_strlen($name) < 2) $errors[] = 'Неверно введен ФИО';
    if (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = 'Неверно введен телефон';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Неверно введен E-mail';
    if (mb_strlen($login) < 6) $errors[] = 'Логин должен быть не короче 6 символов';
    if (mb_strlen($password) < 6) $errors[] = 'Пароль должен быть не короче 6 символов';

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO users (fio, phone, email, login, password) VALUES (?, ?, ?, ?, ?)");
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("sssss", $name, $phone, $email, $login, $hash);
        if ($stmt->execute()) {
            header('Location: bookings.php');
            exit;
        } else {
            $errors[] = "Ошибка регистрации: " . $conn->error;
        }
        $stmt->close();
    }
}
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">ФИО</label><input type="text" class="form-control" name="fio" required></div>
    <div class="mb-3"><label class="form-label">Телефон</label><input type="text" class="form-control" name="phone" required></div>
    <div class="mb-3"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" required></div>
    <div class="mb-3"><label class="form-label">Логин</label><input type="text" class="form-control" name="login" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" class="form-control" name="password" required></div>
    <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
</form>
<?php include 'footer.php'; ?>
