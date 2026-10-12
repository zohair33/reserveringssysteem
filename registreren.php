<?php
session_start();
require 'db.php';

$fout = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam']);
    $email = trim($_POST['email']);
    $wachtwoord = password_hash($_POST['wachtwoord'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO klanten (naam, email, wachtwoord) VALUES (?, ?, ?)");
        $stmt->execute([$naam, $email, $wachtwoord]);
        header("Location: login.php?geslaagd=1");
        exit;
    } catch (PDOException $e) {
        $fout = "E-mailadres is waarschijnlijk al in gebruik.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Registreren - Blazter</title>
    <style>
        body { font-family: Arial; background: #f4f4f9; padding: 40px; }
        .box { background: white; padding: 25px; border-radius: 8px; width: 350px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, button { width: 100%; padding: 10px; margin-top: 10px; box-sizing: border-box; }
        button { background: #0066cc; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Registreren</h2>
        <?php if ($fout): ?><p style="color:red;"><?= $fout; ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="naam" placeholder="Volledige naam" required>
            <input type="email" name="email" placeholder="E-mailadres" required>
            <input type="password" name="wachtwoord" placeholder="Wachtwoord" required>
            <button type="submit">Account Aanmaken</button>
        </form>
        <p>Al een account? <a href="login.php">Inloggen</a></p>
    </div>
</body>
</html>