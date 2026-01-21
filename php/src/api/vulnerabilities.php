<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$pdo = getDbConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $severity = $_GET['severity'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT v.*, h.ip_address, h.hostname 
                    FROM vulnerabilities v 
                    LEFT JOIN hosts h ON v.host_id = h.id 
                    WHERE 1=1";
            $params = [];
            
            if ($severity) {
                $sql .= " AND v.severity = ?";
                $params[] = $severity;
            }
            
            if ($status) {
                $sql .= " AND v.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY v.discovered_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $vulnerabilities = $stmt->fetchAll();
            
            successResponse($vulnerabilities);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("
                SELECT v.*, h.ip_address, h.hostname 
                FROM vulnerabilities v 
                LEFT JOIN hosts h ON v.host_id = h.id 
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            $vulnerability = $stmt->fetch();
            
            if (!$vulnerability) {
                errorResponse('Vulnerability not found', 404);
            }
            
            successResponse($vulnerability);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['title']) || empty($data['severity'])) {
                errorResponse('Title and severity are required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO vulnerabilities 
                (host_id, title, description, severity, status, cve_id, cvss_score, solution, `references`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['host_id'] ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['severity'],
                $data['status'] ?? 'new',
                $data['cve_id'] ?? null,
                $data['cvss_score'] ?? null,
                $data['solution'] ?? null,
                $data['references'] ?? null
            ]);
            
            $id = $pdo->lastInsertId();
            
            successResponse(['id' => $id], 'Vulnerability created successfully');
            break;
            
        case 'update':
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
            
            $allowedFields = ['host_id', 'title', 'description', 'severity', 'status', 'cve_id', 'cvss_score', 'solution', 'references'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $column = $field === 'references' ? '`references`' : $field;
                    $updates[] = "$column = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                errorResponse('No fields to update');
            }
            
            $params[] = $id;
            $sql = "UPDATE vulnerabilities SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            successResponse([], 'Vulnerability updated successfully');
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM vulnerabilities WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                errorResponse('Vulnerability not found', 404);
            }
            
            successResponse([], 'Vulnerability deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
    
} catch (PDOException $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
