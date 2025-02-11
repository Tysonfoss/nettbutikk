<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "nynettbutikk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Tilkobling mislyktes: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Håndter oppdatering av handlekurv
if (isset($_POST['oppdater'])) {
    $error = false;
    foreach ($_POST['antall'] as $id => $antall) {
        if ($antall > 0) {
            // Sjekk lagerbeholdning
            $sql = "SELECT lagerbeholdning FROM Produkt WHERE ProduktID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $produkt = $result->fetch_assoc();
            
            if ($produkt && $produkt['lagerbeholdning'] >= $antall) {
                $_SESSION['handlekurv'][$id] = $antall;
            } else {
                $error = true;
                $_SESSION['error_message'] = "Beklager, ikke nok på lager for noen produkter. Mengden har blitt justert.";
                if ($produkt) {
                    $_SESSION['handlekurv'][$id] = $produkt['lagerbeholdning'];
                } else {
                    unset($_SESSION['handlekurv'][$id]);
                }
            }
        } else {
            unset($_SESSION['handlekurv'][$id]);
        }
    }
    if (!$error) {
        $_SESSION['success_message'] = "Handlekurven er oppdatert.";
    }
    header("Location: handlekurv.php");
    exit();
}

// Fjern produkt fra handlekurv
if (isset($_POST['fjern_produkt'])) {
    $id = $_POST['produkt_id'];
    unset($_SESSION['handlekurv'][$id]);
    header("Location: handlekurv.php");
    exit();
}

// Beregn total og hent produktinformasjon
$total = 0;
$handlekurv_produkter = [];

if (!empty($_SESSION['handlekurv'])) {
    $produkt_ids = array_keys($_SESSION['handlekurv']);
    $ids_string = implode(',', array_map('intval', $produkt_ids));
    
    $sql = "SELECT ProduktID, navn, Pris, info, lagerbeholdning FROM Produkt WHERE ProduktID IN ($ids_string)";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $antall = $_SESSION['handlekurv'][$row['ProduktID']];
            $row['antall'] = $antall;
            $row['sum'] = $antall * (float)$row['Pris'];
            $total += $row['sum'];
            $handlekurv_produkter[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handlekurv</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigasjon -->
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
                        <a class="nav-link" href="produkter.php">Produkter</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="handlekurv.php">Handlekurv</a>
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
        <h1 class="mb-4">Din Handlekurv</h1>
        
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
        
        <?php if (!empty($handlekurv_produkter)): ?>
            <form method="post" action="" class="cart-form">
                <?php foreach ($handlekurv_produkter as $produkt): ?>
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1"><?php echo htmlspecialchars($produkt['navn']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($produkt['info']); ?></p>
                            </div>
                            <div class="col-md-2">
                                <span class="text-muted">kr <?php echo number_format($produkt['Pris'], 2, ',', ' '); ?></span>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="antall[<?php echo $produkt['ProduktID']; ?>]" 
                                       value="<?php echo $produkt['antall']; ?>" 
                                       min="0" class="form-control quantity-input">
                            </div>
                            <div class="col-md-1">
                                <span class="product-total">kr <?php echo number_format($produkt['sum'], 2, ',', ' '); ?></span>
                            </div>
                            <div class="col-md-1 text-end">
                                <!-- Flyttet slett-knappen ut av hovedformen -->
                                <form method="post" action="" class="d-inline">
                                    <input type="hidden" name="produkt_id" value="<?php echo $produkt['ProduktID']; ?>">
                                    <button type="submit" name="fjern_produkt" class="btn-remove" title="Fjern produkt">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <button type="submit" name="oppdater" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise"></i> Oppdater handlekurv
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="cart-total mb-3">
                            Totalt: kr <?php echo number_format($total, 2, ',', ' '); ?>
                        </div>
                        <?php if (isset($_SESSION['KundeID'])): ?>
                            <a href="kasse.php" class="btn btn-success btn-lg">
                                <i class="bi bi-credit-card"></i> Gå til kassen
                            </a>
                        <?php else: ?>
                            <a href="logginn.php" class="btn btn-primary">
                                Logg inn for å bestille <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <h3>Handlekurven er tom</h3>
                <p class="text-muted">Du har ingen produkter i handlekurven din.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-shop"></i> Fortsett å handle
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
