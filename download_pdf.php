<?php

session_start();
require 'db.php';

// Controleer of de benodigde bestanden/bibliotheken er zijn
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    die("Composer autoload niet gevonden. Zorg dat je 'composer require dompdf/dompdf' hebt uitgevoerd.");
}

use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die("Geen reservering opgegeven.");
}

$reservering_id = $_GET['id'];
$klant_id = $_SESSION['klant_id'] ?? 1;

// Haal de gegevens van de reservering, klant en ruimte op uit de database
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
    die("Reservering niet gevonden of geen toegang.");
}

// HTML-template voor de PDF
$html = '
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #333; line-height: 1.6; }
        .header { background: #0066cc; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .details { background: #f9f9f9; padding: 15px; border-left: 4px solid #0066cc; margin: 20px 0; }
        .footer { font-size: 12px; text-align: center; color: #777; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reserveringsbevestiging</h2>
        <p>Blazter Reserveringssysteem</p>
    </div>
    <div class="content">
        <p>Beste <b>' . htmlspecialchars($reservering['klant_naam']) . '</b>,</p>
        <p>Hartelijk dank voor je reservering. Hieronder vind je de details van jouw boeking:</p>
        
        <div class="details">
            <p><b>Reserveringsnummer:</b> #' . $reservering['id'] . '</p>
            <p><b>Gereserveerd Object:</b> ' . htmlspecialchars($reservering['object_naam']) . '</p>
            <p><b>Datum:</b> ' . $reservering['datum'] . '</p>
            <p><b>Tijdvak:</b> ' . $reservering['start_tijd'] . ' tot ' . $reservering['eind_tijd'] . '</p>
        </div>

        <p>Mocht je de reservering willen wijzigen of annuleren, dan kun je dit doen via jouw overzicht op de website.</p>
    </div>
    <div class="footer">
        <p>&copy; 2026 Blazter - Alle rechten voorbehouden</p>
    </div>
</body>
</html>';

// Genereer de PDF met Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Download de PDF direct in de browser van de klant
$dompdf->stream("reservering_" . $reservering['id'] . ".pdf", ["Attachment" => true]);
exit;
?>