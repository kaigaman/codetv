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
    if err: print(f'ERR: {err[:300]}')

print('=== LARAVEL LOG (last 40 lines) ===')
r('docker exec codetv-laravel-1 tail -40 /var/www/storage/logs/laravel.log 2>/dev/null', 15)
print('\n=== CONFIG CACHE ===')
r("docker exec codetv-laravel-1 grep -E 'DB_HOST|APP_URL|APP_KEY' /var/www/.env", 15)
print('\n=== PHP INFO ===')
r("docker exec codetv-laravel-1 php -r \"echo env('APP_KEY') . PHP_EOL;\"", 15)
ssh.close()
