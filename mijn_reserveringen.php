<?php
session_start();
require 'db.php';

if (!isset($_SESSION['klant_id'])) {
    header("Location: login.php");
    exit;
}

$klant_id = $_SESSION['klant_id'];
$melding = '';

if (isset($_GET['annuleer_id'])) {
    $reservering_id = $_GET['annuleer_id'];

    try {
        $pdo->beginTransaction();
        $stmtCheck = $pdo->prepare("SELECT beschikbaarheid_id FROM reserveringen WHERE id = ? AND klant_id = ?");
        $stmtCheck->execute([$reservering_id, $klant_id]);
        $res = $stmtCheck->fetch();

        if ($res) {
            $beschikbaarheid_id = $res['beschikbaarheid_id'];
            $stmtDel = $pdo->prepare("DELETE FROM reserveringen WHERE id = ?");
            $stmtDel->execute([$reservering_id]);

            $stmtUpdate = $pdo->prepare("UPDATE beschikbaarheid SET is_beschikbaar = 1 WHERE id = ?");
            $stmtUpdate->execute([$beschikbaarheid_id]);

            $pdo->commit();
            $melding = "<div class='alert alert-success'>Reservering succesvol geannuleerd!</div>";
        } else {
            $pdo->rollBack();
            $melding = "<div class='alert alert-danger'>Kon reservering niet vinden.</div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $melding = "<div class='alert alert-danger'>Fout bij annuleren: " . $e->getMessage() . "</div>";
    }
}

$stmtRes = $pdo->prepare("
    SELECT r.id AS reservering_id, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd 
    FROM reserveringen r
    JOIN beschikbaarheid b ON r.beschikbaarheid_id = b.id
    JOIN objecten o ON b.object_id = o.id
    WHERE r.klant_id = ?
");
$stmtRes->execute([$klant_id]);
$reserveringen = $stmtRes->fetchAll();
$aantal_reserveringen = count($reserveringen);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn Reserveringen - Blazter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Mijn Reserveringen</h2>
            <div>
                <a href="reserveren.php" class="btn btn-outline-primary btn-sm">Nieuwe Reservering</a>
                <a href="profiel.php" class="btn btn-outline-secondary btn-sm">Profiel</a>
                <a href="uitloggen.php" class="btn btn-danger btn-sm">Uitloggen</a>
            </div>
        </div>

        <div class="card shadow p-4">
            <p>Ingelogd als: <b><?= htmlspecialchars($_SESSION['klant_naam']); ?></b></p>

            <!-- Statistieken widget -->
            <div class="alert alert-info">
                📊 Statistiek: Je hebt momenteel <b><?= $aantal_reserveringen; ?></b> actieve reservering(en).
            </div>

            <?= $melding; ?>

            <?php if ($aantal_reserveringen > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Ruimte / Object</th>
                                <th>Datum & Tijd</th>
                                <th>Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reserveringen as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['object_naam']); ?></td>
                                    <td><?= $item['datum']; ?> (<?= $item['start_tijd']; ?> - <?= $item['eind_tijd']; ?>)</td>
                                    <td>
                                        <a href="download_pdf.php?id=<?= $item['reservering_id']; ?>" class="btn btn-sm btn-primary" target="_blank">PDF</a>
                                        <a href="wijzigen.php?id=<?= $item['reservering_id']; ?>" class="btn btn-sm btn-warning">Wijzigen</a>
                                        <a href="mijn_reserveringen.php?annuleer_id=<?= $item['reservering_id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Weet je zeker dat je wilt annuleren?');">
                                           Annuleren
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Je hebt momenteel geen actieve reserveringen.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>