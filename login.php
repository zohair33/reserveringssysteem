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
        session_regenerate_id(true);
        $_SESSION['klant_id'] = $klant['id'];
        $_SESSION['klant_naam'] = $klant['naam'];
        $_SESSION['is_admin'] = $klant['is_admin'];

        // Automatisch doorsturen op basis van rol
        if ($klant['is_admin'] == 1) {
            header("Location: admin.php");
        } else {
            header("Location: reserveren.php");
        }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 400px;">
        <div class="card shadow p-4">
            <h2 class="mb-4">Inloggen</h2>
            <?php if (isset($_GET['geslaagd'])): ?>
                <div class="alert alert-success">Account aangemaakt! Log hieronder in.</div>
            <?php endif; ?>
            <?php if ($fout): ?>
                <div class="alert alert-danger"><?= $fout; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">E-mailadres</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Wachtwoord</label>
                    <input type="password" name="wachtwoord" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Inloggen</button>
            </form>
            <p class="mt-3 text-center">Nog geen account? <a href="registreren.php">Registreren</a></p>
        </div>
    </div>
</body>
</html>