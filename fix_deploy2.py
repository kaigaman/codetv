import paramiko, time

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

def run(ssh, cmd, timeout=60):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    ec = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out: print(out[:3000])
    if err and ec != 0: print(f'ERR: {err[:500]}')
    return ec, out

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD,
            look_for_keys=False, allow_agent=False, timeout=60, banner_timeout=60)
print('CONNECTED')

# Check git status
print('\n=== GIT STATUS ===')
ec, out = run(ssh, "cd /opt/codetv && git status --short", 10)
if out:
    print(f'Local changes: {out}')
    run(ssh, "cd /opt/codetv && git stash", 10)

# Pull latest code
print('\n=== PULLING LATEST CODE ===')
run(ssh, "cd /opt/codetv && git pull origin main 2>&1 | head -20", 60)

# Check nginx configs listening on 8081
print('\n=== CHECK NGINX PORT 8081 ===')
run(ssh, "grep -r '8081' /etc/nginx/ 2>/dev/null", 10)
run(ssh, "ls -la /etc/nginx/conf.d/domains/", 10)

# Fix: remove the conflicting 8081 port from nginx if it exists
run(ssh, "grep -l '8081' /etc/nginx/conf.d/domains/*.conf 2>/dev/null && echo 'FOUND' || echo 'NOT FOUND'", 10)

# Stop Docker stack, restart with free port 8081
print('\n=== RESTARTING DOCKER STACK ===')
run(ssh, 'cd /opt/codetv && docker compose down --remove-orphans 2>/dev/null', 60)
time.sleep(5)
run(ssh, 'cd /opt/codetv && docker compose up -d --build 2>&1 | tail -30', 600)

time.sleep(15)

print('\n=== FINAL STATE ===')
run(ssh, "docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
run(ssh, "curl -s -o /dev/null -w 'Laravel port 8080: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 20)
run(ssh, "curl -s -o /dev/null -w 'Nginx proxy to 8080: HTTP %{http_code}\n' --connect-timeout 15 http://localhost", 20)

# Run sync
print('\n=== RUNNING CURATED/SOCCER SYNC ===')
run(ssh, 'cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -20', 600)

ssh.close()
print('\nDONE')
