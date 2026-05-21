import paramiko
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=60):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode(errors='replace')
    err = e.read().decode(errors='replace')
    if out: print(out[:2000])
    if err and ec != 0: print(f'ERR: {err[:200]}')

print('=== GENERATE KEY ===')
r('docker exec codetv-laravel-1 php artisan key:generate --force 2>&1', 15)
print('\n=== CACHE CLEAR ===')
r('docker exec codetv-laravel-1 php artisan config:clear 2>&1', 15)
print('\n=== TEST ===')
r("curl -s -o /dev/null -w 'Laravel: HTTP %{http_code}\n' --connect-timeout 10 http://localhost:8080", 15)
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 15)
print('\n=== SYNC ===')
r('docker exec codetv-laravel-1 php artisan iptv:sync-soccer --validate 2>&1', 600)
ssh.close()
