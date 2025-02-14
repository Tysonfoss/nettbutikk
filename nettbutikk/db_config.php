<?php
// Database tilkoblingsdetaljer
$tjenerNavn = "localhost";
$brukerNavn = "root";
$passord = "root";
$databaseNavn = "nynettbutikk";

try {
    // Opprett databasetilkobling
    $db = new mysqli($tjenerNavn, $brukerNavn, $passord, $databaseNavn);
    $db->set_charset("utf8mb4");

    // Sjekk om bilde-kolonnen eksisterer, hvis ikke, legg den til
    $result = $db->query("SHOW COLUMNS FROM Produkt LIKE 'bilde'");
    if ($result && $result->num_rows === 0) {
        $db->query("ALTER TABLE Produkt ADD COLUMN bilde VARCHAR(255) DEFAULT NULL");
    }
} catch (Exception $e) {
    die("Kunne ikke koble til databasen: " . $e->getMessage());
}
?>
