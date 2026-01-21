<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', 405);
}

// Validate file upload
$validation = validateFileUpload($_FILES['file'] ?? null, ['json', 'txt']);
if (!$validation['valid']) {
    errorResponse($validation['error']);
}

$file = $_FILES['file'];
$uploadDir = '/var/www/uploads/nuclei/';

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$filename = uniqid('nuclei_') . '_' . basename($file['name']);
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    errorResponse('Failed to save file');
}

// Parse Nuclei file
$parseResult = parseNucleiFile($filepath);

if (!$parseResult['success']) {
    unlink($filepath);
    errorResponse($parseResult['error']);
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();
    
    $vulnCount = 0;
    $scanTarget = '';
    
    // Process each vulnerability
    foreach ($parseResult['vulnerabilities'] as $vulnData) {
        // Get or create host
        $hostId = null;
        if (!empty($vulnData['host'])) {
            $scanTarget = $vulnData['host'];
            
            // Extract IP or hostname from URL
            $parsed = parse_url($vulnData['host']);
            $hostIdentifier = $parsed['host'] ?? $vulnData['host'];
            
            // Try to find existing host or create new
            $stmt = $pdo->prepare("
                INSERT INTO hosts (ip_address, hostname, status) 
                VALUES (?, ?, 'unknown')
                ON DUPLICATE KEY UPDATE last_seen = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$hostIdentifier, $hostIdentifier]);
            
            $hostId = $pdo->lastInsertId();
            if ($hostId == 0) {
                $stmt = $pdo->prepare("SELECT id FROM hosts WHERE ip_address = ? OR hostname = ?");
                $stmt->execute([$hostIdentifier, $hostIdentifier]);
                $hostId = $stmt->fetchColumn();
            }
        }
        
        // Insert vulnerability
        $stmt = $pdo->prepare("
            INSERT INTO vulnerabilities 
            (host_id, title, description, severity, cve_id, cvss_score, solution, `references`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $description = $vulnData['description'];
        if (!empty($vulnData['matched_at'])) {
            $description .= "\n\nMatched at: " . $vulnData['matched_at'];
        }
        if (!empty($vulnData['template_id'])) {
            $description .= "\nTemplate ID: " . $vulnData['template_id'];
        }
        
        $stmt->execute([
            $hostId,
            $vulnData['title'],
            $description,
            $vulnData['severity'],
            $vulnData['cve_id'],
            $vulnData['cvss_score'],
            null,
            $vulnData['references']
        ]);
        
        $vulnCount++;
    }
    
    // Save scan record
    $stmt = $pdo->prepare("
        INSERT INTO nuclei_scans (filename, scan_target, vulnerabilities_found) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $filename,
        $scanTarget,
        $vulnCount
    ]);
    
    $pdo->commit();
    
    successResponse([
        'vulnerabilities_count' => $vulnCount
    ], "Nuclei scan parsed successfully. Found $vulnCount vulnerabilities.");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    unlink($filepath);
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
