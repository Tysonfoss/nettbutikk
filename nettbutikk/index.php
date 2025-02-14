<?php
// Start sesjon og koble til databasen
session_start();
require_once 'db_config.php';

// Initialiser handlekurv hvis den ikke eksisterer
if (!isset($_SESSION['handlekurv'])) {
    $_SESSION['handlekurv'] = [];
}

// Håndter sletting av produkt fra databasen
// Dette skjer når admin sletter et produkt
if (isset($_POST['slett']) && isset($_POST['produkt_id'])) {
    $produktId = $_POST['produkt_id'];
    
    // Slett produktbildet fra serveren hvis det finnes
    $bildeSti = $db->query("SELECT bilde FROM Produkt WHERE ProduktID = $produktId")->fetch_assoc()['bilde'];
    if ($bildeSti && file_exists('bilder/' . $bildeSti)) {
        unlink('bilder/' . $bildeSti); // Fjern bildefilen fra serveren
    }
    
    // Slett produktet fra bestillingsdetaljer og produkt-tabellen
    $db->query("DELETE FROM bestillingsdetaljer WHERE ProduktID = $produktId");
    $db->query("DELETE FROM Produkt WHERE ProduktID = $produktId");
    header("Location: index.php");
    exit();
}

// Håndter når bruker legger til produkt i handlekurven
if (isset($_POST['legg_til']) && isset($_POST['produkt_id'])) {
    $produktId = $_POST['produkt_id'];
    // Sjekk lagerbeholdning før vi legger til i handlekurv
    $resultat = $db->query("SELECT lagerbeholdning FROM Produkt WHERE ProduktID = $produktId");
    $produkt = $resultat->fetch_assoc();
    
    // Beregn nytt antall i handlekurven
    $antall = isset($_SESSION['handlekurv'][$produktId]) ? $_SESSION['handlekurv'][$produktId] + 1 : 1;
    
    // Sjekk om det er nok på lager
    if ($produkt && $produkt['lagerbeholdning'] >= $antall) {
        $_SESSION['handlekurv'][$produktId] = $antall;
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
    <style>
        .product-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            border-top-left-radius: calc(0.375rem - 1px);
            border-top-right-radius: calc(0.375rem - 1px);
            padding: 10px;
            background-color: #f8f9fa;
        }
        .product-image.no-image {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-image.no-image i {
            font-size: 2.5rem;
            color: #dee2e6;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: grid;
            gap: 0.5rem;
        }
        .price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
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
                            <a class="nav-link" href="alle_bestillinger.php">Bestillinger</a>
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
        $resultat = $db->query("SELECT ProduktID, navn, Pris, info, lagerbeholdning, bilde FROM Produkt");
        
        if ($resultat && $resultat->num_rows > 0): ?>
            <div class="row g-4">
            <?php while ($produkt = $resultat->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <?php if (!empty($produkt['bilde']) && file_exists('bilder/' . $produkt['bilde'])): ?>
                            <img src="bilder/<?= htmlspecialchars($produkt['bilde']) ?>" 
                                 class="product-image" 
                                 alt="<?= htmlspecialchars($produkt['navn']) ?>">
                        <?php else: ?>
                            <div class="product-image no-image">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title mb-3"><?= htmlspecialchars($produkt['navn']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($produkt['info']) ?></p>
                            <p class="price mb-3">kr <?= number_format($produkt['Pris'], 2, ',', ' ') ?></p>
                            <p class="card-text mb-4">
                                <?php if ($produkt['lagerbeholdning'] > 10): ?>
                                    <span class="badge bg-success">På lager</span>
                                <?php elseif ($produkt['lagerbeholdning'] > 0): ?>
                                    <span class="badge bg-warning">Få på lager</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Ikke på lager</span>
                                <?php endif; ?>
                            </p>
                            <div class="action-buttons">
                                <form method="post" class="d-grid">
                                    <input type="hidden" name="produkt_id" value="<?= $produkt['ProduktID'] ?>">
                                    <button type="submit" name="legg_til" class="btn btn-primary">
                                        <i class="bi bi-cart-plus"></i> Legg i handlekurv
                                    </button>
                                </form>
                                <?php if (isset($_SESSION['KundeID'])): ?>
                                    <form method="post" class="d-grid" onsubmit="return confirm('Er du sikker på at du vil slette dette produktet?');">
                                        <input type="hidden" name="produkt_id" value="<?= $produkt['ProduktID'] ?>">
                                        <button type="submit" name="slett" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Slett
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-bag-plus"></i>
                <h3>Start din nettbutikk</h3>
                <p>Din butikk er klar til å fylles med fantastiske produkter. Kom i gang ved å legge til ditt første produkt!</p>
                <a href="legg_til_produkt.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i>Legg til første produkt
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>