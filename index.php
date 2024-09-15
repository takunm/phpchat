<?php
session_start();

$chatHistory = [];
$chatFilePath = 'chat_history.txt';

if (file_exists($chatFilePath)) {
    $chatHistory = json_decode(file_get_contents($chatFilePath), true);
}

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $usersFilePath = 'users.txt';
    $users = [];

    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true);
    }

    $users[$username] = ['password' => $password];
    file_put_contents($usersFilePath, json_encode($users));

    echo '新規登録が完了しました。';
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $usersFilePath = 'users.txt';
    $users = [];

    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true);
    }

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['username'] = $username;
    } else {
        echo 'ユーザー名またはパスワードが正しくありません。';
    }
}

if (isset($_POST['delete'])) {
    echo '本当に削除してもよろしいですか？';
    echo '<form method="post" action="index.php">';
    echo '<input type="hidden" name="confirmDelete" value="true">';
    echo '<input type="submit" value="はい">';
    echo '<input type="button" value="いいえ" onclick="location.href=\'index.php\'">';
    echo '</form>';
}

if (isset($_POST['confirmDelete'])) {
    $deletedUsername = $_SESSION['username'];
    unset($_SESSION['username']);

    $usersFilePath = 'users.txt';
    $users = [];

    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true);
    }

    if (isset($users[$deletedUsername])) {
        unset($users[$deletedUsername]);
        file_put_contents($usersFilePath, json_encode($users));
    }

    file_put_contents($chatFilePath, '');

    echo 'アカウントが削除されました。';
}

if (isset($_POST['message']) && isset($_SESSION['username'])) {
    $message = $_POST['message'];
    $username = $_SESSION['username'];

    $chatHistory[] = ['username' => $username, 'message' => $message];

    file_put_contents($chatFilePath, json_encode($chatHistory));
}

if (!isset($_SESSION['username'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chat App</title>
      <style>
        body {
          background-color: #f1f1f1;
        }

        form {
          background-color: white;
          border-radius: 5px;
          box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.1);
          width: 500px;
          margin: 50px auto;
          padding: 20px;
          font-family: Arial, sans-serif;
        }

        h2 {
          font-size: 20px;
          margin-bottom: 10px;
        }

        label {
          font-size: 16px;
          display: block;
          margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"] {
          border: 1px solid #ccc;
          border-radius: 3px;
          padding: 5px;
          width: 100%;
          margin-bottom: 10px;
          font-size: 16px;
        }

        input[type="submit"] {
          background-color: #4CAF50;
          color: white;
          border: none;
          border-radius: 3px;
          padding: 10px;
          font-size: 16px;
          cursor: pointer;
        }

        input[type="submit"]:hover {
          background-color: #3e8e41;
        }
      </style>
    </head>
    <body>
        <h2>ログイン</h2>
        <form method="post" action="index.php">
            <label for="username">ユーザー名:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <input type="submit" name="login" value="ログイン">
        </form>
        <h2>新規登録</h2>
        <form method="post" action="index.php">
            <label for="new-username">ユーザー名:</label>
            <input type="text" id="new-username" name="username" required>
            <br>
            <label for="new-password">パスワード:</label>
            <input type="password" id="new-password" name="password" required>
            <br>
            <input type="submit" name="register" value="新規登録">
        </form>
    </body>
    </html>
    <?php
} else {
    ?>
  <!DOCTYPE html>
  <html lang="ja">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Chat Room</title>
      <style>
          #chat-box {
              height: 200px;
              overflow-y: scroll;
              border: 1px solid #ccc;
              padding: 10px;
          }
      </style>
  </head>
  <body>
      <h2>ようこそ、<?php echo htmlspecialchars($_SESSION['username']); ?>さん！</h2>
      <div>
          <h3>チャット</h3>
          <form onsubmit="sendMessage(); return false;">
              <div id="chat-box"></div>
              <input type="text" id="message" placeholder="メッセージを入力" required>
              <input type="submit" value="送信">
          </form>
          <form method="post" action="index.php">
              <input type="submit" name="logout" value="ログアウト">
          </form>
          <h3>アカウント削除</h3>
          <form method="post" action="index.php">
                <input type="submit" name="delete" value="アカウント削除">
            </form>
        </div>
        <script>
            function sendMessage() {
                var message = document.getElementById('message').value;
                var chatBox = document.getElementById('chat-box');
                var username = "<?php echo htmlspecialchars($_SESSION['username']); ?>";

                var currentDate = new Date();
                var timestamp = currentDate.toLocaleString('ja-JP', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' });
                chatBox.innerHTML += '<p><strong>' + username + ':</strong> ' + message + ' (' + timestamp + ')</p>';
                document.getElementById('message').value = '';

                chatBox.scrollTop = chatBox.scrollHeight;

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'index.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('message=' + encodeURIComponent(message));
            }

            var chatHistory = <?php echo json_encode($chatHistory); ?>;
            var chatBox = document.getElementById('chat-box');

            chatHistory.forEach(function (entry) {
                var timestamp = new Date(entry.timestamp);
                var formattedTimestamp = timestamp.toLocaleString('ja-JP', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' });
                chatBox.innerHTML += '<p><strong>' + entry.username + ':</strong> ' + entry.message + ' (' + formattedTimestamp + ')</p>';
            });

            chatBox.scrollTop = chatBox.scrollHeight;
        </script>
    <link rel="stylesheet" href="style.css">
    </body>
    </html>
  <?php
  }
  ?>
