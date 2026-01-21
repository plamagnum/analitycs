<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>Security Analytics Center</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                <i class="fas fa-shield-alt"></i> Security Analytics
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_SERVER['PHP_SELF'] === '/index.php' || $_SERVER['PHP_SELF'] === '/') ? 'active' : ''; ?>" href="/">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'vulnerabilities') !== false) ? 'active' : ''; ?>" href="/pages/vulnerabilities.php">
                            <i class="fas fa-bug"></i> Vulnerabilities
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'nmap') !== false) ? 'active' : ''; ?>" href="/pages/nmap.php">
                            <i class="fas fa-network-wired"></i> Nmap
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'nuclei') !== false) ? 'active' : ''; ?>" href="/pages/nuclei.php">
                            <i class="fas fa-satellite-dish"></i> Nuclei
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'ctf') !== false) ? 'active' : ''; ?>" href="/pages/ctf.php">
                            <i class="fas fa-flag"></i> CTF
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'notes') !== false) ? 'active' : ''; ?>" href="/pages/notes.php">
                            <i class="fas fa-sticky-note"></i> Notes
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
