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
    if clean: print(clean[:2000])

print('=== TEST IPTV-ORG CONNECTIVITY ===')
r('docker exec codetv-laravel-1 curl -sI --connect-timeout 10 https://iptv-org.github.io/api/channels.json 2>&1 | head -5', 15)
print('\n=== DOWNLOAD SIZE ===')
r("docker exec codetv-laravel-1 curl -s --connect-timeout 10 'https://iptv-org.github.io/api/channels.json' 2>&1 | wc -c", 30)
print('\n=== SYNC SPORTS ONLY (skip curated) ===')
r('docker exec codetv-laravel-1 php artisan iptv:sync-soccer --sources=sports-m3u 2>&1', 120)
print('\n=== CHANNEL COUNT ===')
r("docker exec codetv-mysql-1 mysql -ucodetv -pcodetv_pass codetv -e 'SELECT COUNT(*) FROM channels;' 2>/dev/null", 10)
ssh.close()
