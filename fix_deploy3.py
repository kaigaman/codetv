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

# Check what's using port 3306
print('\n=== WHAT USES PORT 3306? ===')
run(ssh, "ss -tlnp | grep 3306", 10)
# Check what's using 8081
run(ssh, "ss -tlnp | grep 8081", 10)

# Remove port mappings from docker-compose that conflict
print('\n=== FIXING DOCKER COMPOSE PORT CONFLICTS ===')
# Read current docker-compose
run(ssh, "cd /opt/codetv && cat docker-compose.yml", 10)

# Remove 3306 and 8081 port mappings from docker-compose
run(ssh, """sed -i '/3306:3306/d' /opt/codetv/docker-compose.yml""", 10)
run(ssh, """sed -i '/8081:8080/d' /opt/codetv/docker-compose.yml""", 10)
run(ssh, """sed -i '/6379:6379/d' /opt/codetv/docker-compose.yml""", 10)

# Verify the changes
print('\n=== VERIFIED PORTS IN COMPOSE ===')
run(ssh, "grep -E 'ports:|3306|8081|6379|8082|8000|5555' /opt/codetv/docker-compose.yml || echo 'cleaned'", 10)

# Restart stack
print('\n=== RESTARTING DOCKER STACK ===')
run(ssh, 'cd /opt/codetv && docker compose down --remove-orphans 2>/dev/null', 60)
time.sleep(3)
run(ssh, 'cd /opt/codetv && docker compose up -d --build 2>&1 | tail -20', 600)

time.sleep(20)

print('\n=== FINAL STATE ===')
run(ssh, "docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
run(ssh, "curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 20)
run(ssh, "curl -s -o /dev/null -w 'Nginx proxy: HTTP %{http_code}\n' --connect-timeout 15 http://localhost", 20)

# Run sync
print('\n=== RUNNING SYNC ===')
run(ssh, 'cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -30', 600)

# Test HTTPS
print('\n=== TESTING HTTPS ===')
ec, out = run(ssh, "curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 15 https://localhost 2>&1", 20)
run(ssh, "curl -sk -o /dev/null -w 'HTTPS to code5.online: HTTP %{http_code}\n' --connect-timeout 15 --resolve code5.online:443:127.0.0.1 https://code5.online 2>&1", 20)

ssh.close()
print('\nDONE')
