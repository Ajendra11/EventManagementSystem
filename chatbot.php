<?php
session_start();

require_once __DIR__ . '/../backend/ai.php';

if (!isset($_SESSION['chat'])) {
    $_SESSION['chat'] = [];
}

function add_chat($role, $msg) {
    $_SESSION['chat'][] = ["role"=>$role,"msg"=>$msg];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $msg = trim($_POST["message"]);

    if ($msg !== "") {

        add_chat("user", $msg);

        $reply = ask_ai($msg);

        add_chat("bot", $reply);
    }

    header("Location: chatbot.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<style>
body { background:#111; color:white; font-family:Arial; }
.chat-box { width:400px; margin:20px auto; padding:10px; background:#222; height:500px; overflow:auto; }
.user { text-align:right; color:#4fc3f7; }
.bot { text-align:left; color:#a5d6a7; }
form { display:flex; justify-content:center; gap:10px; }
input { width:300px; padding:10px; }
button { padding:10px; }
</style>
</head>

<body>

<div class="chat-box">
<?php foreach($_SESSION['chat'] as $c): ?>
    <div class="<?= $c['role'] ?>">
        <?= htmlspecialchars($c['msg']) ?>
    </div>
<?php endforeach; ?>
</div>

<form method="POST">
    <input name="message" placeholder="Type..." required>
    <button>Send</button>
</form>

</body>
</html>
