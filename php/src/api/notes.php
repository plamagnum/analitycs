<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$pdo = getDbConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $category = $_GET['category'] ?? null;
            $relatedType = $_GET['related_type'] ?? null;
            
            $sql = "SELECT * FROM notes WHERE 1=1";
            $params = [];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            if ($relatedType) {
                $sql .= " AND related_type = ?";
                $params[] = $relatedType;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $notes = $stmt->fetchAll();
            
            successResponse($notes);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            $note = $stmt->fetch();
            
            if (!$note) {
                errorResponse('Note not found', 404);
            }
            
            successResponse($note);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['title'])) {
                errorResponse('Title is required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO notes 
                (title, content, category, tags, related_type, related_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $data['content'] ?? null,
                $data['category'] ?? null,
                $data['tags'] ?? null,
                $data['related_type'] ?? 'general',
                $data['related_id'] ?? null
            ]);
            
            $id = $pdo->lastInsertId();
            
            successResponse(['id' => $id], 'Note created successfully');
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
            
            $allowedFields = ['title', 'content', 'category', 'tags', 'related_type', 'related_id'];
            
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
            $sql = "UPDATE notes SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            successResponse([], 'Note updated successfully');
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('Invalid request method', 405);
            }
            
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                errorResponse('ID required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                errorResponse('Note not found', 404);
            }
            
            successResponse([], 'Note deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
    
} catch (PDOException $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
