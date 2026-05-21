import paramiko, time

HOST, PORT, USER, PASSWORD = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=60, banner_timeout=60)
print('CONNECTED')

def r(c, t=60):
    try:
        i,o,e = ssh.exec_command(c, timeout=t)
        ec = o.channel.recv_exit_status()
        out = o.read().decode().strip()
        err = e.read().decode().strip()
        if out: print(out[:3000])
        if err and ec != 0: print(f'ERR: {err[:300]}')
    except Exception as e: print(f'ERR: {e}')

# 1. Remove SSL config, write HTTP-only config
print('\n=== 1. HTTP-ONLY NGINX CONFIG ===')
r("""cat > /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf << 'EOF'
server {
    listen 66.212.18.106:80;
    server_name mamboleo.online www.mamboleo.online;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_buffering off;
        proxy_read_timeout 120s;
    }
}
EOF""")
r('nginx -t && systemctl reload nginx', 10)

# 2. Get SSL cert
print('\n=== 2. GET SSL CERT ===')
r('certbot --nginx -d mamboleo.online -d www.mamboleo.online --non-interactive --agree-tos --email admin@mamboleo.online --redirect 2>&1', 120)

# 3. Verify nginx works
print('\n=== 3. VERIFY NGINX ===')
r('nginx -t && systemctl reload nginx', 10)
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 15 --resolve mamboleo.online:443:127.0.0.1 https://mamboleo.online", 20)
r("curl -s -o /dev/null -w 'HTTP: HTTP %{http_code}\n' --connect-timeout 15 http://localhost", 20)
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 20)

# 4. Wait for Docker build to finish
print('\n=== 4. WAITING FOR DOCKER ===')
for i in range(60):
    ec, out = 0, ''
    try:
        i2,o2,e2 = ssh.exec_command("docker ps --format '{{.Names}}' | grep laravel", timeout=15)
        out = o2.read().decode().strip()
    except: pass
    if 'laravel' in out:
        print(f'Laravel UP after {i*5}s')
        break
    print(f'  waiting... ({i*5}s)')
    time.sleep(5)

# 5. Show final state
print('\n=== 5. FINAL STATE ===')
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
r("curl -s -o /dev/null -w 'HTTPS mamboleo.online: HTTP %{http_code}\n' --connect-timeout 15 --resolve mamboleo.online:443:127.0.0.1 https://mamboleo.online", 20)

# 6. Sync
print('\n=== 6. SYNC ===')
r("docker ps --format '{{.Names}}' | grep laravel", 10)
r('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -20', 600)

ssh.close()
print('DONE')
