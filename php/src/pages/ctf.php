<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'CTF Management';
$pdo = getDbConnection();

// Get competitions for dropdown
$competitions = $pdo->query("SELECT id, name FROM ctf_competitions ORDER BY start_date DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-flag"></i> CTF Management</h1>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="ctfTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="competitions-tab" data-bs-toggle="tab" data-bs-target="#competitions" type="button">
            <i class="fas fa-trophy"></i> Competitions
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="challenges-tab" data-bs-toggle="tab" data-bs-target="#challenges" type="button">
            <i class="fas fa-puzzle-piece"></i> Challenges
        </button>
    </li>
</ul>

<div class="tab-content" id="ctfTabContent">
    <!-- Competitions Tab -->
    <div class="tab-pane fade show active" id="competitions" role="tabpanel">
        <div class="row mb-3">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchCompetitions" placeholder="Search competitions...">
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addCompModal">
                    <i class="fas fa-plus"></i> Add Competition
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover" id="compTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Platform</th>
                                <th>Start Date</th>
                                <th>Team</th>
                                <th>Rank</th>
                                <th>Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="compTableBody">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Challenges Tab -->
    <div class="tab-pane fade" id="challenges" role="tabpanel">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchChallenges" placeholder="Search challenges...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="web">Web</option>
                    <option value="pwn">Pwn</option>
                    <option value="crypto">Crypto</option>
                    <option value="forensics">Forensics</option>
                    <option value="misc">Misc</option>
                    <option value="reverse">Reverse</option>
                    <option value="osint">OSINT</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="solved">Solved</option>
                    <option value="unsolved">Unsolved</option>
                    <option value="in_progress">In Progress</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addChallengeModal">
                    <i class="fas fa-plus"></i> Add Challenge
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover" id="challengesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Competition</th>
                                <th>Category</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="challengesTableBody">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Competition Modal -->
<div class="modal fade" id="addCompModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Competition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="compForm">
                    <input type="hidden" id="compId" name="id">
                    
                    <div class="mb-3">
                        <label for="compName" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="compName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="compURL" class="form-label">URL</label>
                        <input type="url" class="form-control" id="compURL" name="url" placeholder="https://">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="compStartDate" class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" id="compStartDate" name="start_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="compEndDate" class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" id="compEndDate" name="end_date">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="compPlatform" class="form-label">Platform</label>
                            <input type="text" class="form-control" id="compPlatform" name="platform" placeholder="CTFd, HackTheBox, etc.">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="compTeam" class="form-label">Team Name</label>
                            <input type="text" class="form-control" id="compTeam" name="team_name">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="compRank" class="form-label">Final Rank</label>
                            <input type="number" class="form-control" id="compRank" name="final_rank" min="1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="compPoints" class="form-label">Points</label>
                            <input type="number" class="form-control" id="compPoints" name="total_points" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="compNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="compNotes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCompetition()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Challenge Modal -->
<div class="modal fade" id="addChallengeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Challenge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="challengeForm">
                    <input type="hidden" id="challengeId" name="id">
                    
                    <div class="mb-3">
                        <label for="challengeName" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="challengeName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="challengeComp" class="form-label">Competition</label>
                        <select class="form-select" id="challengeComp" name="competition_id">
                            <option value="">Standalone challenge</option>
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>"><?php echo h($comp['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="challengeCategory" class="form-label">Category *</label>
                            <select class="form-select" id="challengeCategory" name="category" required>
                                <option value="web">Web</option>
                                <option value="pwn">Pwn</option>
                                <option value="crypto">Crypto</option>
                                <option value="forensics">Forensics</option>
                                <option value="misc">Misc</option>
                                <option value="reverse">Reverse</option>
                                <option value="osint">OSINT</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="challengePoints" class="form-label">Points</label>
                            <input type="number" class="form-control" id="challengePoints" name="points" min="0" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="challengeStatus" class="form-label">Status</label>
                            <select class="form-select" id="challengeStatus" name="status">
                                <option value="unsolved">Unsolved</option>
                                <option value="in_progress">In Progress</option>
                                <option value="solved">Solved</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="challengeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="challengeDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="challengeFlag" class="form-label">Flag</label>
                        <input type="text" class="form-control" id="challengeFlag" name="flag" placeholder="flag{...}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="challengeWriteup" class="form-label">Writeup/Solution</label>
                        <textarea class="form-control" id="challengeWriteup" name="writeup" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveChallenge()">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/ctf.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
