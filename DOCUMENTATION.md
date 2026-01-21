# Security Analytics Center - Interface Overview

## Color Scheme (Dark Cybersecurity Theme)

- **Background**: Dark gray (#0d1117, #161b22, #21262d)
- **Text**: Light gray (#c9d1d9, #8b949e)
- **Primary Accent**: Blue (#58a6ff)
- **Danger**: Red (#f85149)
- **Success**: Green (#3fb950)
- **Warning**: Yellow (#d29922)

## Main Pages

### 1. Dashboard (index.php)
- **Header**: Navigation bar with all sections
- **Statistics Cards**: 4 cards showing:
  - Total Vulnerabilities (red)
  - Discovered Hosts (blue)
  - Open Ports (green)
  - CTF Challenges (yellow)
- **Charts**: 
  - Pie chart for vulnerability severity distribution
  - Bar chart for vulnerability status
- **Recent Activities**:
  - Recent vulnerabilities list
  - Recent scan uploads list

### 2. Vulnerabilities Page (/pages/vulnerabilities.php)
- **Search Bar**: Real-time filtering
- **Severity Filter**: Dropdown (Critical, High, Medium, Low, Info)
- **Add Button**: Opens modal for new vulnerability
- **Table**: 
  - Columns: ID, Title, Host, Severity (colored badge), Status (colored badge), CVE, Discovered Date, Actions
  - Actions: Edit (blue), Delete (red)
- **Modal Form**:
  - Title, Host dropdown, Severity, Status
  - CVE ID, CVSS Score
  - Description, Solution, References

### 3. Nmap Upload Page (/pages/nmap.php)
- **Upload Area**: Drag & drop zone with icon
- **Instructions Card**: Shows example commands
- **Recent Scans Table**: Displays uploaded scans with stats

### 4. Nuclei Upload Page (/pages/nuclei.php)
- **Upload Area**: Similar to Nmap
- **Instructions Card**: Nuclei command examples
- **Recent Scans Table**: Shows vulnerabilities found

### 5. CTF Management (/pages/ctf.php)
- **Tabs**: Competitions | Challenges
- **Competitions Tab**:
  - Table with competition details
  - Add competition modal
- **Challenges Tab**:
  - Category filter (Web, Pwn, Crypto, Forensics, Misc, Reverse, OSINT)
  - Status filter (Solved, Unsolved, In Progress)
  - Challenge table with writeups

### 6. Notes Page (/pages/notes.php)
- **Card Grid Layout**: Notes displayed as cards
- **Category Filter**: Security, CTF, Development, Research, Other
- **Add Note Modal**: Title, Category, Tags, Content, Related To
- **Each Card Shows**:
  - Title, Category badge, Tags
  - Content preview (150 chars)
  - Date, Edit/Delete buttons

## Features

### Security
- ✅ SQL Injection protection (PDO prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ File validation (type and size)
- ✅ CSRF would be added in production

### User Experience
- ✅ Responsive design (Bootstrap 5)
- ✅ Dark theme for reduced eye strain
- ✅ Real-time search and filtering
- ✅ AJAX operations (no page reloads)
- ✅ Toast notifications for actions
- ✅ Loading spinners
- ✅ Modal dialogs for forms

### Data Visualization
- ✅ Chart.js pie and bar charts
- ✅ Colored severity badges
- ✅ Status indicators
- ✅ Statistics cards

## Technology Stack

### Frontend
- Bootstrap 5.3.2 (responsive grid, components)
- Font Awesome 6.5.1 (icons)
- Chart.js 4.4.1 (charts)
- Vanilla JavaScript (AJAX, DOM manipulation)

### Backend
- PHP 8.2-FPM
- PDO for database
- Custom routing via Nginx

### Database
- MySQL 8.0
- 8 normalized tables
- Foreign keys and indexes

### Infrastructure
- Docker Compose
- Nginx (Alpine)
- phpMyAdmin
- Persistent volumes for data

## File Upload Flow

### Nmap XML Upload
1. User selects/drops XML file
2. JavaScript validates client-side
3. File sent via FormData to /api/upload_nmap.php
4. PHP validates file type
5. XML parsed using SimpleXML
6. Data extracted: hosts, ports, services
7. Database updated with INSERT ON DUPLICATE KEY
8. Response with statistics
9. UI updated, toast notification shown

### Nuclei JSON/TXT Upload
1. Similar flow to Nmap
2. Supports both JSON (JSONL) and TXT formats
3. Creates vulnerability records automatically
4. Links to existing or creates new hosts
5. Extracts CVE, severity, references

## API Endpoints

All endpoints return JSON responses with format:
```json
{
  "success": true/false,
  "message": "...",
  "data": {...},
  "error": "..." // if failed
}
```

### Vulnerabilities API (/api/vulnerabilities.php)
- GET ?action=list - List all vulnerabilities
- GET ?action=get&id=X - Get single vulnerability
- POST ?action=create - Create new vulnerability
- POST ?action=update - Update vulnerability
- DELETE ?action=delete&id=X - Delete vulnerability

### CTF API (/api/ctf.php)
- GET ?action=list - List competitions
- GET ?action=get&id=X - Get competition with challenges
- GET ?action=list_challenges - List all challenges
- POST ?action=create_competition - Create competition
- POST ?action=create_challenge - Create challenge
- POST ?action=update_competition - Update competition
- POST ?action=update_challenge - Update challenge
- DELETE ?action=delete_competition&id=X - Delete competition
- DELETE ?action=delete_challenge&id=X - Delete challenge

### Notes API (/api/notes.php)
- GET ?action=list - List notes
- GET ?action=get&id=X - Get single note
- POST ?action=create - Create note
- POST ?action=update - Update note
- DELETE ?action=delete&id=X - Delete note

### Upload APIs
- POST /api/upload_nmap.php - Upload Nmap XML
- POST /api/upload_nuclei.php - Upload Nuclei results

## Database Schema Summary

```
hosts (id, ip_address, hostname, os, status, first_seen, last_seen, notes)
  ↓
ports (id, host_id, port_number, protocol, service_name, service_version, state)
  ↓
vulnerabilities (id, host_id, title, description, severity, status, cve_id, cvss_score, solution, references)

nmap_scans (id, filename, uploaded_at, scan_type, hosts_discovered, ports_discovered, scan_command)

nuclei_scans (id, filename, uploaded_at, vulnerabilities_found, scan_target)

ctf_competitions (id, name, url, start_date, end_date, platform, team_name, final_rank, total_points, notes)
  ↓
ctf_challenges (id, competition_id, name, category, points, status, description, flag, writeup, solution_files, solved_at)

notes (id, title, content, category, tags, related_type, related_id)
```

## Quick Start Commands

```bash
# Clone and setup
git clone https://github.com/plamagnum/analitycs.git
cd analitycs
cp .env.example .env

# Start services
docker compose up -d

# Check status
docker compose ps

# Access services
# Web: http://localhost
# phpMyAdmin: http://localhost:8080
# MySQL: localhost:3306

# View logs
docker compose logs -f

# Stop services
docker compose down
```

## Future Enhancements (Not Implemented)

- User authentication and authorization
- API token authentication
- Export reports (PDF, CSV)
- Scheduled scans
- Email notifications
- Integration with external APIs (Shodan, VirusTotal)
- Webhook support
- Advanced analytics and trends
- Dark/Light theme toggle
- Multi-language support
