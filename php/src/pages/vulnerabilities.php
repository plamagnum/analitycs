<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Vulnerabilities';
$pdo = getDbConnection();

// Get hosts for dropdown
$hosts = $pdo->query("SELECT id, ip_address, hostname FROM hosts ORDER BY ip_address")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-bug"></i> Vulnerabilities Management</h1>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search vulnerabilities...">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="severityFilter">
            <option value="">All Severities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
            <option value="info">Info</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addVulnModal">
            <i class="fas fa-plus"></i> Add Vulnerability
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover" id="vulnTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Host</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>CVE</th>
                        <th>Discovered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vulnTableBody">
                    <tr>
                        <td colspan="8" class="text-center">
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

<!-- Add/Edit Vulnerability Modal -->
<div class="modal fade" id="addVulnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vulnerability</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vulnForm">
                    <input type="hidden" id="vulnId" name="id">
                    
                    <div class="mb-3">
                        <label for="vulnTitle" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="vulnTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vulnHost" class="form-label">Host</label>
                        <select class="form-select" id="vulnHost" name="host_id">
                            <option value="">Select host (optional)</option>
                            <?php foreach ($hosts as $host): ?>
                                <option value="<?php echo $host['id']; ?>">
                                    <?php echo h($host['hostname'] ?: $host['ip_address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vulnSeverity" class="form-label">Severity *</label>
                            <select class="form-select" id="vulnSeverity" name="severity" required>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium" selected>Medium</option>
                                <option value="low">Low</option>
                                <option value="info">Info</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="vulnStatus" class="form-label">Status</label>
                            <select class="form-select" id="vulnStatus" name="status">
                                <option value="new" selected>New</option>
                                <option value="in_progress">In Progress</option>
                                <option value="fixed">Fixed</option>
                                <option value="false_positive">False Positive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vulnCVE" class="form-label">CVE ID</label>
                            <input type="text" class="form-control" id="vulnCVE" name="cve_id" placeholder="CVE-2024-XXXX">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="vulnCVSS" class="form-label">CVSS Score</label>
                            <input type="number" class="form-control" id="vulnCVSS" name="cvss_score" min="0" max="10" step="0.1">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vulnDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="vulnDescription" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vulnSolution" class="form-label">Solution</label>
                        <textarea class="form-control" id="vulnSolution" name="solution" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vulnReferences" class="form-label">References</label>
                        <textarea class="form-control" id="vulnReferences" name="references" rows="2" placeholder="One URL per line"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveVulnerability()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
let vulnerabilities = [];

// Load vulnerabilities
async function loadVulnerabilities() {
    try {
        const severity = document.getElementById('severityFilter').value;
        const url = `/api/vulnerabilities.php?action=list${severity ? '&severity=' + severity : ''}`;
        
        const result = await apiRequest(url);
        
        if (result.success) {
            vulnerabilities = result.data;
            renderVulnerabilities();
        }
    } catch (error) {
        console.error('Error loading vulnerabilities:', error);
    }
}

// Render vulnerabilities table
function renderVulnerabilities() {
    const tbody = document.getElementById('vulnTableBody');
    
    if (vulnerabilities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No vulnerabilities found</td></tr>';
        return;
    }
    
    tbody.innerHTML = vulnerabilities.map(vuln => `
        <tr>
            <td>${vuln.id}</td>
            <td>${vuln.title}</td>
            <td>${vuln.hostname || vuln.ip_address || 'N/A'}</td>
            <td><span class="badge bg-${getSeverityClass(vuln.severity)}">${vuln.severity.toUpperCase()}</span></td>
            <td><span class="badge bg-${getStatusClass(vuln.status)}">${vuln.status.replace('_', ' ').toUpperCase()}</span></td>
            <td>${vuln.cve_id || '-'}</td>
            <td>${formatDate(vuln.discovered_at)}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="editVulnerability(${vuln.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteVulnerability(${vuln.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

// Save vulnerability
async function saveVulnerability() {
    const form = document.getElementById('vulnForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const id = document.getElementById('vulnId').value;
    const action = id ? 'update' : 'create';
    
    showLoading();
    const result = await apiRequest(`/api/vulnerabilities.php?action=${action}`, 'POST', data);
    hideLoading();
    
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addVulnModal')).hide();
        form.reset();
        loadVulnerabilities();
    } else {
        showToast(result.error, 'danger');
    }
}

// Edit vulnerability
async function editVulnerability(id) {
    const result = await apiRequest(`/api/vulnerabilities.php?action=get&id=${id}`);
    
    if (result.success) {
        const vuln = result.data;
        
        document.getElementById('vulnId').value = vuln.id;
        document.getElementById('vulnTitle').value = vuln.title || '';
        document.getElementById('vulnHost').value = vuln.host_id || '';
        document.getElementById('vulnSeverity').value = vuln.severity || 'medium';
        document.getElementById('vulnStatus').value = vuln.status || 'new';
        document.getElementById('vulnCVE').value = vuln.cve_id || '';
        document.getElementById('vulnCVSS').value = vuln.cvss_score || '';
        document.getElementById('vulnDescription').value = vuln.description || '';
        document.getElementById('vulnSolution').value = vuln.solution || '';
        document.getElementById('vulnReferences').value = vuln.references || '';
        
        document.querySelector('#addVulnModal .modal-title').textContent = 'Edit Vulnerability';
        new bootstrap.Modal(document.getElementById('addVulnModal')).show();
    }
}

// Delete vulnerability
async function deleteVulnerability(id) {
    if (await deleteItem(id, 'vulnerability', '/api/vulnerabilities.php')) {
        loadVulnerabilities();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadVulnerabilities();
    
    // Filter by severity
    document.getElementById('severityFilter').addEventListener('change', loadVulnerabilities);
    
    // Search functionality
    filterTable('searchInput', 'vulnTable');
    
    // Reset form when modal closes
    document.getElementById('addVulnModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('vulnForm').reset();
        document.getElementById('vulnId').value = '';
        document.querySelector('#addVulnModal .modal-title').textContent = 'Add Vulnerability';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
