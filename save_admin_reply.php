<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['room_id'])) {
        die("Brak room_id");
    }
    $roomId = $_POST['room_id'];
    $uploadDir = "uploads/$roomId/";

    if (!is_dir($uploadDir)) {
        die("Brak folderu dla room_id: $roomId");
    }

    $adminMsg = isset($_POST['admin_message']) ? $_POST['admin_message'] : '';
    $adminMsgSafe = htmlspecialchars($adminMsg, ENT_QUOTES, 'UTF-8');

    // Dopisujemy do pliku admin.txt
    // (możesz wczytywać to w room.php i wyświetlać np. w osobnej sekcji)
    file_put_contents($uploadDir . 'admin.txt', $adminMsgSafe."\n", FILE_APPEND);

    // Po zapisaniu, przekierowujemy z powrotem do pokoju
    header("Location: room.php?room_id=$roomId");
    exit;
}
