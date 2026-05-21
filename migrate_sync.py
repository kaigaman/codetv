import paramiko
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=30):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode().strip()
    err = e.read().decode().strip()
    if out: print(out[:2000])
    if err and ec != 0: print(f'ERR: {err[:200]}')

print('=== MIGRATE ===')
r('docker exec codetv-laravel-1 php artisan migrate --force 2>&1 | head -30', 60)
print('\n=== CHECK DB ===')
r('docker exec codetv-laravel-1 php artisan db:show 2>&1 | head -10', 30)
print('\n=== TEST ===')
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 15 http://localhost:8080", 15)
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 15 https://mamboleo.online", 15)
print('\n=== SEED ===')
r('docker exec codetv-laravel-1 php artisan db:seed --force 2>&1 | head -20', 60)
print('\n=== SYNC ===')
r('docker exec codetv-laravel-1 php artisan iptv:sync-soccer --validate 2>&1', 600)
ssh.close()
