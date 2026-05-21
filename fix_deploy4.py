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

# Restore clean docker-compose.yml from git
print('\n=== RESTORING DOCKER COMPOSE ===')
run(ssh, 'cd /opt/codetv && git checkout -- docker-compose.yml', 10)
run(ssh, 'cd /opt/codetv && grep -E "^    ports:" docker-compose.yml', 10)

# Create override file that removes conflicting ports
print('\n=== CREATING OVERRIDE ===')
run(ssh, """cat > /opt/codetv/docker-compose.override.yml << 'YAMLEOF'
services:
  mysql:
    ports: []
  redis:
    ports: []
  kptv-fast:
    ports: []
  flower:
    ports: []
  python:
    ports: []
YAMLEOF
""", 10)

# Verify YAML is valid
print('\n=== VALIDATING ===')
run(ssh, 'cd /opt/codetv && docker compose config 2>&1 | head -20', 30)

# Down and restart
print('\n=== RESTARTING DOCKER STACK ===')
run(ssh, 'cd /opt/codetv && docker compose down --remove-orphans 2>/dev/null', 60)
time.sleep(3)
run(ssh, 'cd /opt/codetv && docker compose up -d --build 2>&1 | tail -30', 600)

time.sleep(20)

print('\n=== FINAL STATE ===')
run(ssh, "docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
run(ssh, "curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 20)
run(ssh, "curl -s -o /dev/null -w 'Nginx: HTTP %{http_code}\n' --connect-timeout 15 http://localhost", 20)

# Run sync
ec, out = run(ssh, "docker ps --format '{{.Names}}' | grep laravel", 10)
if 'laravel' in out:
    print('\n=== RUNNING SYNC ===')
    run(ssh, 'cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -30', 600)

# Test HTTPS
print('\n=== TESTING HTTPS ===')
run(ssh, "curl -sk -o /dev/null -w 'HTTPS code5.online: HTTP %{http_code}\n' --connect-timeout 15 --resolve code5.online:443:127.0.0.1 https://code5.online 2>&1", 20)

ssh.close()
print('\nDONE')
