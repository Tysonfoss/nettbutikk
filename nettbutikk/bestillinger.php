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

// Hent alle bestillinger for denne kunden
$sql = "SELECT bestilling.BestillingID, bestilling.Dato, bestilling.TotalPris, 
               produkt.Navn as ProduktNavn, bestillingslinje.Antall, bestillingslinje.Pris as EnhetsPris 
        FROM bestilling 
        JOIN bestillingslinje ON bestilling.BestillingID = bestillingslinje.BestillingID 
        JOIN produkt ON bestillingslinje.ProduktID = produkt.ProduktID 
        WHERE bestilling.KundeID = ? 
        ORDER BY bestilling.Dato DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['KundeID']);
$stmt->execute();
$result = $stmt->get_result();

$orders = array();
while ($row = $result->fetch_assoc()) {
    $bestillingID = $row['BestillingID'];
    if (!isset($orders[$bestillingID])) {
        $orders[$bestillingID] = array(
            'dato' => $row['Dato'],
            'totalPris' => $row['TotalPris'],
            'produkter' => array()
        );
    }
    $orders[$bestillingID]['produkter'][] = array(
        'navn' => $row['ProduktNavn'],
        'antall' => $row['Antall'],
        'pris' => $row['EnhetsPris']
    );
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dine bestillinger</title>
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
                            <a class="nav-link active" href="bestillinger.php"><?php echo htmlspecialchars($_SESSION['Navn']); ?></a>
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
        <h2 class="mb-4">Dine bestillinger</h2>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                Du har ingen bestillinger ennå.
            </div>
        <?php else: ?>
            <?php foreach ($orders as $bestillingID => $ordre): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <strong>Bestilling #<?php echo $bestillingID; ?></strong>
                            </div>
                            <div class="col text-end">
                                <?php echo date('d.m.Y', strtotime($ordre['dato'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produkt</th>
                                        <th>Antall</th>
                                        <th>Pris</th>
                                        <th>Sum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ordre['produkter'] as $produkt): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($produkt['navn']); ?></td>
                                            <td><?php echo $produkt['antall']; ?></td>
                                            <td><?php echo number_format($produkt['pris'], 2, ',', ' '); ?> kr</td>
                                            <td><?php echo number_format($produkt['pris'] * $produkt['antall'], 2, ',', ' '); ?> kr</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong><?php echo number_format($ordre['totalPris'], 2, ',', ' '); ?> kr</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
