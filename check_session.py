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
    if out: print(out[:5000])
    if err: print(f'ERR: {err[:300]}')

print('=== FULL ERROR MESSAGE ===')
r("docker exec codetv-laravel-1 grep -A 3 'production.ERROR' /var/www/storage/logs/laravel.log | head -20", 15)
print('\n=== STORAGE DIRS ===')
r('docker exec codetv-laravel-1 ls -la /var/www/storage/framework/', 15)
print('\n=== APP_DEBUG ===')
r("docker exec codetv-laravel-1 grep 'APP_DEBUG' /var/www/.env", 15)
print('\n=== SESSION CONFIG ===')
r("docker exec codetv-laravel-1 cat /var/www/config/session.php | head -30", 15)
ssh.close()
