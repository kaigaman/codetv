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
    if out: print(out[:3000])
    if err: print(err[:500])

print('=== LARAVEL LOG ===')
r("docker logs codetv-laravel-1 2>&1 | tail -30", 15)
print('\n=== LARAVEL ERROR LOG ===')
r("docker exec codetv-laravel-1 cat /var/www/storage/logs/laravel.log 2>&1 | tail -30", 15)
print('\n=== .ENV FILE ===')
r("docker exec codetv-laravel-1 cat /var/www/.env 2>&1 | grep -E 'APP_URL|APP_ENV|APP_DEBUG|DB_'", 15)
print('\n=== CONFIG ===')
r("docker exec codetv-laravel-1 php artisan config:show app 2>&1 | grep -i 'url' | head -5", 15)
ssh.close()
