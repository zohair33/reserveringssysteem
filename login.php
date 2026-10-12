<?php
session_start();
require 'db.php';

$fout = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $wachtwoord = $_POST['wachtwoord'];

    $stmt = $pdo->prepare("SELECT * FROM klanten WHERE email = ?");
    $stmt->execute([$email]);
    $klant = $stmt->fetch();

    if ($klant && password_verify($wachtwoord, $klant['wachtwoord'])) {
        $_SESSION['klant_id'] = $klant['id'];
        $_SESSION['klant_naam'] = $klant['naam'];
        header("Location: reserveren.php");
        exit;
    } else {
        $fout = "Ongeldige e-mail of wachtwoord combinatie.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Inloggen - Blazter</title>
    <style>
        body { font-family: Arial; background: #f4f4f9; padding: 40px; }
        .box { background: white; padding: 25px; border-radius: 8px; width: 350px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, button { width: 100%; padding: 10px; margin-top: 10px; box-sizing: border-box; }
        button { background: #0066cc; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Inloggen</h2>
        <?php if (isset($_GET['geslaagd'])): ?><p style="color:green;">Account aangemaakt! Log hieronder in.</p><?php endif; ?>
        <?php if ($fout): ?><p style="color:red;"><?= $fout; ?></p><?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="E-mailadres" required>
            <input type="password" name="wachtwoord" placeholder="Wachtwoord" required>
            <button type="submit">Inloggen</button>
        </form>
        <p>Nog geen account? <a href="registreren.php">Registreren</a></p>
    </div>
</body>
</html>