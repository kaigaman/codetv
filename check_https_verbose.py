import paramiko
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=15):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode('utf-8', errors='replace')
    err = e.read().decode('utf-8', errors='replace')
    clean = out.encode('ascii', errors='replace').decode()
    if clean: print(clean[:4000])

print('=== VERBOSE HTTPS WITH RESPONSE BODY ===')
r("curl -skv https://mamboleo.online 2>&1", 10)
print('\n=== LARAVEL ACCESS LOG ===')
r("docker exec codetv-laravel-1 tail -10 /var/www/storage/logs/laravel.log 2>/dev/null", 10)
print('\n=== TRUNCATE LARAVEL LOG ===')
r('docker exec codetv-laravel-1 truncate -s 0 /var/www/storage/logs/laravel.log 2>&1', 10)
r("curl -sk https://mamboleo.online 2>&1", 10)
print('\n=== LARAVEL LOG AFTER REQUEST ===')
r("docker exec codetv-laravel-1 tail -10 /var/www/storage/logs/laravel.log 2>/dev/null", 10)
ssh.close()
