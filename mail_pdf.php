<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

$reservering_id = $_GET['id'] ?? null;
$klant_id = $_SESSION['klant_id'] ?? null;

if (!$reservering_id || !$klant_id) {
    die("Geen reservering geselecteerd.");
}

// 1. Haal reserveringsgegevens op
$stmt = $pdo->prepare("
    SELECT r.id, k.naam AS klant_naam, k.email, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd 
    FROM reserveringen r
    JOIN klanten k ON r.klant_id = k.id
    JOIN beschikbaarheid b ON r.beschikbaarheid_id = b.id
    JOIN objecten o ON b.object_id = o.id
    WHERE r.id = ? AND r.klant_id = ?
");
$stmt->execute([$reservering_id, $klant_id]);
$reservering = $stmt->fetch();

if (!$reservering) {
    die("Reservering niet gevonden.");
}

// 2. Genereer PDF in geheugen
$html = "
    <h2>Reserveringsbevestiging - Blazter</h2>
    <p>Beste <b>{$reservering['klant_naam']}</b>,</p>
    <p>Hierbij ontvang je de bevestiging van je reservering:</p>
    <ul>
        <li><b>Reserveringsnummer:</b> #{$reservering['id']}</li>
        <li><b>Object/Ruimte:</b> {$reservering['object_naam']}</li>
        <li><b>Datum:</b> {$reservering['datum']}</li>
        <li><b>Tijd:</b> {$reservering['start_tijd']} - {$reservering['eind_tijd']}</li>
    </ul>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfContent = $dompdf->output();

// 3. Verstuur Mail met PHPMailer
$mail = new PHPMailer(true);
try {
    // Mail instellingen (bijv. SMTP/Localhost)
    $mail->isSMTP();
    $mail->Host       = 'localhost'; // Of je SMTP server
    $mail->SMTPAuth   = false;
    $mail->Port       = 25;

    $mail->setFrom('no-reply@blazter.nl', 'Blazter Reserveringen');
    $mail->addAddress($reservering['email'], $reservering['klant_naam']);

    // Voeg PDF bij
    $mail->addStringAttachment($pdfContent, 'reservering_' . $reservering['id'] . '.pdf');

    $mail->isHTML(true);
    $mail->Subject = 'Jouw Reserveringsbevestiging - Blazter';
    $mail->Body    = 'Beste ' . htmlspecialchars($reservering['klant_naam']) . ',<br><br>In de bijlage vind je de PDF van jouw reservering.<br><br>Met vriendelijke groet,<br>Blazter';

    $mail->send();
    header("Location: mijn_reserveringen.php?mailed=1");
} catch (Exception $e) {
    echo "Mail kon niet verzonden worden: {$mail->ErrorInfo}";
}
?>