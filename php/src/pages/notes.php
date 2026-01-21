<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Notes';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-sticky-note"></i> Notes</h1>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search notes...">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="categoryFilter">
            <option value="">All Categories</option>
            <option value="security">Security</option>
            <option value="ctf">CTF</option>
            <option value="development">Development</option>
            <option value="research">Research</option>
            <option value="other">Other</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addNoteModal">
            <i class="fas fa-plus"></i> Add Note
        </button>
    </div>
</div>

<div class="row" id="notesGrid">
    <div class="col-12 text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<!-- Add/Edit Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="noteForm">
                    <input type="hidden" id="noteId" name="id">
                    
                    <div class="mb-3">
                        <label for="noteTitle" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="noteTitle" name="title" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="noteCategory" class="form-label">Category</label>
                            <select class="form-select" id="noteCategory" name="category">
                                <option value="">Select category</option>
                                <option value="security">Security</option>
                                <option value="ctf">CTF</option>
                                <option value="development">Development</option>
                                <option value="research">Research</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="noteTags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="noteTags" name="tags" placeholder="Comma separated">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="noteRelatedType" class="form-label">Related To</label>
                            <select class="form-select" id="noteRelatedType" name="related_type">
                                <option value="general">General</option>
                                <option value="vulnerability">Vulnerability</option>
                                <option value="ctf">CTF</option>
                                <option value="host">Host</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="noteRelatedId" class="form-label">Related ID</label>
                            <input type="number" class="form-control" id="noteRelatedId" name="related_id" placeholder="Optional">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="noteContent" class="form-label">Content</label>
                        <textarea class="form-control" id="noteContent" name="content" rows="10"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveNote()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
let notes = [];

// Load notes
async function loadNotes() {
    try {
        const category = document.getElementById('categoryFilter').value;
        let url = '/api/notes.php?action=list';
        if (category) url += '&category=' + category;
        
        const result = await apiRequest(url);
        
        if (result.success) {
            notes = result.data;
            renderNotes();
        }
    } catch (error) {
        console.error('Error loading notes:', error);
    }
}

// Render notes as cards
function renderNotes() {
    const grid = document.getElementById('notesGrid');
    
    if (notes.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center text-muted">No notes found</div>';
        return;
    }
    
    grid.innerHTML = notes.map(note => {
        const tags = note.tags ? note.tags.split(',').map(t => `<span class="badge bg-secondary me-1">${t.trim()}</span>`).join('') : '';
        
        return `
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${note.title}</h5>
                        ${note.category ? `<span class="badge bg-info mb-2">${note.category}</span>` : ''}
                        ${tags ? `<div class="mb-2">${tags}</div>` : ''}
                        <p class="card-text" style="max-height: 100px; overflow: hidden;">
                            ${note.content ? note.content.substring(0, 150) + (note.content.length > 150 ? '...' : '') : 'No content'}
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> ${formatDate(note.created_at)}
                        </small>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-info" onclick="editNote(${note.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteNote(${note.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Save note
async function saveNote() {
    const form = document.getElementById('noteForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const id = document.getElementById('noteId').value;
    const action = id ? 'update' : 'create';
    
    showLoading();
    const result = await apiRequest(`/api/notes.php?action=${action}`, 'POST', data);
    hideLoading();
    
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addNoteModal')).hide();
        form.reset();
        loadNotes();
    } else {
        showToast(result.error, 'danger');
    }
}

// Edit note
async function editNote(id) {
    const result = await apiRequest(`/api/notes.php?action=get&id=${id}`);
    
    if (result.success) {
        const note = result.data;
        
        document.getElementById('noteId').value = note.id;
        document.getElementById('noteTitle').value = note.title || '';
        document.getElementById('noteCategory').value = note.category || '';
        document.getElementById('noteTags').value = note.tags || '';
        document.getElementById('noteRelatedType').value = note.related_type || 'general';
        document.getElementById('noteRelatedId').value = note.related_id || '';
        document.getElementById('noteContent').value = note.content || '';
        
        document.querySelector('#addNoteModal .modal-title').textContent = 'Edit Note';
        new bootstrap.Modal(document.getElementById('addNoteModal')).show();
    }
}

// Delete note
async function deleteNote(id) {
    if (await deleteItem(id, 'note', '/api/notes.php')) {
        loadNotes();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadNotes();
    
    // Filter by category
    document.getElementById('categoryFilter').addEventListener('change', loadNotes);
    
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const cards = document.querySelectorAll('#notesGrid .col-md-4');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    
    // Reset form when modal closes
    document.getElementById('addNoteModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('noteForm').reset();
        document.getElementById('noteId').value = '';
        document.querySelector('#addNoteModal .modal-title').textContent = 'Add Note';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
