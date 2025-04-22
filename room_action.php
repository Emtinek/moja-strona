<?php
/**
 * room_action.php
 *  - action=get  -> zwraca całą konwersację (chat.txt)
 *  - action=send -> dopisuje nową wiadomość (role=user/admin, treść)
 */
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    die(json_encode(['error' => 'Brak parametru action']));
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$roomId = $_GET['room_id'] ?? $_POST['room_id'] ?? null;
if (!$roomId) {
    die(json_encode(['error' => 'Brak room_id']));
}

$uploadDir = __DIR__ . "/uploads/$roomId";
if (!is_dir($uploadDir)) {
    die(json_encode(['error' => "Pokój $roomId nie istnieje."]));
}

$chatFile = $uploadDir . '/chat.txt';

if ($action === 'get') {
    // Wczytaj cały chat.txt (historia)
    $chatData = [];
    if (file_exists($chatFile)) {
        $lines = file($chatFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Format w pliku: time|role|message
            $parts = explode('|', $line, 3);
            if (count($parts) === 3) {
                list($time, $role, $msg) = $parts;
                $chatData[] = [
                    'time'    => $time,
                    'role'    => $role,
                    'message' => $msg
                ];
            }
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['chat' => $chatData]);
    exit;

} elseif ($action === 'send') {
    // Dodawanie nowej wiadomości do czatu
    $role = $_POST['role'] ?? 'user';
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        die(json_encode(['error' => 'Pusta wiadomość']));
    }

    // Dopisujemy do chat.txt
    $line = time() . '|' . $role . '|' . str_replace(["\r","\n"], '', $message) . "\n";
    file_put_contents($chatFile, $line, FILE_APPEND);

    // ============================
    // Wysyłanie maili z powiadomieniem
    // ============================
    $emailFile = $uploadDir . '/email.txt';
    $userEmail = file_exists($emailFile) ? trim(file_get_contents($emailFile)) : '';

    // Nagłówki maila (From + HTML)
    $headers  = "From: Prioforge <prioforge@gmail.com>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Link do pokoju
    $roomLink = "https://prioforge.com/room.php?room_id=" . urlencode($roomId);

    // Skracamy wiadomość do 1000 znaków
    $maxLen = 500;
    $shortMessage = mb_substr($message, 0, $maxLen);
    if (mb_strlen($message) > $maxLen) {
        $shortMessage .= ' [...]';
    }

    if ($role === 'admin') {
        // Admin pisze -> mail do użytkownika
        if (!empty($userEmail)) {
            $subject = "Odpowiedź w Twoim pokoju (ID: $roomId)";
            $body    = "
                <p>Administrator odpowiedział na Twoją wiadomość.</p>
                <p><b>Treść:</b> $shortMessage</p>
                <p>Odpowiedz w pokoju:
                   <a href='$roomLink'>$roomLink</a></p>
                <p>Pozdrawiamy,<br>Prioforge</p>
            ";
            @mail($userEmail, $subject, $body, $headers);
        }
    } else {
        // User pisze -> mail do admina
        $adminEmail = "inkrinyt@gmail.com";
        $subject    = "Nowa wiadomość w pokoju (ID: $roomId)";
        $body       = "
            <p>Użytkownik napisał nową wiadomość.</p>
            <p><b>Treść:</b> $shortMessage</p>
            <p>Link do pokoju: <a href='$roomLink'>$roomLink</a></p>
        ";
        @mail($adminEmail, $subject, $body, $headers);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;

} else {
    echo json_encode(['error' => 'Nieznana akcja']);
    exit;
}

