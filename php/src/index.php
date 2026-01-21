<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';
$pdo = getDbConnection();

// Get statistics
try {
    // Vulnerability statistics
    $vulnStats = $pdo->query("
        SELECT severity, COUNT(*) as count 
        FROM vulnerabilities 
        GROUP BY severity
    ")->fetchAll();
    
    $vulnByStatus = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM vulnerabilities 
        GROUP BY status
    ")->fetchAll();
    
    $totalVulns = $pdo->query("SELECT COUNT(*) as count FROM vulnerabilities")->fetch()['count'];
    $totalHosts = $pdo->query("SELECT COUNT(*) as count FROM hosts")->fetch()['count'];
    $totalPorts = $pdo->query("SELECT COUNT(*) as count FROM ports WHERE state = 'open'")->fetch()['count'];
    
    // CTF statistics
    $totalCTFs = $pdo->query("SELECT COUNT(*) as count FROM ctf_competitions")->fetch()['count'];
    $solvedChallenges = $pdo->query("SELECT COUNT(*) as count FROM ctf_challenges WHERE status = 'solved'")->fetch()['count'];
    $totalChallenges = $pdo->query("SELECT COUNT(*) as count FROM ctf_challenges")->fetch()['count'];
    
    // Recent activities
    $recentVulns = $pdo->query("
        SELECT v.*, h.ip_address, h.hostname 
        FROM vulnerabilities v 
        LEFT JOIN hosts h ON v.host_id = h.id 
        ORDER BY v.discovered_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    $recentScans = $pdo->query("
        SELECT 'nmap' as type, filename, uploaded_at, hosts_discovered as count 
        FROM nmap_scans 
        UNION ALL 
        SELECT 'nuclei' as type, filename, uploaded_at, vulnerabilities_found as count 
        FROM nuclei_scans 
        ORDER BY uploaded_at DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch (PDOException $e) {
    die("Error fetching statistics: " . $e->getMessage());
}

// Prepare chart data
$severityLabels = [];
$severityCounts = [];
foreach ($vulnStats as $stat) {
    $severityLabels[] = ucfirst($stat['severity']);
    $severityCounts[] = $stat['count'];
}

$statusLabels = [];
$statusCounts = [];
foreach ($vulnByStatus as $stat) {
    $statusLabels[] = str_replace('_', ' ', ucfirst($stat['status']));
    $statusCounts[] = $stat['count'];
}

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Vulnerabilities</h6>
                        <h2 class="mb-0"><?php echo $totalVulns; ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-bug"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Discovered Hosts</h6>
                        <h2 class="mb-0"><?php echo $totalHosts; ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-server"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Open Ports</h6>
                        <h2 class="mb-0"><?php echo $totalPorts; ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-network-wired"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">CTF Challenges</h6>
                        <h2 class="mb-0"><?php echo $solvedChallenges; ?>/<?php echo $totalChallenges; ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-flag"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Vulnerabilities by Severity</h5>
            </div>
            <div class="card-body">
                <canvas id="severityChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Vulnerabilities by Status</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Vulnerabilities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentVulns)): ?>
                    <p class="text-muted">No vulnerabilities found</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentVulns as $vuln): ?>
                            <a href="/pages/vulnerabilities.php" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo h($vuln['title']); ?></h6>
                                    <span class="badge bg-<?php echo getSeverityClass($vuln['severity']); ?>">
                                        <?php echo ucfirst($vuln['severity']); ?>
                                    </span>
                                </div>
                                <p class="mb-1">
                                    <small>
                                        <i class="fas fa-server"></i> 
                                        <?php echo h($vuln['hostname'] ?: $vuln['ip_address'] ?: 'N/A'); ?>
                                    </small>
                                </p>
                                <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($vuln['discovered_at'])); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-upload"></i> Recent Scans</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentScans)): ?>
                    <p class="text-muted">No scans uploaded yet</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentScans as $scan): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="fas fa-<?php echo $scan['type'] === 'nmap' ? 'network-wired' : 'satellite-dish'; ?>"></i>
                                        <?php echo h($scan['filename']); ?>
                                    </h6>
                                    <span class="badge bg-info"><?php echo ucfirst($scan['type']); ?></span>
                                </div>
                                <p class="mb-1">
                                    <small>
                                        Found: <strong><?php echo $scan['count']; ?></strong> 
                                        <?php echo $scan['type'] === 'nmap' ? 'hosts' : 'vulnerabilities'; ?>
                                    </small>
                                </p>
                                <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($scan['uploaded_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Chart data from PHP
const severityData = {
    labels: <?php echo json_encode($severityLabels); ?>,
    datasets: [{
        data: <?php echo json_encode($severityCounts); ?>,
        backgroundColor: [
            'rgba(220, 53, 69, 0.8)',   // critical - red
            'rgba(255, 193, 7, 0.8)',    // high - yellow
            'rgba(13, 110, 253, 0.8)',   // medium - blue
            'rgba(108, 117, 125, 0.8)',  // low - gray
            'rgba(248, 249, 250, 0.8)'   // info - light
        ],
        borderColor: [
            'rgba(220, 53, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(13, 110, 253, 1)',
            'rgba(108, 117, 125, 1)',
            'rgba(248, 249, 250, 1)'
        ],
        borderWidth: 2
    }]
};

const statusData = {
    labels: <?php echo json_encode($statusLabels); ?>,
    datasets: [{
        data: <?php echo json_encode($statusCounts); ?>,
        backgroundColor: [
            'rgba(220, 53, 69, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(25, 135, 84, 0.8)',
            'rgba(108, 117, 125, 0.8)'
        ],
        borderColor: [
            'rgba(220, 53, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(25, 135, 84, 1)',
            'rgba(108, 117, 125, 1)'
        ],
        borderWidth: 2
    }]
};

// Create charts
if (document.getElementById('severityChart')) {
    new Chart(document.getElementById('severityChart'), {
        type: 'pie',
        data: severityData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

if (document.getElementById('statusChart')) {
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: statusData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
