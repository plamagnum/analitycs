// CTF Management JavaScript

let competitions = [];
let challenges = [];

// Load competitions
async function loadCompetitions() {
    try {
        const result = await apiRequest('/api/ctf.php?action=list');
        
        if (result.success) {
            competitions = result.data;
            renderCompetitions();
        }
    } catch (error) {
        console.error('Error loading competitions:', error);
    }
}

// Render competitions table
function renderCompetitions() {
    const tbody = document.getElementById('compTableBody');
    
    if (competitions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No competitions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = competitions.map(comp => `
        <tr>
            <td>${comp.id}</td>
            <td>${comp.name}</td>
            <td>${comp.platform || '-'}</td>
            <td>${comp.start_date ? formatDate(comp.start_date) : '-'}</td>
            <td>${comp.team_name || '-'}</td>
            <td>${comp.final_rank || '-'}</td>
            <td>${comp.total_points}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="editCompetition(${comp.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteCompetition(${comp.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

// Save competition
async function saveCompetition() {
    const form = document.getElementById('compForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const id = document.getElementById('compId').value;
    const action = id ? 'update_competition' : 'create_competition';
    
    showLoading();
    const result = await apiRequest(`/api/ctf.php?action=${action}`, 'POST', data);
    hideLoading();
    
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addCompModal')).hide();
        form.reset();
        loadCompetitions();
    } else {
        showToast(result.error, 'danger');
    }
}

// Edit competition
async function editCompetition(id) {
    const result = await apiRequest(`/api/ctf.php?action=get&id=${id}`);
    
    if (result.success) {
        const comp = result.data;
        
        document.getElementById('compId').value = comp.id;
        document.getElementById('compName').value = comp.name || '';
        document.getElementById('compURL').value = comp.url || '';
        document.getElementById('compStartDate').value = comp.start_date ? comp.start_date.replace(' ', 'T') : '';
        document.getElementById('compEndDate').value = comp.end_date ? comp.end_date.replace(' ', 'T') : '';
        document.getElementById('compPlatform').value = comp.platform || '';
        document.getElementById('compTeam').value = comp.team_name || '';
        document.getElementById('compRank').value = comp.final_rank || '';
        document.getElementById('compPoints').value = comp.total_points || 0;
        document.getElementById('compNotes').value = comp.notes || '';
        
        document.querySelector('#addCompModal .modal-title').textContent = 'Edit Competition';
        new bootstrap.Modal(document.getElementById('addCompModal')).show();
    }
}

// Delete competition
async function deleteCompetition(id) {
    if (await deleteItem(id, 'competition', '/api/ctf.php?action=delete_competition')) {
        loadCompetitions();
    }
}

// Load challenges
async function loadChallenges() {
    try {
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        let url = '/api/ctf.php?action=list_challenges';
        if (category) url += '&category=' + category;
        if (status) url += '&status=' + status;
        
        const result = await apiRequest(url);
        
        if (result.success) {
            challenges = result.data;
            renderChallenges();
        }
    } catch (error) {
        console.error('Error loading challenges:', error);
    }
}

// Render challenges table
function renderChallenges() {
    const tbody = document.getElementById('challengesTableBody');
    
    if (challenges.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No challenges found</td></tr>';
        return;
    }
    
    tbody.innerHTML = challenges.map(ch => `
        <tr>
            <td>${ch.id}</td>
            <td>${ch.name}</td>
            <td>${ch.competition_name || 'Standalone'}</td>
            <td><span class="badge bg-secondary">${ch.category.toUpperCase()}</span></td>
            <td>${ch.points}</td>
            <td><span class="badge bg-${getStatusClass(ch.status)}">${ch.status.replace('_', ' ').toUpperCase()}</span></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="editChallenge(${ch.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteChallenge(${ch.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

// Save challenge
async function saveChallenge() {
    const form = document.getElementById('challengeForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Convert empty competition_id to null
    if (!data.competition_id) {
        data.competition_id = null;
    }
    
    const id = document.getElementById('challengeId').value;
    const action = id ? 'update_challenge' : 'create_challenge';
    
    showLoading();
    const result = await apiRequest(`/api/ctf.php?action=${action}`, 'POST', data);
    hideLoading();
    
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('addChallengeModal')).hide();
        form.reset();
        loadChallenges();
    } else {
        showToast(result.error, 'danger');
    }
}

// Edit challenge
async function editChallenge(id) {
    // Get challenge from current list
    const challenge = challenges.find(ch => ch.id == id);
    
    if (challenge) {
        document.getElementById('challengeId').value = challenge.id;
        document.getElementById('challengeName').value = challenge.name || '';
        document.getElementById('challengeComp').value = challenge.competition_id || '';
        document.getElementById('challengeCategory').value = challenge.category || 'web';
        document.getElementById('challengePoints').value = challenge.points || 0;
        document.getElementById('challengeStatus').value = challenge.status || 'unsolved';
        document.getElementById('challengeDescription').value = challenge.description || '';
        document.getElementById('challengeFlag').value = challenge.flag || '';
        document.getElementById('challengeWriteup').value = challenge.writeup || '';
        
        document.querySelector('#addChallengeModal .modal-title').textContent = 'Edit Challenge';
        new bootstrap.Modal(document.getElementById('addChallengeModal')).show();
    }
}

// Delete challenge
async function deleteChallenge(id) {
    if (await deleteItem(id, 'challenge', '/api/ctf.php?action=delete_challenge')) {
        loadChallenges();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadCompetitions();
    loadChallenges();
    
    // Filter handlers
    document.getElementById('categoryFilter').addEventListener('change', loadChallenges);
    document.getElementById('statusFilter').addEventListener('change', loadChallenges);
    
    // Search functionality
    filterTable('searchCompetitions', 'compTable');
    filterTable('searchChallenges', 'challengesTable');
    
    // Reset forms when modals close
    document.getElementById('addCompModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('compForm').reset();
        document.getElementById('compId').value = '';
        document.querySelector('#addCompModal .modal-title').textContent = 'Add Competition';
    });
    
    document.getElementById('addChallengeModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('challengeForm').reset();
        document.getElementById('challengeId').value = '';
        document.querySelector('#addChallengeModal .modal-title').textContent = 'Add Challenge';
    });
});
