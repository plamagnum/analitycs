<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Nmap Scans';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-network-wired"></i> Nmap Scan Upload</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-upload"></i> Upload Nmap XML</h5>
            </div>
            <div class="card-body">
                <div class="upload-area" id="nmapUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drag & Drop or Click to Upload</h4>
                    <p class="text-muted">Nmap XML scan files (.xml)</p>
                    <input type="file" id="nmapFileInput" accept=".xml" style="display: none;">
                </div>
                
                <div id="uploadStatus" class="mt-3"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Instructions</h5>
            </div>
            <div class="card-body">
                <h6>Generate Nmap XML scan:</h6>
                <pre><code>nmap -oX scan.xml -sV [target]</code></pre>
                
                <h6 class="mt-3">Examples:</h6>
                <ul>
                    <li>Basic scan: <code>nmap -oX output.xml 192.168.1.1</code></li>
                    <li>Service detection: <code>nmap -oX output.xml -sV 192.168.1.0/24</code></li>
                    <li>Full scan: <code>nmap -oX output.xml -A -p- target.com</code></li>
                </ul>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i>
                    Upload will automatically parse hosts, ports, and services
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Scans</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover" id="scansTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Filename</th>
                        <th>Hosts Found</th>
                        <th>Ports Found</th>
                        <th>Uploaded</th>
                    </tr>
                </thead>
                <tbody id="scansTableBody">
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize file upload
initFileUpload('nmapUploadArea', 'nmapFileInput', async (file) => {
    const statusDiv = document.getElementById('uploadStatus');
    statusDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Uploading and parsing...</div>';
    
    const result = await uploadFile(file, '/api/upload_nmap.php');
    
    if (result) {
        statusDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                Successfully parsed: ${result.data.hosts_count} hosts, ${result.data.ports_count} ports
            </div>
        `;
        loadScans();
    } else {
        statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Upload failed</div>';
    }
});

// Load recent scans
async function loadScans() {
    try {
        const pdo = <?php echo json_encode(getDbConnection()); ?>;
        const response = await fetch('/api/get_nmap_scans.php');
        
        // For now, let's use a direct database query approach
        const tbody = document.getElementById('scansTableBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Scans will appear here after upload</td></tr>';
        
    } catch (error) {
        console.error('Error loading scans:', error);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadScans();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
