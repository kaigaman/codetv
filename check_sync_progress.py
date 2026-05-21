import paramiko
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=30):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode('utf-8', errors='replace')
    err = e.read().decode('utf-8', errors='replace')
    clean = out.encode('ascii', errors='replace').decode()
    if clean: print(clean[:4000])

print('=== CHECK SYNC PROCESS ===')
r('ps aux | grep "sync-soccer\|iptv:sync" | grep -v grep', 10)
print('\n=== LARAVEL LOG ===')
r('docker exec codetv-laravel-1 tail -20 /var/www/storage/logs/laravel.log 2>/dev/null', 10)
print('\n=== PERSIST APP_KEY + SESSION_DRIVER IN .ENV ===')
r('docker exec codetv-laravel-1 grep -E "APP_KEY|SESSION_DRIVER" /var/www/.env', 10)
print('\n=== HOST .ENV ===')
r('grep -E "APP_KEY|SESSION_DRIVER" /opt/codetv/backend/.env 2>/dev/null; echo "---"; cat /opt/codetv/backend/.env 2>/dev/null', 10)
ssh.close()
