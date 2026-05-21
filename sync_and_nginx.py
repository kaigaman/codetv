import paramiko, sys
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=30):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode('utf-8', errors='replace')
    err = e.read().decode('utf-8', errors='replace')
    # strip non-ascii for Windows console
    clean = out.encode('ascii', errors='replace').decode()
    if clean: print(clean[:2000])
    if err and ec != 0:
        clean_err = err.encode('ascii', errors='replace').decode()
        print(f'ERR: {clean_err[:200]}')

print('=== SYNC ===')
r('docker exec codetv-laravel-1 php artisan iptv:sync-soccer --validate 2>&1', 600)
print('\n=== NGINX STATUS ===')
r('systemctl status nginx --no-pager 2>&1 | head -20', 10)
print('\n=== NGINX CONFIG ===')
r('cat /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf 2>&1', 10)
ssh.close()
