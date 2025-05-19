<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_time = $_POST['date_time'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');

    if (!$date_time || strtotime($date_time) < time()) $errors[] = "Введите корректную будущую дату и время.";
    if ($guests < 1 || $guests > 10) $errors[] = "Количество гостей должно быть от 1 до 10.";
    if (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = "Введите корректный номер телефона.";

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, date_time, guests, phone, status, created_at) VALUES (?, ?, ?, ?, 'Обрабатывается', NOW())");
        $stmt->bind_param("isis", $_SESSION['user_id'], $date_time, $guests, $phone);
        if ($stmt->execute()) $message = "Ваше бронирование отправлено на рассмотрение.";
        else $errors[] = "Ошибка при сохранении бронирования: " . $stmt->error;
        $stmt->close();
    }
}
if ($message) echo '<div class="alert alert-success">'.htmlspecialchars($message).'</div>';
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">Дата и время</label>
        <input type="datetime-local" class="form-control" name="date_time" required value="<?=htmlspecialchars($_POST['date_time'] ?? '')?>">
    </div>
    <div class="mb-3"><label class="form-label">Количество гостей</label>
        <input type="number" class="form-control" name="guests" min="1" max="10" required value="<?=htmlspecialchars($_POST['guests'] ?? '1')?>">
    </div>
    <div class="mb-3"><label class="form-label">Телефон</label>
        <input type="tel" class="form-control" name="phone" required value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
    </div>
    <button type="submit" class="btn btn-primary w-100">Забронировать</button>
</form>
<?php include 'footer.php'; ?>
