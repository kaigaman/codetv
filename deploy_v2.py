import paramiko, time, os

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=30)

def run(cmd, timeout=60):
    print(f'$ {cmd}')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    exit_code = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out: print(out[:2000])
    if err and exit_code != 0: print(f'ERR: {err[:500]}')
    return exit_code, out

# Step 1: Fix DNS
print('=== STEP 1: FIXING DNS ===')
run('echo "nameserver 8.8.8.8" > /etc/resolv.conf && echo "nameserver 1.1.1.1" >> /etc/resolv.conf')
run('cat /etc/resolv.conf')
run('ping -c 1 -W 5 github.com 2>&1 || echo "PING_FAILED"')

# Step 2: Clone again
print('\n=== STEP 2: CLONING CODETV ===')
run('cd /opt && rm -rf codetv && git clone https://github.com/kaigaman/codetv.git', timeout=120)

# Step 3: Create .env
print('\n=== STEP 3: CONFIGURING .ENV ===')
run('mkdir -p /opt/codetv/backend')
run("""cat > /opt/codetv/backend/.env << 'ENVEOF'
APP_NAME=CODETV
APP_ENV=production
APP_DEBUG=false
APP_URL=https://code5.online
DB_HOST=mysql
DB_DATABASE=codetv
DB_USERNAME=codetv
DB_PASSWORD=codetv_pass
REDIS_HOST=redis
PYTHON_API=http://python:8000
KPTV_FAST_API=http://kptv-fast:8080
IPTV_API_URL=http://iptv-api:8080
SANCTUM_STATEFUL_DOMAINS=code5.online
SESSION_DOMAIN=.code5.online
ENVEOF
""")

# Step 4: Update nginx config (using echo instead of SFTP)
print('\n=== STEP 4: UPDATING NGINX CONFIG FOR CODE5.ONLINE ===')
nginx = '''server {
    listen 66.212.18.106:443 ssl http2;
    server_name code5.online www.code5.online;
    ssl_certificate /etc/letsencrypt/live/code5.online/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/code5.online/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    client_max_body_size 100m;
    location / {
        proxy_pass http://127.0.0.1:80;
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
    server_name code5.online www.code5.online;
    return 301 https://$server_name$request_uri;
}
'''
run(f"cat > /etc/nginx/conf.d/domains/code5.online.ssl.conf << 'NGXEOF'\n{nginx}\nNGXEOF", timeout=10)
run('nginx -t && systemctl reload nginx')

# Step 5: Deploy Docker
print('\n=== STEP 5: DEPLOYING DOCKER STACK ===')
run('cd /opt/codetv && docker compose down --remove-orphans 2>/dev/null', timeout=60)
run('cd /opt/codetv && docker compose up -d --build', timeout=300)

# Step 6: Verify
print('\n=== STEP 6: VERIFICATION ===')
time.sleep(15)
run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", timeout=10)
run('curl -s -o /dev/null -w "localtest: HTTP %{http_code}\n" http://localhost --connect-timeout 15', timeout=20)

# Step 7: Stop the old Node.js app on port 3002 if still running
print('\n=== STEP 7: CLEANING UP OLD APP ===')
run('fuser -k 3002/tcp 2>/dev/null || echo "no process on 3002"')

ssh.close()
print('\n✅ CODETV deployed on code5.online')
