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

print('=== HTTPS TEST ===')
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 10)
print('\n=== HOME PAGE CONTENT ===')
r('curl -sk https://mamboleo.online 2>&1 | head -20', 10)
print('\n=== FIND CONTAINER WITH NGINX ===')
r("docker ps --format '{{.Names}} {{.Image}} {{.Ports}}' | grep -i nginx", 10)
print('\n=== CHECK PORT 443 ===')
r("ss -tlnp | grep 443", 10)
print('\n=== RUN SYNC ===')
r('docker exec codetv-laravel-1 php artisan iptv:sync-soccer --validate 2>&1', 600)
ssh.close()
