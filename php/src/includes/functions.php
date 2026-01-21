<?php
// Helper functions for the application

/**
 * Sanitize output for HTML display
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Error response helper
 */
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Success response helper
 */
function successResponse($data = [], $message = 'Success') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedExtensions = []) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed'];
    }
    
    if (!empty($allowedExtensions)) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)];
        }
    }
    
    return ['valid' => true];
}

/**
 * Get severity color class
 */
function getSeverityClass($severity) {
    $classes = [
        'critical' => 'danger',
        'high' => 'warning',
        'medium' => 'info',
        'low' => 'secondary',
        'info' => 'light'
    ];
    return $classes[$severity] ?? 'secondary';
}

/**
 * Get status badge class
 */
function getStatusClass($status) {
    $classes = [
        'new' => 'danger',
        'in_progress' => 'warning',
        'fixed' => 'success',
        'false_positive' => 'secondary',
        'solved' => 'success',
        'unsolved' => 'danger',
        'up' => 'success',
        'down' => 'danger',
        'unknown' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Parse Nmap XML file
 */
function parseNmapXML($filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $xml = @simplexml_load_file($filePath);
    if ($xml === false) {
        return ['success' => false, 'error' => 'Invalid XML file'];
    }
    
    $result = [
        'success' => true,
        'hosts' => [],
        'scan_info' => []
    ];
    
    // Extract scan information
    if (isset($xml['args'])) {
        $result['scan_info']['command'] = (string)$xml['args'];
    }
    if (isset($xml['start'])) {
        $result['scan_info']['start'] = (string)$xml['start'];
    }
    if (isset($xml->runstats->finished['time'])) {
        $result['scan_info']['end'] = (string)$xml->runstats->finished['time'];
    }
    
    // Parse hosts
    foreach ($xml->host as $host) {
        $status = (string)$host->status['state'];
        if ($status !== 'up') continue;
        
        $hostData = [
            'ip' => '',
            'hostname' => null,
            'os' => null,
            'status' => $status,
            'ports' => []
        ];
        
        // Get IP address
        foreach ($host->address as $address) {
            if ((string)$address['addrtype'] === 'ipv4') {
                $hostData['ip'] = (string)$address['addr'];
                break;
            }
        }
        
        // Get hostname
        if (isset($host->hostnames->hostname)) {
            $hostData['hostname'] = (string)$host->hostnames->hostname['name'];
        }
        
        // Get OS
        if (isset($host->os->osmatch)) {
            $hostData['os'] = (string)$host->os->osmatch['name'];
        }
        
        // Parse ports
        if (isset($host->ports->port)) {
            foreach ($host->ports->port as $port) {
                $portData = [
                    'number' => (int)$port['portid'],
                    'protocol' => (string)$port['protocol'],
                    'state' => (string)$port->state['state'],
                    'service' => isset($port->service['name']) ? (string)$port->service['name'] : null,
                    'version' => isset($port->service['product']) ? (string)$port->service['product'] . ' ' . (string)$port->service['version'] : null
                ];
                $hostData['ports'][] = $portData;
            }
        }
        
        $result['hosts'][] = $hostData;
    }
    
    return $result;
}

/**
 * Parse Nuclei JSON/TXT file
 */
function parseNucleiFile($filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $content = file_get_contents($filePath);
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $result = [
        'success' => true,
        'vulnerabilities' => []
    ];
    
    if ($ext === 'json') {
        // Parse JSON format (JSONL - one JSON object per line)
        $lines = explode("\n", trim($content));
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = json_decode($line, true);
            if ($data === null) continue;
            
            $vuln = [
                'title' => $data['info']['name'] ?? 'Unknown',
                'description' => $data['info']['description'] ?? '',
                'severity' => strtolower($data['info']['severity'] ?? 'info'),
                'host' => $data['host'] ?? '',
                'matched_at' => $data['matched-at'] ?? $data['host'] ?? '',
                'template_id' => $data['template-id'] ?? $data['template'] ?? '',
                'cve_id' => isset($data['info']['classification']['cve-id']) ? implode(', ', (array)$data['info']['classification']['cve-id']) : null,
                'cvss_score' => $data['info']['classification']['cvss-score'] ?? null,
                'references' => isset($data['info']['reference']) ? implode("\n", (array)$data['info']['reference']) : null
            ];
            
            $result['vulnerabilities'][] = $vuln;
        }
    } else {
        // Parse TXT format (simple text output)
        $lines = explode("\n", trim($content));
        foreach ($lines as $line) {
            if (empty(trim($line)) || strpos($line, '[') !== 0) continue;
            
            // Parse format: [severity] [template-id] url
            preg_match('/\[(.*?)\]\s+\[(.*?)\]\s+(.*)/', $line, $matches);
            if (count($matches) >= 4) {
                $vuln = [
                    'title' => $matches[2],
                    'description' => '',
                    'severity' => strtolower($matches[1]),
                    'host' => $matches[3],
                    'matched_at' => $matches[3],
                    'template_id' => $matches[2],
                    'cve_id' => null,
                    'cvss_score' => null,
                    'references' => null
                ];
                
                $result['vulnerabilities'][] = $vuln;
            }
        }
    }
    
    return $result;
}
