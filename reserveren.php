<?php
session_start();
require 'db.php';

if (!isset($_SESSION['klant_id'])) {
    header("Location: login.php");
    exit;
}

$klant_id = $_SESSION['klant_id'];
$geselecteerde_datum = $_GET['datum'] ?? '';
$melding = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beschikbaarheid_id = $_POST['beschikbaarheid_id'];

    try {
        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("SELECT is_beschikbaar FROM beschikbaarheid WHERE id = ? FOR UPDATE");
        $stmtCheck->execute([$beschikbaarheid_id]);
        $slot = $stmtCheck->fetch();

        if ($slot && $slot['is_beschikbaar'] == 1) {
            $stmtRes = $pdo->prepare("INSERT INTO reserveringen (klant_id, beschikbaarheid_id) VALUES (?, ?)");
            $stmtRes->execute([$klant_id, $beschikbaarheid_id]);

            $stmtUpdate = $pdo->prepare("UPDATE beschikbaarheid SET is_beschikbaar = 0 WHERE id = ?");
            $stmtUpdate->execute([$beschikbaarheid_id]);

            $pdo->commit();
            $melding = "<div class='alert alert-success'>Reservering succesvol geplaatst!</div>";
        } else {
            $pdo->rollBack();
            $melding = "<div class='alert alert-danger'>Dit tijdvak is zojuist door iemand anders geboekt.</div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $melding = "<div class='alert alert-danger'>Fout: " . $e->getMessage() . "</div>";
    }
}

// Ophalen met of zonder datumfilter
if ($geselecteerde_datum) {
    $stmtSlots = $pdo->prepare("
        SELECT b.id, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd
        FROM beschikbaarheid b
        JOIN objecten o ON b.object_id = o.id
        WHERE b.is_beschikbaar = 1 AND b.datum = ?
    ");
    $stmtSlots->execute([$geselecteerde_datum]);
} else {
    $stmtSlots = $pdo->query("
        SELECT b.id, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd
        FROM beschikbaarheid b
        JOIN objecten o ON b.object_id = o.id
        WHERE b.is_beschikbaar = 1
    ");
}
$tijdvakken = $stmtSlots->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Reserveren - Blazter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Ruimte Reserveren</h2>
            <div>
                <a href="mijn_reserveringen.php" class="btn btn-outline-primary btn-sm">Mijn Reserveringen</a>
                <a href="profiel.php" class="btn btn-outline-secondary btn-sm">Profiel</a>
                <a href="uitloggen.php" class="btn btn-danger btn-sm">Uitloggen</a>
            </div>
        </div>

        <div class="card shadow p-4 mb-4">
            <p>Welkom, <b><?= htmlspecialchars($_SESSION['klant_naam']); ?></b>!</p>
            <?= $melding; ?>

            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label">Filter op datum:</label>
                    <input type="date" name="datum" class="form-control" value="<?= htmlspecialchars($geselecteerde_datum); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">Filteren</button>
                </div>
            </form>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Kies een beschikbaar tijdvak:</label>
                    <select name="beschikbaarheid_id" class="form-select" required>
                        <option value="">-- Selecteer tijdvak --</option>
                        <?php foreach ($tijdvakken as $slot): ?>
                            <option value="<?= $slot['id']; ?>">
                                <?= htmlspecialchars($slot['object_naam']); ?> (<?= $slot['datum']; ?> van <?= $slot['start_tijd']; ?> tot <?= $slot['eind_tijd']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Bevestig Reservering</button>
            </form>
        </div>
    </div>
</body>
</html>