<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "nynettbutikk";

// Opprett databasetilkobling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Tilkobling mislyktes: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Kunne ikke koble til databasen. Vennligst prøv igjen senere.");
}

// Initialiser handlekurv
if (!isset($_SESSION['handlekurv'])) {
    $_SESSION['handlekurv'] = [];
}

// Håndter sletting av produkt
if (isset($_POST['slett'])) {
    try {
        // Start en transaksjon
        $conn->begin_transaction();

        $produkt_id = $_POST['produkt_id'];

        // Først slett fra bestillingsdetaljer
        $sql_delete_details = "DELETE FROM bestillingsdetaljer WHERE ProduktID = ?";
        $stmt = $conn->prepare($sql_delete_details);
        if (!$stmt) {
            throw new Exception("Kunne ikke forberede spørringen for bestillingsdetaljer: " . $conn->error);
        }
        $stmt->bind_param("i", $produkt_id);
        if (!$stmt->execute()) {
            throw new Exception("Kunne ikke slette fra bestillingsdetaljer: " . $stmt->error);
        }
        $stmt->close();

        // Så slett selve produktet
        $sql_delete_product = "DELETE FROM Produkt WHERE ProduktID = ?";
        $stmt = $conn->prepare($sql_delete_product);
        if (!$stmt) {
            throw new Exception("Kunne ikke forberede spørringen for produkt: " . $conn->error);
        }
        $stmt->bind_param("i", $produkt_id);
        if (!$stmt->execute()) {
            throw new Exception("Kunne ikke slette produktet: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaksjonen
        $conn->commit();

        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        // Hvis noe går galt, rollback transaksjonen
        $conn->rollback();
        die("En feil oppstod ved sletting av produkt: " . $e->getMessage());
    }
}

// Håndter legg til i handlekurv
if (isset($_POST['legg_til'])) {
    $produkt_id = $_POST['produkt_id'];
    
    // Sjekk lagerbeholdning
    $sql = "SELECT lagerbeholdning FROM Produkt WHERE ProduktID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produkt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produkt = $result->fetch_assoc();
    
    // Beregn total antall i handlekurv + ny mengde
    $current_cart_quantity = isset($_SESSION['handlekurv'][$produkt_id]) ? $_SESSION['handlekurv'][$produkt_id] : 0;
    $new_quantity = $current_cart_quantity + 1;
    
    if ($produkt && $produkt['lagerbeholdning'] >= $new_quantity) {
        if (!isset($_SESSION['handlekurv'][$produkt_id])) {
            $_SESSION['handlekurv'][$produkt_id] = 1;
        } else {
            $_SESSION['handlekurv'][$produkt_id]++;
        }
        $_SESSION['success_message'] = "Produktet ble lagt til i handlekurven.";
    } else {
        $_SESSION['error_message'] = "Beklager, ikke nok på lager.";
    }
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nettbutikk</title>
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
                        <a class="nav-link active" href="index.php">Hjem</a>
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

    <div class="container my-5">
        <h1 class="page-title">Våre Produkter</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php
        try {
            // Hent alle produkter
            $sql = "SELECT ProduktID, navn, Pris, info, lagerbeholdning FROM Produkt";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Kunne ikke hente produkter: " . $conn->error);
            }

            if ($result->num_rows > 0) {
                echo '<div class="row g-4">';
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($row['navn']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($row['info']); ?></p>
                                <p class="price mb-3">
                                    kr <?php echo number_format($row['Pris'], 2, ',', ' '); ?>
                                </p>
                                <p class="card-text mb-4">
                                    <?php if ($row['lagerbeholdning'] > 10): ?>
                                        <span class="badge bg-success">På lager</span>
                                    <?php elseif ($row['lagerbeholdning'] > 0): ?>
                                        <span class="badge bg-warning">Få på lager</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ikke på lager</span>
                                    <?php endif; ?>
                                </p>
                                <div class="action-buttons">
                                    <form method="post" class="d-grid">
                                        <input type="hidden" name="produkt_id" value="<?php echo $row['ProduktID']; ?>">
                                        <button type="submit" name="legg_til" class="btn btn-primary">
                                            <i class="bi bi-cart-plus"></i> Legg i handlekurv
                                        </button>
                                    </form>
                                    <form method="post" class="d-grid" onsubmit="return confirm('Er du sikker på at du vil slette dette produktet?');">
                                        <input type="hidden" name="produkt_id" value="<?php echo $row['ProduktID']; ?>">
                                        <button type="submit" name="slett" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Slett
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                echo '</div>';
            } else {
                ?>
                <div class="empty-state">
                    <i class="bi bi-bag-plus"></i>
                    <h3>Start din nettbutikk</h3>
                    <p>Din butikk er klar til å fylles med fantastiske produkter. Kom i gang ved å legge til ditt første produkt!</p>
                    <a href="legg_til_produkt.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>Legg til første produkt
                    </a>
                </div>
                <?php
            }
        } catch (Exception $e) {
            ?>
            <div class="error-message">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo $e->getMessage(); ?>
            </div>
            <?php
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>