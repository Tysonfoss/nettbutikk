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

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $epost = $_POST['epost'];
    $passord = $_POST['passord'];
    
    $sql = "SELECT KundeID, Navn, etternavn FROM kunde WHERE Epost = ? AND Passord = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $epost, $passord);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $kunde = $result->fetch_assoc();
        $_SESSION['KundeID'] = $kunde['KundeID'];
        $_SESSION['Navn'] = $kunde['Navn'];
        $_SESSION['Etternavn'] = $kunde['etternavn'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Feil e-post eller passord";
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn</title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="logginn.php">Logg inn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registrer.php">Registrer</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Logg inn</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="epost" class="form-label">E-post</label>
                                <input type="email" class="form-control" id="epost" name="epost" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="passord" class="form-label">Passord</label>
                                <input type="password" class="form-control" id="passord" name="passord" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Logg inn</button>
                                <a href="registrer.php" class="btn btn-secondary">Registrer ny bruker</a>
                                <a href="index.php" class="btn btn-link">Tilbake til forsiden</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>