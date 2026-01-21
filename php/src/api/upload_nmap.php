<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', 405);
}

// Validate file upload
$validation = validateFileUpload($_FILES['file'] ?? null, ['xml']);
if (!$validation['valid']) {
    errorResponse($validation['error']);
}

$file = $_FILES['file'];
$uploadDir = '/var/www/uploads/nmap/';

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$filename = uniqid('nmap_') . '_' . basename($file['name']);
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    errorResponse('Failed to save file');
}

// Parse Nmap XML
$parseResult = parseNmapXML($filepath);

if (!$parseResult['success']) {
    unlink($filepath);
    errorResponse($parseResult['error']);
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();
    
    $hostsCount = 0;
    $portsCount = 0;
    
    // Save scan information
    $stmt = $pdo->prepare("
        INSERT INTO nmap_scans (filename, scan_type, scan_command, scan_start, scan_end, hosts_discovered, ports_discovered) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $scanCommand = $parseResult['scan_info']['command'] ?? null;
    $scanStart = $parseResult['scan_info']['start'] ?? null;
    $scanEnd = $parseResult['scan_info']['end'] ?? null;
    
    // Process each host
    foreach ($parseResult['hosts'] as $hostData) {
        if (empty($hostData['ip'])) continue;
        
        // Insert or update host
        $stmt = $pdo->prepare("
            INSERT INTO hosts (ip_address, hostname, os, status) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                hostname = COALESCE(VALUES(hostname), hostname),
                os = COALESCE(VALUES(os), os),
                status = VALUES(status),
                last_seen = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $hostData['ip'],
            $hostData['hostname'],
            $hostData['os'],
            $hostData['status']
        ]);
        
        // Get host ID
        $hostId = $pdo->lastInsertId();
        if ($hostId == 0) {
            $stmt = $pdo->prepare("SELECT id FROM hosts WHERE ip_address = ?");
            $stmt->execute([$hostData['ip']]);
            $hostId = $stmt->fetchColumn();
        }
        
        $hostsCount++;
        
        // Insert ports
        foreach ($hostData['ports'] as $portData) {
            $stmt = $pdo->prepare("
                INSERT INTO ports (host_id, port_number, protocol, service_name, service_version, state) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    service_name = VALUES(service_name),
                    service_version = VALUES(service_version),
                    state = VALUES(state),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $hostId,
                $portData['number'],
                $portData['protocol'],
                $portData['service'],
                $portData['version'],
                $portData['state']
            ]);
            
            $portsCount++;
        }
    }
    
    // Update scan record with counts
    $stmt = $pdo->prepare("
        INSERT INTO nmap_scans (filename, scan_command, scan_start, scan_end, hosts_discovered, ports_discovered) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $filename,
        $scanCommand,
        $scanStart,
        $scanEnd,
        $hostsCount,
        $portsCount
    ]);
    
    $pdo->commit();
    
    successResponse([
        'hosts_count' => $hostsCount,
        'ports_count' => $portsCount
    ], "Nmap scan parsed successfully. Found $hostsCount hosts and $portsCount ports.");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    unlink($filepath);
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
