<?php
session_start();

// Sjekk om bruker er logget inn
if (!isset($_SESSION['KundeID'])) {
    header("Location: logginn.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "nynettbutikk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Tilkobling mislyktes: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Hent brukerinfo
$sql = "SELECT Navn, etternavn, Epost, adressa, postnummer, poststad FROM kunde WHERE KundeID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['KundeID']);
$stmt->execute();
$result = $stmt->get_result();
$bruker = $result->fetch_assoc();

// Hent alle bestillinger for brukeren
$sql = "SELECT b.BestilingID, b.dato, b.totalpris, 
        GROUP_CONCAT(CONCAT(p.navn, ' (', bd.Antall, ' stk)') SEPARATOR ', ') as produkter
        FROM bestiling b
        JOIN Bestillingsdetaljer bd ON b.BestilingID = bd.BestillingID
        JOIN Produkt p ON bd.ProduktID = p.ProduktID
        WHERE b.KundeID = ?
        GROUP BY b.BestilingID
        ORDER BY b.dato DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['KundeID']);
$stmt->execute();
$bestillinger = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Min Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-2">
            <a class="navbar-brand" href="index.php">Nettbutikk</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Hjem</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="handlekurv.php">Handlekurv</a>
                    </li>
                    <?php if (isset($_SESSION['KundeID'])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="profil.php">Min Profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="loggut.php">Logg ut</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logginn.php">Logg inn</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registrer.php">Registrer</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Profilinformasjon</h5>
                        <p><strong>Navn:</strong> <?php echo htmlspecialchars($bruker['Navn'] . ' ' . $bruker['etternavn']); ?></p>
                        <p><strong>E-post:</strong> <?php echo htmlspecialchars($bruker['Epost']); ?></p>
                        <p><strong>Adresse:</strong> <?php echo htmlspecialchars($bruker['adressa']); ?></p>
                        <p><strong>Postnummer:</strong> <?php echo htmlspecialchars($bruker['postnummer']); ?></p>
                        <p><strong>Poststed:</strong> <?php echo htmlspecialchars($bruker['poststad']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <h2 class="mb-4">Mine Bestillinger</h2>
                <?php if ($bestillinger->num_rows > 0): ?>
                    <div class="accordion" id="ordersAccordion">
                        <?php while ($bestilling = $bestillinger->fetch_assoc()): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#order<?php echo $bestilling['BestilingID']; ?>">
                                        Ordre #<?php echo $bestilling['BestilingID']; ?> - 
                                        <?php echo date('d.m.Y', strtotime($bestilling['dato'])); ?> - 
                                        kr <?php echo $bestilling['totalpris']; ?>
                                    </button>
                                </h2>
                                <div id="order<?php echo $bestilling['BestilingID']; ?>" class="accordion-collapse collapse" 
                                     data-bs-parent="#ordersAccordion">
                                    <div class="accordion-body">
                                        <p><strong>Produkter:</strong></p>
                                        <p><?php echo htmlspecialchars($bestilling['produkter']); ?></p>
                                        <p><strong>Total:</strong> kr <?php echo $bestilling['totalpris']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Du har ingen bestillinger ennå.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
