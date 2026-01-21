// Security Analytics Center - Main JavaScript

// Show loading spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.id = 'loadingSpinner';
    spinner.innerHTML = '<div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(spinner);
}

// Hide loading spinner
function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.remove();
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// AJAX helper
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {}
    };
    
    if (data) {
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

// File upload with drag and drop
function initFileUpload(uploadAreaId, fileInputId, onFileSelect) {
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(fileInputId);
    
    if (!uploadArea || !fileInput) return;
    
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            onFileSelect(e.target.files[0]);
        }
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length > 0) {
            onFileSelect(e.dataTransfer.files[0]);
        }
    });
}

// Upload file to API
async function uploadFile(file, endpoint) {
    const formData = new FormData();
    formData.append('file', file);
    
    showLoading();
    try {
        const result = await apiRequest(endpoint, 'POST', formData);
        hideLoading();
        
        if (result.success) {
            showToast(result.message || 'File uploaded successfully', 'success');
            return result;
        } else {
            showToast(result.error || 'Upload failed', 'danger');
            return null;
        }
    } catch (error) {
        hideLoading();
        showToast('Upload error: ' + error.message, 'danger');
        return null;
    }
}

// Delete item with confirmation
async function deleteItem(id, type, endpoint) {
    if (!confirm(`Are you sure you want to delete this ${type}?`)) {
        return false;
    }
    
    showLoading();
    try {
        const result = await apiRequest(`${endpoint}?action=delete&id=${id}`, 'DELETE');
        hideLoading();
        
        if (result.success) {
            showToast(result.message || 'Deleted successfully', 'success');
            return true;
        } else {
            showToast(result.error || 'Delete failed', 'danger');
            return false;
        }
    } catch (error) {
        hideLoading();
        showToast('Delete error: ' + error.message, 'danger');
        return false;
    }
}

// Filter table
function filterTable(searchInputId, tableId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    
    return true;
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('uk-UA');
}

// Severity badge helper
function getSeverityBadge(severity) {
    const classes = {
        'critical': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'secondary',
        'info': 'light'
    };
    
    const className = classes[severity] || 'secondary';
    return `<span class="badge bg-${className}">${severity.toUpperCase()}</span>`;
}

// Status badge helper
function getStatusBadge(status) {
    const classes = {
        'new': 'danger',
        'in_progress': 'warning',
        'fixed': 'success',
        'false_positive': 'secondary',
        'solved': 'success',
        'unsolved': 'danger',
        'up': 'success',
        'down': 'danger'
    };
    
    const className = classes[status] || 'secondary';
    const displayStatus = status.replace('_', ' ').toUpperCase();
    return `<span class="badge bg-${className}">${displayStatus}</span>`;
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        let cols = row.querySelectorAll('td, th');
        let csvRow = [];
        
        for (let col of cols) {
            csvRow.push('"' + col.textContent.replace(/"/g, '""') + '"');
        }
        
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
