<?php
session_start();

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['handlekurv']) && !empty($_SESSION['handlekurv'])) {
    // Start en transaksjon
    $conn->begin_transaction();
    
    try {
        // Beregn total pris
        $total = 0;
        foreach ($_SESSION['handlekurv'] as $produktID => $antall) {
            $sql = "SELECT Pris FROM Produkt WHERE ProduktID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $produktID);
            $stmt->execute();
            $result = $stmt->get_result();
            $produkt = $result->fetch_assoc();
            $total += $produkt['Pris'] * $antall;
        }
        
        // Opprett bestilling
        $sql = "INSERT INTO bestiling (KundeID, dato, totalpris) VALUES (?, NOW(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $_SESSION['KundeID'], $total);
        $stmt->execute();
        
        $bestillingId = $conn->insert_id; // Get the ID of the newly created order
        
        // Legg til bestillingsdetaljer
        foreach ($_SESSION['handlekurv'] as $produktID => $antall) {
            $sql = "INSERT INTO Bestillingsdetaljer (BestillingID, ProduktID, Antall) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $bestillingId, $produktID, $antall);
            $stmt->execute();
        }
        
        // Oppdater lagerbeholdning
        foreach ($_SESSION['handlekurv'] as $produktID => $antall) {
            $sql = "UPDATE Produkt SET lagerbeholdning = lagerbeholdning - ? WHERE ProduktID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $antall, $produktID);
            $stmt->execute();
        }
        
        // Fullfør transaksjonen
        $conn->commit();
        
        // Tøm handlekurven
        unset($_SESSION['handlekurv']);
        
        // Set success message
        $_SESSION['order_success'] = true;
        $_SESSION['bestilling_id'] = $bestillingId;
        
    } catch (Exception $e) {
        // Hvis noe går galt, tilbakestill transaksjonen
        $conn->rollback();
        $error = "Det oppstod en feil under behandling av bestillingen: " . $e->getMessage();
    }
}

// Hent handlekurvdata hvis den finnes
$products = array();
$total = 0;

if (isset($_SESSION['handlekurv']) && !empty($_SESSION['handlekurv'])) {
    foreach ($_SESSION['handlekurv'] as $produktID => $antall) {
        $sql = "SELECT ProduktID, Navn, Pris FROM Produkt WHERE ProduktID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $produktID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($produkt = $result->fetch_assoc()) {
            $produkt['Antall'] = $antall;
            $produkt['Sum'] = $antall * $produkt['Pris'];
            $products[] = $produkt;
            $total += $produkt['Sum'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasse</title>
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
                            <a class="nav-link" href="profil.php">Min Profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="legg_til_produkt.php">Legg til produkt</a>
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
    
    <div class="container mt-5">
        <?php if (isset($_SESSION['order_success']) && $_SESSION['order_success']): ?>
            <div class="alert alert-success text-center" role="alert">
                <h4 class="alert-heading">Takk for din bestilling!</h4>
                <p>Din bestilling med ordrenummer <?php echo $_SESSION['bestilling_id']; ?> er bekreftet.</p>
                <hr>
                <p class="mb-0">
                    <a href="index.php" class="btn btn-primary">Fortsett å handle</a>
                </p>
            </div>
            <?php 
            // Clear the success message
            unset($_SESSION['order_success']);
            unset($_SESSION['bestilling_id']);
            ?>
        <?php else: ?>
        <h2 class="mb-4">Kasse</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                Handlekurven din er tom.
                <a href="index.php" class="alert-link">Gå til butikken</a>
            </div>
        <?php else: ?>
            <div class="card mb-4">
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
                                <?php foreach ($products as $produkt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($produkt['Navn']); ?></td>
                                        <td><?php echo $produkt['Antall']; ?></td>
                                        <td><?php echo number_format($produkt['Pris'], 2, ',', ' '); ?> kr</td>
                                        <td><?php echo number_format($produkt['Sum'], 2, ',', ' '); ?> kr</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong><?php echo number_format($total, 2, ',', ' '); ?> kr</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <form method="POST" action="" class="mt-3">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Bekreft bestilling</button>
                            <a href="handlekurv.php" class="btn btn-secondary">Tilbake til handlekurv</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
