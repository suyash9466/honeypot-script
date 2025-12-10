import sys
import json
import sqlite3
import geoip2.database
from datetime import datetime
from pathlib import Path

CONFIG = {
    'geoip_path': r'C:\xampp\geoip\GeoLite2-City.mmdb',
    'admin_db_path': r'C:\xampp\logs\admin_attacks.db',
    'log_dir': r'C:\xampp\logs\admin',
    'admin_block_threshold': 2 
}

def init_admin_db():
    """Initialize SQLite database for admin attacks"""
    Path(CONFIG['log_dir']).mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(CONFIG['admin_db_path'])
    c = conn.cursor()
    
    c.execute('''CREATE TABLE IF NOT EXISTS admin_attempts
                 (id INTEGER PRIMARY KEY AUTOINCREMENT,
                 timestamp DATETIME,
                 ip TEXT,
                 country TEXT,
                 city TEXT,
                 username TEXT,
                 user_agent TEXT,
                 is_suspicious BOOLEAN)''')
    
    c.execute('''CREATE TABLE IF NOT EXISTS admin_ip_stats
                 (ip TEXT PRIMARY KEY,
                 attempts INTEGER DEFAULT 1,
                 last_attempt DATETIME,
                 is_blocked BOOLEAN DEFAULT FALSE)''')
    
    conn.commit()
    return conn

def geoip_lookup(ip):
    try:
        with geoip2.database.Reader(CONFIG['geoip_path']) as reader:
            resp = reader.city(ip)
            return {
                "ip": ip,
                "country": resp.country.name,
                "city": resp.city.name
            }
    except Exception as e:
        return {"ip": ip, "error": str(e)}

def analyze_admin_request(data):
    """Special analysis for admin login attempts"""
    conn = init_admin_db()
    c = conn.cursor()
    
    username = data['payload'].get('username', '')
    suspicious_users = ['admin', 'administrator', 'root', 'sysadmin']
    is_suspicious = username.lower() in suspicious_users
    
    c.execute('''INSERT OR IGNORE INTO admin_ip_stats
                 (ip, last_attempt)
                 VALUES (?,?)''',
              (data['ip'], data['timestamp']))
    
    c.execute('''UPDATE admin_ip_stats
                 SET attempts=attempts+1, last_attempt=?
                 WHERE ip=?''',
              (data['timestamp'], data['ip']))

    c.execute('''INSERT INTO admin_attempts
                 (timestamp, ip, country, city, username, user_agent, is_suspicious)
                 VALUES (?,?,?,?,?,?,?)''',
              (data['timestamp'],
               data['ip'],
               data['geo'].get('country'),
               data['geo'].get('city'),
               username,
               data['server']['user_agent'],
               is_suspicious))

    c.execute('SELECT attempts FROM admin_ip_stats WHERE ip=?', (data['ip'],))
    attempts = c.fetchone()[0]
    
    if attempts >= CONFIG['admin_block_threshold']:
        block_ip(data['ip'])
        c.execute('UPDATE admin_ip_stats SET is_blocked=TRUE WHERE ip=?', (data['ip'],))
        log_alert(f"ADMIN BLOCK: {data['ip']} after {attempts} attempts")
    
    conn.commit()
    conn.close()

def analyze_request(data):
    analyze_admin_request(data)

def block_ip(ip):
    """Block IP in Windows Firewall"""
    import subprocess
    try:
        subprocess.run(
            ['netsh', 'advfirewall', 'firewall', 'add', 'rule',
             f'name=BLOCK_ADMIN_{ip}',
             'dir=in', 'action=block', 'protocol=any',
             f'remoteip={ip}'],
            check=True
        )
    except subprocess.CalledProcessError as e:
        log_alert(f"Failed to block {ip}: {str(e)}")

def log_alert(message):
    """Log security alerts"""
    Path(CONFIG['log_dir']).mkdir(parents=True, exist_ok=True)
    alert_file = Path(CONFIG['log_dir']) / 'alerts.log'
    with open(alert_file, 'a') as f:
        f.write(f"[{datetime.now()}] {message}\n")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: honeypot.py [geoip|analyze|admin_analyze] [data]")
        sys.exit(1)

    command = sys.argv[1]
    data = sys.argv[2]

    try:
        if command == "geoip":
            print(json.dumps(geoip_lookup(data)))
        elif command == "admin_analyze":
            analyze_admin_request(json.loads(data))
        elif command == "analyze":
            analyze_request(json.loads(data)) 
    except Exception as e:
        log_alert(f"Processing error: {str(e)}")
