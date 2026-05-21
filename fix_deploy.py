import paramiko, time

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

def run(ssh, cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    ec = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out: print(out[:3000])
    if err and ec != 0: print(f'ERR: {err[:500]}')
    return ec, out

for i in range(10):
    try:
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD,
                    look_for_keys=False, allow_agent=False, timeout=60, banner_timeout=60)
        print(f'CONNECTED (attempt {i+1})')

        print('=== STATE ===')
        run(ssh, "docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
        run(ssh, "ss -tlnp | grep -E ':(80|443|8080|8082)'", 10)
        run(ssh, "cat /etc/nginx/conf.d/domains/code5.online.ssl.conf", 10)
        run(ssh, 'free -h', 10)

        # Fix: Change Laravel container to port 8080 to avoid port 80 conflict
        print('\n=== FIXING DOCKER PORT CONFLICT ===')
        run(ssh, 'cd /opt/codetv && docker compose down 2>/dev/null', 30)
        
        # Update docker-compose to use port 8080 for Laravel
        run(ssh, """sed -i 's/"80:80"/"8080:80"/' /opt/codetv/docker-compose.yml""", 10)
        run(ssh, """grep -n '8080:80' /opt/codetv/docker-compose.yml""", 10)

        # Update nginx proxy_pass to port 8080
        run(ssh, """sed -i 's|proxy_pass http://127.0.0.1:80;|proxy_pass http://127.0.0.1:8080;|' /etc/nginx/conf.d/domains/code5.online.ssl.conf""", 10)
        run(ssh, """grep proxy_pass /etc/nginx/conf.d/domains/code5.online.ssl.conf""", 10)
        run(ssh, 'nginx -t && systemctl reload nginx', 10)

        # Git pull latest code
        print('\n=== PULLING LATEST CODE ===')
        run(ssh, "cd /opt/codetv && git pull origin main 2>&1 | head -20", 30)

        # Update .env with production values
        run(ssh, """cat > /opt/codetv/backend/.env << 'ENVEOF'
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
TRUSTED_PROXIES=*
ENVEOF""", 10)

        # Restart Docker stack
        print('\n=== RESTARTING DOCKER STACK ===')
        run(ssh, 'cd /opt/codetv && docker compose up -d --build 2>&1 | tail -20', 600)

        time.sleep(15)

        print('\n=== FINAL CHECK ===')
        run(ssh, "docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
        run(ssh, "curl -s -o /dev/null -w 'localhost: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 20)
        run(ssh, "curl -s -o /dev/null -w 'nginx proxy: HTTP %{http_code}\n' --connect-timeout 15 http://localhost", 20)

        # Run sync
        ec, out = run(ssh, "docker ps --format '{{.Names}}' | grep laravel", 10)
        if 'laravel' in out:
            print('\n=== RUNNING CURATED/SOCCER SYNC ===')
            run(ssh, 'cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | head -50', 600)

        ssh.close()
        print('\nDONE')
        break
    except Exception as e:
        print(f'  Attempt {i+1} failed: {str(e)[:100]}')
        time.sleep(30)
