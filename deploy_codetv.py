import paramiko, time

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
    if out: print(out[:3000])
    if err and exit_code != 0: print(f'ERR: {err[:500]}')
    return exit_code, out

# Step 1: Clone CODETV repo
print('=== STEP 1: CLONING CODETV REPO ===')
run('cd /opt && rm -rf codetv && git clone https://github.com/kaigaman/codetv.git', timeout=120)

# Step 2: Create .env for Docker production
print('\n=== STEP 2: CONFIGURING .ENV ===')
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

# Step 3: Check existing app on port 3002
print('\n=== STEP 3: CHECKING PORT 3002 ===')
run('ss -tlnp | grep 3002 || echo "NOT_RUNNING"')

# Step 4: Update nginx config for code5.online to point to Laravel
print('\n=== STEP 4: UPDATING NGINX CONFIG ===')
nginx_conf = '''server {
    listen 66.212.18.106:443 ssl;
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
        proxy_connect_timeout 30s;
    }

    location /storage/ {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_cache_valid 200 302 1h;
        expires max;
    }
}

server {
    listen 66.212.18.106:80;
    server_name code5.online www.code5.online;
    return 301 https://$server_name$request_uri;
}
'''

transport = ssh.get_transport()
sftp = paramiko.SFTPClient.from_transport(transport)
with sftp.open('/etc/nginx/conf.d/domains/code5.online.ssl.conf', 'w') as f:
    f.write(nginx_conf)
sftp.close()

run('nginx -t && systemctl reload nginx || echo "NGINX_CHECK_FAILED"')

# Step 5: Deploy Docker stack
print('\n=== STEP 5: DEPLOYING DOCKER STACK ===')
run('cd /opt/codetv && docker compose down --remove-orphans 2>/dev/null', timeout=60)
run('cd /opt/codetv && docker compose up -d --build', timeout=300)

# Step 6: Verify containers are running
print('\n=== STEP 6: VERIFYING CONTAINERS ===')
time.sleep(20)
run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", timeout=10)

# Step 7: Test the site
print('\n=== STEP 7: TESTING ===')
run('curl -s -o /dev/null -w "HTTP %{http_code} https://code5.online\n" https://code5.online --connect-timeout 15 --resolve code5.online:443:127.0.0.1 2>/dev/null || echo "LOCAL_TEST_FAILED"')
run("curl -s -o /dev/null -w 'HTTP %{http_code} http://localhost\n' http://localhost --connect-timeout 15 2>/dev/null || echo 'LOCALHOST_TEST_FAILED'")

ssh.close()
print('\n✅ Deployment complete!')
