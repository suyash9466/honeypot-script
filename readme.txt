Admin Honeypot Logger & Blocker

A Python-based honeypot tool to detect and block suspicious admin login attempts. It tracks IP addresses, logs metadata like username and location, and blocks repeated offenders using the Windows Firewall.

## Features

-  Logs admin login attempts (username, IP, location, user-agent)
-  Geo-location lookup using MaxMind GeoLite2 database
-  Tracks repeated attempts per IP
-  Auto-blocks IPs with too many suspicious attempts
-  Stores all data in a SQLite database
-  Writes security alerts to log file

## Folder Structure
project/
├── honeypot.py # Main script
├── requirements.txt # Required Python packages
├── README.md # This file
├── /geoip/
│ └── GeoLite2-City.mmdb # GeoIP2 database (downloaded separately)
└── /logs/
├── /admin/
│ └── alerts.log # Alert logs
└── admin_attacks.db # SQLite DB (auto-created)