<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$pdo = getDbConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $type = $_GET['type'] ?? null;
            
            $sql = "SELECT * FROM ctf_competitions";
            $params = [];
            
            $sql .= " ORDER BY start_date DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $competitions = $stmt->fetchAll();
            
            successResponse($competitions);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM ctf_competitions WHERE id = ?");
            $stmt->execute([$id]);
            $competition = $stmt->fetch();
            
            if (!$competition) {
                errorResponse('Competition not found', 404);
            }
            
            // Get challenges for this competition
            $stmt = $pdo->prepare("SELECT * FROM ctf_challenges WHERE competition_id = ? ORDER BY created_at DESC");
            $stmt->execute([$id]);
            $challenges = $stmt->fetchAll();
            
            $competition['challenges'] = $challenges;
            
            successResponse($competition);
            break;
            
        case 'list_challenges':
            $competitionId = $_GET['competition_id'] ?? null;
            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT c.*, comp.name as competition_name 
                    FROM ctf_challenges c 
                    LEFT JOIN ctf_competitions comp ON c.competition_id = comp.id 
                    WHERE 1=1";
            $params = [];
            
            if ($competitionId) {
                $sql .= " AND c.competition_id = ?";
                $params[] = $competitionId;
            }
            
            if ($category) {
                $sql .= " AND c.category = ?";
                $params[] = $category;
            }
            
            if ($status) {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $challenges = $stmt->fetchAll();
            
            successResponse($challenges);
            break;
            
        case 'create_competition':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name'])) {
                errorResponse('Name is required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO ctf_competitions 
                (name, url, start_date, end_date, platform, team_name, final_rank, total_points, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['url'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['platform'] ?? null,
                $data['team_name'] ?? null,
                $data['final_rank'] ?? null,
                $data['total_points'] ?? 0,
                $data['notes'] ?? null
            ]);
            
            $id = $pdo->lastInsertId();
            
            successResponse(['id' => $id], 'Competition created successfully');
            break;
            
        case 'create_challenge':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['category'])) {
                errorResponse('Name and category are required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO ctf_challenges 
                (competition_id, name, category, points, status, description, flag, writeup, solution_files, solved_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['competition_id'] ?? null,
                $data['name'],
                $data['category'],
                $data['points'] ?? 0,
                $data['status'] ?? 'unsolved',
                $data['description'] ?? null,
                $data['flag'] ?? null,
                $data['writeup'] ?? null,
                $data['solution_files'] ?? null,
                $data['solved_at'] ?? null
            ]);
            
            $id = $pdo->lastInsertId();
            
            successResponse(['id' => $id], 'Challenge created successfully');
            break;
            
        case 'update_competition':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
                errorResponse('Invalid request method', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                errorResponse('ID required');
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['name', 'url', 'start_date', 'end_date', 'platform', 'team_name', 'final_rank', 'total_points', 'notes'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                errorResponse('No fields to update');
            }
            
            $params[] = $id;
            $sql = "UPDATE ctf_competitions SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            successResponse([], 'Competition updated successfully');
            break;
            
        case 'update_challenge':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
                errorResponse('Invalid request method', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                errorResponse('ID required');
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['competition_id', 'name', 'category', 'points', 'status', 'description', 'flag', 'writeup', 'solution_files', 'solved_at'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                errorResponse('No fields to update');
            }
            
            $params[] = $id;
            $sql = "UPDATE ctf_challenges SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            successResponse([], 'Challenge updated successfully');
            break;
            
        case 'delete_competition':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM ctf_competitions WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                errorResponse('Competition not found', 404);
            }
            
            successResponse([], 'Competition deleted successfully');
            break;
            
        case 'delete_challenge':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM ctf_challenges WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                errorResponse('Challenge not found', 404);
            }
            
            successResponse([], 'Challenge deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
    
} catch (PDOException $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
