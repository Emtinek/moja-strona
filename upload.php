<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Generowanie unikalnej nazwy folderu (np. timestamp lub uniqid)
    $messageId = time(); 
    $uploadDir = "uploads/$messageId/";

    // Tworzymy folder (rekurencyjnie, jeśli by go nie było)
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // ---------------------
    // 1. Zapis wiadomości
    // ---------------------
    $message = '';
    if (isset($_POST['message'])) {
        // Zabezpieczamy przed HTML
        $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
        file_put_contents($uploadDir . 'message.txt', $message . "\n");
        $response['message'] = "Wiadomość zapisana w folderze: $messageId.";
    }

    // ---------------------
    // 2. Zapis emaila
    // ---------------------
    $email = '';
    if (isset($_POST['email'])) {
        $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
        file_put_contents($uploadDir . 'email.txt', $email . "\n");
        $response['email'] = "Email zapisany w folderze: $messageId.";
    }

    // ---------------------
    // 3. Obsługa przesłanych plików
    // ---------------------
    if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
        $files = $_FILES['files'];
        $uploadedFiles = [];
        $errors = [];

        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName     = basename($files['name'][$i]);
            $targetFile   = $uploadDir . $fileName;
            $fileTmpName  = $files['tmp_name'][$i];
            $fileError    = $files['error'][$i];

            if ($fileError === UPLOAD_ERR_OK) {
                if (move_uploaded_file($fileTmpName, $targetFile)) {
                    $uploadedFiles[] = $fileName;
                } else {
                    $errors[] = "Błąd podczas zapisu pliku: $fileName.";
                }
            } else {
                $errors[] = "Błąd przesyłania pliku: $fileName (kod: $fileError).";
            }
        }

        if (!empty($uploadedFiles)) {
            $response['uploaded_files'] = $uploadedFiles;
        }
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
    } else {
        // Jeśli nie ma żadnych plików
        $response['message'] = $response['message'] ?? "Brak plików do przesłania.";
    }

    // ---------------------
    // 4. Dodajemy 'room_id' do odpowiedzi
    // ---------------------
    $response['room_id'] = $messageId;

    // =========================================================
    // Wysyłka maili (skrócona treść do 1000 znaków)
    // =========================================================
    // Link do pokoju
    $roomLink = "https://prioforge.com/room.php?room_id=$messageId";

    // Nagłówki maila (From + HTML)
    $headers  = "From: Prioforge <prioforge@gmail.com>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Skracamy treść wiadomości do 1000 znaków
    $maxLen = 500;
    $shortMessage = mb_substr($message, 0, $maxLen);
    if (mb_strlen($message) > $maxLen) {
        $shortMessage .= ' [...]';
    }

    // (A) Mail do użytkownika (jeśli podał email)
    if (!empty($email)) {
        $subjectUser = "Twój pokój został utworzony (ID: $messageId)";
        $bodyUser = "
            <p>Witaj!</p>
            <p>Twoja wiadomość:</p>
            <blockquote>$shortMessage</blockquote>
            <p>Oto link do Twojego pokoju:
               <a href='$roomLink'>$roomLink</a></p>
            <p>Pozdrawiamy,<br>Prioforge</p>
        ";
        @mail($email, $subjectUser, $bodyUser, $headers);
    }

    // (B) Mail do Ciebie (admina)
    $adminEmail   = "inkrinyt@gmail.com";
    $subjectAdmin = "Utworzono nowy pokój (ID: $messageId)";
    $bodyAdmin = "
        <p>Nowe zgłoszenie od użytkownika: <b>$email</b></p>
        <p>Treść wiadomości: <i>$shortMessage</i></p>
        <p>Link do pokoju: <a href='$roomLink'>$roomLink</a></p>
    ";
    @mail($adminEmail, $subjectAdmin, $bodyAdmin, $headers);

    // ---------------------------------------------------------
    // Odpowiedź JSON
    // ---------------------------------------------------------
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Jeśli żądanie nie jest POST
    header('Content-Type: application/json');
    echo json_encode(["error" => "Błędne żądanie."]);
    exit;
}

