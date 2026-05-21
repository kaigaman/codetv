import paramiko, time

HOST, PORT, USER, PASSWORD = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=60, banner_timeout=60)
print('CONNECTED')

def r(c, t=600):
    try:
        stdin, stdout, stderr = ssh.exec_command(c, timeout=t)
        ec = stdout.channel.recv_exit_status()
        out = stdout.read().decode().strip()
        err = stderr.read().decode().strip()
        if out: print(out[:4000])
        if err and ec != 0: print(f'ERR: {err[:500]}')
        return ec, out
    except Exception as e:
        print(f'CMD_FAILED: {e}'); return -1, ''

# === 1. STOP CONFLICTING SERVICES & DOCKER ===
print('\n=== 1. CLEANUP ===')
r('systemctl stop mariadb mysql 2>/dev/null; pkill -9 mysqld mariadbd 2>/dev/null; sleep 1', 30)
r('mv /etc/nginx/conf.d/domains/stream-proxy.conf /etc/nginx/conf.d/domains/stream-proxy.conf.bak 2>/dev/null', 10)
r('cd /opt/codetv && docker compose down --remove-orphans 2>/dev/null', 60)

# === 2. GIT PULL ===
print('\n=== 2. GIT PULL ===')
r('cd /opt/codetv && git stash && git pull origin main 2>&1', 60)

# === 3. NGINX CONFIG FOR MAMBOLEO.ONLINE ===
print('\n=== 3. NGINX + SSL ===')
nginx = '''server {
    listen 66.212.18.106:443 ssl http2;
    server_name mamboleo.online www.mamboleo.online;
    ssl_certificate /etc/letsencrypt/live/mamboleo.online/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mamboleo.online/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    client_max_body_size 100m;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_buffering off;
        proxy_read_timeout 120s;
    }
}
server {
    listen 66.212.18.106:80;
    server_name mamboleo.online www.mamboleo.online;
    return 301 https://$server_name$request_uri;
}'''
r(f"cat > /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf << 'EOF'\n{nginx}\nEOF", 10)

# Remove old code5.online config (optional)
r('rm -f /etc/nginx/conf.d/domains/code5.online.ssl.conf', 10)

# Get SSL cert
print('\n--- Getting SSL cert ---')
r('certbot --nginx -d mamboleo.online -d www.mamboleo.online --non-interactive --agree-tos --email admin@mamboleo.online --redirect 2>&1 | tail -10', 120)

# Fix nginx config after certbot (certbot might have modified it)
r("""cat > /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf << 'EOF'
server {
    listen 66.212.18.106:443 ssl http2;
    server_name mamboleo.online www.mamboleo.online;
    ssl_certificate /etc/letsencrypt/live/mamboleo.online/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mamboleo.online/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    client_max_body_size 100m;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_buffering off;
        proxy_read_timeout 120s;
    }
}
server {
    listen 66.212.18.106:80;
    server_name mamboleo.online www.mamboleo.online;
    return 301 https://$server_name$request_uri;
}
EOF""", 10)

r('nginx -t && systemctl reload nginx', 10)

# === 4. .ENV ===
print('\n=== 4. CONFIGURING .ENV ===')
r("""cat > /opt/codetv/backend/.env << 'EOF'
APP_NAME=CODETV
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mamboleo.online
DB_HOST=mysql
DB_DATABASE=codetv
DB_USERNAME=codetv
DB_PASSWORD=codetv_pass
REDIS_HOST=redis
PYTHON_API=http://python:8000
KPTV_FAST_API=http://kptv-fast:8080
IPTV_API_URL=http://iptv-api:8080
SANCTUM_STATEFUL_DOMAINS=mamboleo.online
SESSION_DOMAIN=.mamboleo.online
TRUSTED_PROXIES=*
EOF""", 10)

# === 5. DOCKER COMPOSE UP ===
print('\n=== 5. DOCKER UP ===')
r('cd /opt/codetv && docker compose up -d --build 2>&1', 600)

time.sleep(20)

# === 6. VERIFY ===
print('\n=== 6. VERIFY ===')
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 10 http://localhost:8080", 15)
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 --resolve mamboleo.online:443:127.0.0.1 https://mamboleo.online", 15)

# === 7. SYNC ===
print('\n=== 7. SYNC SOCCER ===')
ec, out = r("docker ps --format '{{.Names}}' | grep laravel", 10)
if 'laravel' in out:
    r('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1', 600)

ssh.close()
print('\n✅ DONE')
