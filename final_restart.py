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

# Remove override (it didn't work)
r('rm -f /opt/codetv/docker-compose.override.yml', 10)

# Kill the host redis that's blocking port 6379
print('\n=== KILL HOST REDIS ===')
r("systemctl stop redis-server 2>/dev/null; systemctl stop redis 2>/dev/null; kill -9 66568 2>/dev/null; sleep 1", 15)
r("ss -tlnp | grep 6379 || echo '6379 FREE'", 10)

# Kill native PHP on 8080 if exists
r("pkill -f 'php artisan serve' 2>/dev/null; sleep 1", 10)
r("ss -tlnp | grep 8080 || echo '8080 FREE'", 10)

# Down everything
print('\n=== NUKE DOCKER ===')
r("docker kill $(docker ps -q) 2>/dev/null; docker rm -f $(docker ps -aq) 2>/dev/null; sleep 2", 30)

# Start fresh
print('\n=== START COMPOSE ===')
r('cd /opt/codetv && docker compose up -d 2>&1', 600)
time.sleep(20)

print('\n=== STATE ===')
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)

print('\n=== TESTS ===')
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 10 http://localhost:8080", 15)
r("curl -sk -o /dev/null -w 'HTTPS mamboleo.online: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 15)

# Sync
ec, out = r("docker ps --format '{{.Names}}' | grep laravel", 10)
if 'laravel' in out:
    print('\n=== SOCCER SYNC ===')
    r('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -30', 600)
else:
    print(f'LARAVEL: {out}')
    # Wait and check again
    time.sleep(30)
    r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
    r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 20)

ssh.close()
print('DONE')
