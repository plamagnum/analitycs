<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Nuclei Scans';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-satellite-dish"></i> Nuclei Scan Upload</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-upload"></i> Upload Nuclei Results</h5>
            </div>
            <div class="card-body">
                <div class="upload-area" id="nucleiUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drag & Drop or Click to Upload</h4>
                    <p class="text-muted">Nuclei JSON or TXT files (.json, .txt)</p>
                    <input type="file" id="nucleiFileInput" accept=".json,.txt" style="display: none;">
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
                <h6>Generate Nuclei scan results:</h6>
                <pre><code># JSON format (recommended)
nuclei -u https://example.com -jsonl -o output.json

# Text format
nuclei -u https://example.com -o output.txt</code></pre>
                
                <h6 class="mt-3">Examples:</h6>
                <ul>
                    <li>Single target: <code>nuclei -u https://target.com -jsonl -o scan.json</code></li>
                    <li>Multiple targets: <code>nuclei -l urls.txt -jsonl -o results.json</code></li>
                    <li>Specific severity: <code>nuclei -u target.com -s critical,high -jsonl -o critical.json</code></li>
                </ul>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i>
                    Upload will automatically create vulnerability records
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
                        <th>Target</th>
                        <th>Vulnerabilities Found</th>
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
initFileUpload('nucleiUploadArea', 'nucleiFileInput', async (file) => {
    const statusDiv = document.getElementById('uploadStatus');
    statusDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Uploading and parsing...</div>';
    
    const result = await uploadFile(file, '/api/upload_nuclei.php');
    
    if (result) {
        statusDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                Successfully imported: ${result.data.vulnerabilities_count} vulnerabilities
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
