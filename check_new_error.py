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

print('=== CLEAR LOG ===')
r('docker exec codetv-laravel-1 truncate -s 0 /var/www/storage/logs/laravel.log 2>&1', 10)
print('\n=== MAKE REQUEST ===')
r("curl -s -o /dev/null -w 'HTTP %{http_code}\n' --connect-timeout 10 http://localhost:8080", 15)
print('\n=== NEW ERROR ===')
r('docker exec codetv-laravel-1 cat /var/www/storage/logs/laravel.log 2>&1', 15)
print('\n=== CONFIG DIR ===')
r('docker exec codetv-laravel-1 ls -la /var/www/config/', 15)
print('\n=== SESSION CONFIG ===')
r('docker exec codetv-laravel-1 cat /var/www/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php 2>&1 | head -5', 10)
ssh.close()
