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
    if clean: print(clean[:3000])

print('=== SYNC RESULT LOG ===')
r('docker exec codetv-laravel-1 cat /var/www/storage/logs/laravel.log 2>/dev/null', 10)
print('\n=== CHANNELS COUNT ===')
r("docker exec codetv-mysql-1 mysql -ucodetv -pcodetv_pass codetv -e 'SELECT COUNT(*) as total FROM channels; SELECT COUNT(*) FROM channels WHERE is_active=1 AND is_online=1;' 2>/dev/null", 10)
print('\n=== CATEGORIES ===')
r("docker exec codetv-mysql-1 mysql -ucodetv -pcodetv_pass codetv -e 'SELECT id, name, slug FROM categories;' 2>/dev/null", 10)
print('\n=== UPDATE HOST .ENV ===')
r('echo "APP_KEY=base64:8AHkQefMhCeCICq8E6+DhDnGYbN6PZMC7tJSLj5bwCI=" >> /opt/codetv/backend/.env', 10)
r('echo "SESSION_DRIVER=file" >> /opt/codetv/backend/.env', 10)
print('\n=== UPDATED .ENV ===')
r('grep -E "APP_KEY|SESSION_DRIVER" /opt/codetv/backend/.env', 10)
ssh.close()
