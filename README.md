# Paxton Carnegie Library - Movie Kiosk System

A touch-friendly kiosk interface for browsing and requesting DVDs from the library's collection.

## Features

### Kiosk Interface (`kiosk.php`)
- **Portrait-optimized** design for 16:9 vertical screens
- **Touch-friendly** with large tap targets and numpad input
- **Beautiful animations** - smooth transitions and hover effects
- **Movie browsing** - Featured, All Movies, and Search sections
- **Request system** - Patrons can request movies to be pulled by staff
- **Auto-logout** - Sessions expire after 90 seconds of inactivity
- **All ages friendly** - Simple, intuitive interface

### Staff Panel (`staff.php`)
- **Real-time requests** - See movie requests as they come in
- **Sound notifications** - Audio alert for new requests
- **Auto-refresh** - Automatically checks for new requests every 10 seconds
- **Request management** - Mark complete, delete, view history
- **Statistics** - Pending count, daily totals

## Setup

### 1. Configuration
Copy `config.example.php` to `config.php` and fill in your Polaris credentials:

```php
$username = 'DOMAIN\\your_username';
$password = 'your_password';
```

### 2. Directory Permissions
Make sure the `data/` directory is writable by the web server:

```bash
chmod 755 data/
```

### 3. Cover Images (Optional)
Run the cover cache builder to pre-fetch cover images:

```bash
php build_cover_cache.php
```

This can be run as a daily cron job to keep covers updated.

## File Structure

```
paxlib-v2-main/
├── kiosk.php              # Main kiosk interface
├── staff.php              # Staff panel for managing requests
├── config.php             # Configuration (credentials)
├── config.example.php     # Example configuration template
├── dvds.csv               # DVD inventory data
├── build_cover_cache.php  # Utility to pre-fetch cover images
├── api/
│   ├── list.php          # Returns DVD list from CSV
│   ├── item.php          # Fetches item details from Polaris
│   ├── request.php       # Submit movie request (POST)
│   ├── requests.php      # Request CRUD API
│   └── leap_proxy.php    # Polaris API proxy
├── data/
│   ├── requests.json     # Stored requests (auto-created)
│   └── covers_cache.json # Cached cover URLs (auto-created)
└── img/
    └── no-cover.svg      # Placeholder for missing covers
```

## API Endpoints

### GET `/api/list.php`
Returns all DVDs from the CSV file.

### GET `/api/item.php?barcode=XXXXX`
Fetches detailed item info from Polaris by barcode.

### POST `/api/request.php`
Creates a new movie request. Body:
```json
{
  "movie": {
    "barcode": "31783000573034",
    "title": "Movie Title",
    "callNumber": "DVD MOV",
    "cover": "https://..."
  },
  "patron": {
    "name": "John",
    "barcode": "21756..."
  }
}
```

### GET `/api/requests.php`
Lists all movie requests.

### PUT `/api/requests.php`
Updates a request (mark complete). Body:
```json
{
  "id": "abc123",
  "completed": true
}
```

### DELETE `/api/requests.php?id=XXX`
Deletes a specific request.

## Kiosk Deployment Tips

### Auto-start in Kiosk Mode
For Chromium on Raspberry Pi or similar:

```bash
chromium-browser --kiosk --disable-restore-session-state \
  --disable-session-crashed-bubble \
  http://localhost/kiosk.php
```

### Prevent Screen Timeout
```bash
xset s off
xset -dpms
xset s noblank
```

### Hide Cursor (optional)
```bash
unclutter -idle 0.5 -root &
```

## Customization

### Colors
Edit CSS variables in `kiosk.php`:
```css
:root {
  --accent: #4ade80;      /* Primary green */
  --gold: #fbbf24;        /* Warning/highlight */
  --bg: #0a0f0d;          /* Background */
}
```

### Timeout Duration
Edit in `kiosk.php`:
```javascript
const INACTIVITY_TIMEOUT = 90000; // 90 seconds
const WARNING_DURATION = 30000;   // 30 second warning
```

### Organization/Workstation IDs
Update in `api/item.php`:
```php
$prefix = 'polaris/699/3073';  // org/workstation IDs
```

## Requirements

- PHP 7.4+
- cURL extension
- Write access to `data/` directory
- Polaris API credentials
- Network access to Polaris server

## License

Internal use for Paxton Carnegie Library.
