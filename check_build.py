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
        return ec, out
    except Exception as e: print(f'ERR: {e}'); return -1, ''

# Check build process
print('=== BUILD STATUS ===')
ec, out = r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
ec, out = r("docker images --format '{{.Repository}}:{{.Tag}} {{.Size}}'", 10)
ec, out = r("ps aux | grep 'docker build' | head -5", 10)

# Check if Laravel image exists at all
ec, out = r("docker images codetv-laravel --format '{{.Repository}} {{.Size}}' 2>/dev/null || echo 'NO_IMAGE'", 10)
if 'NO_IMAGE' in out or not out:
    print('Laravel image does not exist - build needed')
else:
    print('Laravel image exists')

# Check buildx/buildkit status
ec, out = r("docker buildx version 2>/dev/null || echo 'no buildx'", 10)

# Check if compose is still building
ec, out = r("docker compose -f /opt/codetv/docker-compose.yml ps 2>&1 | head -10", 10)

# Let's check if we can start without build
print('\n=== ATTEMPTING COMPOSE WITHOUT BUILD ===')
r('cd /opt/codetv && docker compose up -d --no-build 2>&1 | tail -10', 120)
time.sleep(10)

ec, out = r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)

# Try external HTTPS test
print('\n=== EXTERNAL HTTPS TEST ===')
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online 2>&1", 20)

# Check Laravel specifically
ec, out = r("docker ps --format '{{.Names}} {{.Status}}' | grep -i laravel || echo 'NO_LARAVEL'", 10)
if 'laravel' in out:
    print('\n=== RUNNING SYNC ===')
    r('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -30', 600)

ssh.close()
print('DONE')
