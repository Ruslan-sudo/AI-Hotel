<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isBlocked($_SESSION['user_id'], $pdo)) {
    session_destroy();
    redirect('login.php');
}

$room_id = $_GET['room_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND is_available = 1");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    redirect('dashboard.php');
}

$booking_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    $total_price = $days * $room['price_per_night'];
    
    if ($_POST['action'] === 'confirm') {
        // Сохраняем бронирование
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
        $stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $total_price]);
        $booking_id = $pdo->lastInsertId();
        
        // Получаем данные пользователя
        $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $user->execute([$_SESSION['user_id']]);
        $user_data = $user->fetch();
        
        // Отправляем email
        $to = $user_data['email'];
        $subject = "Подтверждение бронирования отеля";
        $message = "
            <html>
            <head><title>Бронирование подтверждено</title></head>
            <body>
                <h2>Поздравляем с успешным бронированием!</h2>
                <p><strong>Номер:</strong> {$room['room_number']} ({$room['type']})</p>
                <p><strong>Дата заезда:</strong> $check_in</p>
                <p><strong>Дата выезда:</strong> $check_out</p>
                <p><strong>Количество ночей:</strong> $days</p>
                <p><strong>Итоговая сумма:</strong> $total_price руб</p>
                <p>Спасибо, что выбрали наш отель!</p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: hotel@example.com" . "\r\n";
        
        mail($to, $subject, $message, $headers);
        
        $_SESSION['booking_success'] = true;
        $_SESSION['booking_data'] = [
            'room_number' => $room['room_number'],
            'type' => $room['type'],
            'check_in' => $check_in,
            'check_out' => $check_out,
            'days' => $days,
            'total_price' => $total_price,
            'email' => $user_data['email']
        ];
        
        redirect('dashboard.php');
    } else {
        // Показываем предпросмотр
        $booking_data = [
            'room_number' => $room['room_number'],
            'type' => $room['type'],
            'check_in' => $check_in,
            'check_out' => $check_out,
            'days' => $days,
            'total_price' => $total_price
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирование номера</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Бронирование номера <?php echo $room['room_number']; ?></h1>
        
        <?php if($booking_data): ?>
            <div class="booking-summary">
                <h2>Содержимое заказа:</h2>
                <p><strong>Номер:</strong> <?php echo $booking_data['room_number']; ?></p>
                <p><strong>Тип:</strong> <?php echo $booking_data['type']; ?></p>
                <p><strong>Дата заезда:</strong> <?php echo $booking_data['check_in']; ?></p>
                <p><strong>Дата выезда:</strong> <?php echo $booking_data['check_out']; ?></p>
                <p><strong>Количество ночей:</strong> <?php echo $booking_data['days']; ?></p>
                <p><strong>Итоговая сумма:</strong> <?php echo $booking_data['total_price']; ?> руб</p>
            </div>
            <form method="POST">
                <input type="hidden" name="check_in" value="<?php echo $booking_data['check_in']; ?>">
                <input type="hidden" name="check_out" value="<?php echo $booking_data['check_out']; ?>">
                <button type="submit" name="action" value="confirm" class="btn-success">Подтвердить бронирование</button>
                <a href="dashboard.php" class="btn">Отмена</a>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Дата заезда:</label>
                    <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Дата выезда:</label>
                    <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <button type="submit" name="action" value="preview">Продолжить</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>