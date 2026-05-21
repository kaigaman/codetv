import paramiko
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=30):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode(errors='replace')
    err = e.read().decode(errors='replace')
    if out: print(out[:3000])
    if err and ec != 0: print(f'ERR: {err[:300]}')

print('=== LARAVEL LOG ===')
r('docker exec codetv-laravel-1 tail -40 /var/www/storage/logs/laravel.log 2>/dev/null', 15)
print('\n=== ARTISAN CHECK ===')
r('docker exec codetv-laravel-1 php artisan route:list --path=/ 2>&1 | head -10', 15)
print('\n=== CURL VERBOSE ===')
r("curl -s http://localhost:8080 2>&1 | head -50", 15)
ssh.close()
