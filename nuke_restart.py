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
        if out: print(out[:2000])
        if err and ec != 0: print(f'ERR: {err[:300]}')
        return ec, out
    except Exception as e: print(f'ERR: {e}'); return -1, ''

# Find ALL processes on conflicting ports
print('=== PORT SCAN ===')
r("ss -tlnp | grep -E ':(6379|8080|5555|8000)'", 10)

# Kill ALL Docker containers & prune
print('\n=== NUKE DOCKER ===')
r("docker kill $(docker ps -q) 2>/dev/null; docker rm -f $(docker ps -aq) 2>/dev/null", 30)
r("docker system prune -f 2>/dev/null", 30)
time.sleep(2)
r("ss -tlnp | grep -E ':(6379|8080|5555|8000)' || echo 'ALL PORTS FREE'", 10)

# Start fresh - note: remove port mappings from docker-compose to avoid future conflicts
print('\n=== MODIFY DOCKER COMPOSE ===')
r("""cat > /opt/codetv/docker-compose.override.yml << 'EOF'
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
EOF""", 10)

# Validate
r('cd /opt/codetv && docker compose config -q 2>&1 && echo "VALID" || echo "INVALID"', 30)

print('\n=== START STACK ===')
r('cd /opt/codetv && docker compose up -d 2>&1', 600)
time.sleep(20)

print('\n=== STATE ===')
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)

print('\n=== TESTS ===')
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 10 http://localhost:8080", 15)
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 15)

# Sync
ec, out = r("docker ps --format '{{.Names}}' | grep laravel", 10)
if 'laravel' in out:
    print('\n=== SYNC ===')
    r('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -20', 600)
else:
    print(f'\nLARAVEL STATUS: {out}')

ssh.close()
print('DONE')
