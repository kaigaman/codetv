import paramiko, time
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=120):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode().strip()
    err = e.read().decode().strip()
    if out: print(out[:2000])
    if err and ec != 0: print(f'ERR: {err[:200]}')

print('=== GIT PULL ===')
r('cd /opt/codetv && git stash 2>/dev/null; git pull origin main 2>&1 | head -5', 60)
print('\n=== REBUILD LARAVEL ===')
r('cd /opt/codetv && docker compose build laravel 2>&1 | tail -10', 600)
print('\n=== START LARAVEL ===')
r('cd /opt/codetv && docker compose up -d laravel 2>&1', 300)
time.sleep(10)
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'laravel|NAMES'", 10)
r("curl -s -o /dev/null -w 'HTTP: %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 15)
print('\n=== SYNC ===')
r("cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-soccer --validate 2>&1 | tail -20", 600)
ssh.close()
