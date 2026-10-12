<?php
session_start();
require 'db.php';

if (!isset($_SESSION['klant_id'])) {
    header("Location: login.php");
    exit;
}

$klant_id = $_SESSION['klant_id'];
$melding = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam']);
    $email = trim($_POST['email']);

    try {
        $stmt = $pdo->prepare("UPDATE klanten SET naam = ?, email = ? WHERE id = ?");
        $stmt->execute([$naam, $email, $klant_id]);
        $_SESSION['klant_naam'] = $naam;
        $melding = "<div class='alert alert-success'>Profiel succesvol bijgewerkt!</div>";
    } catch (PDOException $e) {
        $melding = "<div class='alert alert-danger'>Fout bij bijwerken (misschien is het e-mailadres al in gebruik).</div>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM klanten WHERE id = ?");
$stmt->execute([$klant_id]);
$klant = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn Profiel - Blazter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow p-4">
            <h2 class="mb-4">Mijn Profiel</h2>
            <?= $melding; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Volledige naam</label>
                    <input type="text" name="naam" class="form-control" value="<?= htmlspecialchars($klant['naam']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mailadres</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($klant['email']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Opslaan</button>
            </form>
            <div class="mt-3 text-center">
                <a href="mijn_reserveringen.php" class="btn btn-secondary btn-sm">Terug naar overzicht</a>
            </div>
        </div>
    </div>
</body>
</html>