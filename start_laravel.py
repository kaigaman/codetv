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

# What's on port 8080?
print('=== PORT 8080 ===')
r("ss -tlnp | grep 8080", 10)

# Stop everything using port 8080
r("fuser -k 8080/tcp 2>/dev/null; sleep 1", 10)

# Restart Laravel specifically
print('\n=== STARTING LARAVEL ===')
r('cd /opt/codetv && docker compose up -d laravel 2>&1', 300)
time.sleep(10)

# Check
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'laravel|NAMES'", 10)

# Test
print('\n=== TEST ===')
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 10 http://localhost:8080", 15)
r("curl -sk -o /dev/null -w 'HTTPS mamboleo.online: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 15)
r("curl -s -o /dev/null -w 'Nginx proxy: HTTP %{http_code}\n' --connect-timeout 10 http://localhost", 15)

# Run sync if Laravel is up
ec, out = r("docker ps --format '{{.Names}}' | grep laravel", 10)
if 'laravel' in out:
    print('\n=== SYNC ===')
    r('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -20', 600)

ssh.close()
print('DONE')
