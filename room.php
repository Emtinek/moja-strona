<?php
// room.php
//  1) Na górze logo + "Room <email>"
//  2) Sekcja czatu (bąbelki): 
//     - pierwsza wiadomość usera i auto-reply admina dopisywane do chat.txt, jeśli puste
//  3) Sekcja z załączonymi plikami poniżej czatu
//  4) Responsywne style + ikonka logowania jako admin (hasło Ematlov)

if (!isset($_GET['room_id'])) {
    die("Brak parametru room_id.");
}

$roomId = $_GET['room_id'];
$uploadDir = __DIR__ . "/uploads/$roomId/";
if (!is_dir($uploadDir)) {
    die("Pokój o ID $roomId nie istnieje.");
}

// Wczytujemy plik z emailem, plik z pierwotną wiadomością
$emailFile   = $uploadDir . 'email.txt';
$messageFile = $uploadDir . 'message.txt';

$email   = file_exists($emailFile)   ? file_get_contents($emailFile)   : 'Brak emaila';
$message = file_exists($messageFile) ? file_get_contents($messageFile) : 'Brak wiadomości';

// Automatyczna odpowiedź (wyświetlana jako pierwsza wiadomość admina)
$autoReply = "Dziękujemy za zgłoszenie! Odpowiemy jeszcze dziś, proszę czekać na nasz email (w razie braku odpowiedzi tego samego dnia, proszę sprawdzić skrzynke SPAM. Jeśli potrzebujesz telefon do nas:+48 572 356 589).";

// Plik chat.txt, w którym przechowujemy kolejne linie: timestamp|role|message
$chatFile = $uploadDir . 'chat.txt';

// Jeśli chat.txt jest pusty, dopisz do niego pierwsze dwie linie (user + admin)
if (!file_exists($chatFile)) {
    // Plik nie istnieje – tworzymy go
    touch($chatFile);
}
if (filesize($chatFile) === 0) {
    // Dodajemy pierwszą wiadomość usera (z pliku message.txt), 
    //  a następnie automatyczną odpowiedź admina
    $msgUser     = trim(str_replace(["\r","\n"], '', $message));
    $msgAuto     = trim(str_replace(["\r","\n"], '', $autoReply));
    $timeNow     = time();

    // Dopisujemy do chat.txt
    if ($msgUser !== '') {
        // Linia: timestamp|user|wiadomość
        file_put_contents($chatFile, $timeNow . '|user|' . $msgUser . "\n", FILE_APPEND);
    }
    // Bez względu na to, czy userMessage puste czy nie, dodajemy auto-odpowiedź
    file_put_contents($chatFile, $timeNow . '|admin|' . $msgAuto . "\n", FILE_APPEND);
}

// Wczytujemy listę załączonych plików (pomijając pliki .txt)
$filesList = [];
$allFiles = scandir($uploadDir);
foreach ($allFiles as $f) {
    if ($f === '.' || $f === '..') continue;
    if (in_array($f, ['email.txt', 'message.txt', 'admin.txt', 'chat.txt'])) {
        continue;
    }
    $filesList[] = $f; 
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Room <?php echo htmlspecialchars($email); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Wygląd strony */
        body {
          font-family: 'Montserrat', sans-serif;
          background-color: #1e1c1c;
          color: #333;
          margin: 0; 
          padding: 0;
        }

        /* Logo na górze (podobnie jak w index.html) */
        .logo-container {
          text-align: center;
          padding: 20px;
        }
        .logo {
          max-width: 200px;
          width: 100%;
          height: auto;
        }

        .container {
          max-width: 800px;
          margin: 0 auto 20px auto;
          background-color: #fff;
          padding: 20px;
          border-radius: 20px;
          box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* Nagłówek typu: "Room user@example.com" */
        h1 {
          font-size: 22px;
          margin-bottom: 20px;
          text-align: center;
          color: #162f3b;
        }

        /* Sekcja czatu (na żywo) */
        .chat-section {
          background: #fafafa;
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 20px;
        }
        .chat-header {
          font-size: 18px;
          margin-bottom: 10px;
          text-align: center;
          
          color: #161212;
          padding: 10px;
          border-radius: 30px;
        }
        .chat-messages {
          max-height: 300px;
          overflow-y: auto;
          background: #fafafa;
          
          border-radius: 4px;
          padding: 10px;
          
          min-height: 100px;
        }
        /* Bąbelki */
        .bubble {
          display: inline-block;
          padding: 8px 12px;
          margin: 5px 0;
          border-radius: 16px;
          max-width: 80%;
          word-wrap: break-word;
          clear: both;
        }
        .bubble-user {
          background: #d7d7d7;
          float: left;
        }
        .bubble-admin {
          background: #97dcde;
          float: right;
        }

        .chat-input-area {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }
        .chat-input {
    font-family: 'Montserrat', sans-serif;
    width: 100%;
    box-sizing: border-box;
    padding: 10px;
    font-size: 14px;
    border-radius: 20px;
    border: 1px solid #ccc;
    outline: none;
    resize: none;        /* Wyłączamy ręczny 'chwyt' */
    min-height: 50px;    /* Minimalna wysokość */
    overflow-y: hidden;  /* Usuwamy pasek przewijania, bo zwiększymy height JS-em */
}

        .send-btn {
          font-family: 'Montserrat', sans-serif;     
          background-color: #162f3b;
          color: #fff;
          border: none;
          padding: 8px 16px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 14px;
          align-self: flex-end;
          border-radius: 30px;
        }
        .send-btn:hover {
          opacity: 0.9;
        }

        /* Przycisk logowania admina - dyskretny: ikona */
        .admin-login {
          text-align: right;
          margin-bottom: 8px;
        }
        .admin-login button {
          background: none;
          border: none;
          cursor: pointer;
          padding: 0;
        }
        .admin-login button img {
          width: 24px; 
          height: 24px;
          
          transition: opacity 0.2s;
        }
        .admin-login button img:hover {
          opacity: 0.8;
        }

        /* Pole hasła admina - pokazuje się po kliknięciu */
        #adminPasswordField {
          display: none;
          margin-bottom: 10px;
          text-align: right;
        }
        #adminPasswordField input {
          font-family: 'Montserrat', sans-serif;        
          padding: 5px;
          border: 1px solid #ccc;
          border-radius: 4px;
          font-size: 14px;
          width: 150px;
        }
        #adminPasswordField button {
          font-family: 'Montserrat', sans-serif;    
          background-color: #162f3b;
          color: #fff;
          border: none;
          margin-left: 5px;
          padding: 6px 12px;
          border-radius: 25px;
          cursor: pointer;
          font-size: 14px;
        }
        #adminPasswordField button:hover {
          opacity: 0.9;
        }

        /* Sekcja plików załączonych */
        .files-section {
          margin-top: 20px;
          background: #fff;
          padding: 10px;
        }
        .label {
          font-weight: bold;
          color: #162f3b;
        }
        .file-list {
          display: flex;
          gap: 10px;
          flex-wrap: wrap;
          margin-top: 10px;
        }
        .file-item {
          border: 1px solid #ccc;
          padding: 5px;
          border-radius: 4px;
          text-align: center;
          max-width: 100px;
          background: #fff;
          overflow: hidden;
        }
        .file-item img, 
        .file-item video {
          max-width: 100%;
          height: auto;
        }

        /* Responsywność */
        @media (max-width: 768px) {
          .container {
            margin: 15px;
            padding: 15px;
          }
          .chat-messages {
            max-height: 250px;
          }
          .file-item {
            max-width: 80px;
          }
        }
        @media (max-width: 480px) {
          .bubble {
            max-width: 100%;
          }
          .admin-login button img {
            width: 20px;
            height: 20px;
          }
        }
        .chat-messages::-webkit-scrollbar {
  width: 10px; /* Szerokość paska */
}

.chat-messages::-webkit-scrollbar-track {
  background: #f0f0f0; /* Kolor tła "toru" */
  border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb {
  background-color: #162f3b; /* Kolor "kciuka" */
  border-radius: 10px;
  border: 2px solid #f0f0f0; /* Opcjonalna ramka */
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background-color: #1c3e5c; /* Jaśniejszy odcień przy najechaniu */
}

/* Dla Firefoksa */
.chat-messages {
  scrollbar-width: thin;
  scrollbar-color: #162f3b #f0f0f0; /* Pierwszy kolor – kciuk, drugi – tło */
}
    </style>
</head>
<body>

<!-- Logo u góry -->
<div class="logo-container">
  <a href="https://www.prioforge.com">
    <img src="NOWELOGO.png" alt="PRIOFORGE Logo" class="logo">
  </a>
</div>

<div class="container">

    <!-- Nagłówek: "Room <email>" -->
    <h1>Room <?php echo htmlspecialchars($email); ?></h1>

    <!-- Sekcja czatu (zawiera wcześniejszą wiadomość usera + auto reply admina) -->
    <div class="chat-section">
        <div class="chat-header">Conversation</div>

        <!-- Dyskretny przycisk (ikona) do logowania jako admin -->
        <div class="admin-login">
            <button id="adminLoginToggle">
                <!-- Podmień src na ścieżkę do swojej ikonki admina -->
                <img src="admin-iconn.png" alt="Admin">
            </button>
        </div>
        <div id="adminPasswordField">
            <input type="password" id="adminPassword" placeholder="Hasło admina">
            <button id="adminPasswordBtn">OK</button>
        </div>

        <!-- Lista wiadomości (bąbelki) -->
        <div class="chat-messages" id="chatMessages"></div>

        <!-- Pole pisania wiadomości -->
        <div class="chat-input-area">
            <textarea id="chatInput" class="chat-input" placeholder="Napisz wiadomość..."></textarea>
            <button class="send-btn" id="sendBtn">Wyślij</button>
        </div>
    </div>

    <!-- Sekcja plików załączonych poniżej czatu -->
    <div class="files-section">
        <span class="label">Załączone pliki:</span>
        <div class="file-list">
            <?php
            if (empty($filesList)) {
                echo "<p style='margin-top:10px;'>Brak załączników.</p>";
            } else {
                foreach ($filesList as $file) {
    $path         = "uploads/$roomId/" . $file; 
    $fileType     = mime_content_type($uploadDir . $file);
    $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    echo '<div class="file-item">';

    // 1. Sprawdzamy rozszerzenie .webp
    if ($fileExtension === 'webp') {
        // Zamiast wyświetlać obrazek .webp, pokazujemy ikonę photo.png
        echo "<img src='photo.png' alt='webp icon' style='max-width:80px;'><br>";
        // Możesz wyświetlić nazwę pliku, jeśli chcesz:
        // echo htmlspecialchars($file);
    }
    // 2. Jeśli to inny typ 'image' (np. jpg, png)
    elseif (strpos($fileType, 'image') !== false) {
        echo "<img src='$path' alt='obraz'>";
    }
    // 3. Jeśli to plik video
    elseif (strpos($fileType, 'video') !== false) {
        echo "<video src='$path' controls></video>";
    }
    // 4. Wszystko inne
    else {
        // Ikona pliku + nazwa
        echo "<img src='https://img.icons8.com/ios-glyphs/60/000000/file--v1.png' style='max-width:50px'><br>";
        echo htmlspecialchars($file);
    }

    echo "</div>";
}

            }
            ?>
        </div>
    </div>

</div>

<!-- Skrypt JS do obsługi czatu -->
<script>
// Parametry i stałe
const roomId         = "<?php echo htmlspecialchars($roomId); ?>";
const ADMIN_PASSWORD = "Ematlov";  // hasło do roli admina

// Elementy
const chatMessages         = document.getElementById('chatMessages');
const chatInput            = document.getElementById('chatInput');
const sendBtn              = document.getElementById('sendBtn');
const adminLoginToggle     = document.getElementById('adminLoginToggle');
const adminPasswordField   = document.getElementById('adminPasswordField');
const adminPassword        = document.getElementById('adminPassword');
const adminPasswordBtn     = document.getElementById('adminPasswordBtn');

// Flaga: czy jesteśmy adminem
let isAdmin = false;

/**
 * 1. Pokaż/ukryj pole hasła admina (po kliknięciu ikonki "admin").
 */
adminLoginToggle.addEventListener('click', () => {
  if (adminPasswordField.style.display === 'none') {
    adminPasswordField.style.display = 'block';
  } else {
    adminPasswordField.style.display = 'none';
  }
});

/**
 * 2. Po kliknięciu przycisku "OK" sprawdzamy hasło
 */
adminPasswordBtn.addEventListener('click', () => {
  const pass = adminPassword.value.trim();
  if (pass === ADMIN_PASSWORD) {
    isAdmin = true;
    alert('Zalogowano jako admin.');
    adminPasswordField.style.display = 'none';
  } else {
    alert('Błędne hasło.');
    isAdmin = false;
  }
});

/**
 * 3. Pobieranie historii czatu
 */
function fetchChat() {
    // Sprawdzamy, czy user jest blisko dołu czatu
    // warunek: jeśli scrollTop + clientHeight >= scrollHeight - 5
    const wasAtBottom = (
        chatMessages.scrollTop + chatMessages.clientHeight 
        >= chatMessages.scrollHeight - 5
    );

    fetch(`room_action.php?action=get&room_id=${roomId}`)
        .then(res => res.json())
        .then(data => {
            chatMessages.innerHTML = '';

            data.chat.forEach(item => {
                const div = document.createElement('div');
                div.classList.add('bubble');
                if (item.role === 'admin') {
                    div.classList.add('bubble-admin');
                } else {
                    div.classList.add('bubble-user');
                }
                div.textContent = item.message;
                chatMessages.appendChild(div);
            });

            // Jeżeli user był na dole (lub prawie) -> przewijamy
            if (wasAtBottom) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        })
        .catch(err => console.error(err));
}

// Automatyczne powiększanie textarea
chatInput.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = (this.scrollHeight) + 'px';
});

/**
 * 4. Wysyłanie nowej wiadomości
 */
function sendMessage() {
  const msg = chatInput.value.trim();
  if (msg === '') return;

  // Określamy rolę
  const role = isAdmin ? 'admin' : 'user';

  // Tworzymy formData
  const formData = new FormData();
  formData.append('action', 'send');
  formData.append('room_id', roomId);
  formData.append('role', role);
  formData.append('message', msg);

  fetch('room_action.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.error) {
      console.error('Błąd:', data.error);
    } else {
      // Wyczyść input
      chatInput.value = '';
      // Odśwież historię
      fetchChat();
    }
  })
  .catch(err => console.error('Błąd sendMessage:', err));
}

// Obsługa kliknięcia "Wyślij"
sendBtn.addEventListener('click', sendMessage);

// Obsługa Enter w polu textarea (bez SHIFT)
chatInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

// 5. Odświeżaj chat co 3 sekundy
setInterval(fetchChat, 3000);

// 6. Na start pobierz od razu
fetchChat();
</script>
</body>
</html>
