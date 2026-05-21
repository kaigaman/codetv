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

print('=== FIX LISTEN ===')
r("""sed -i 's/listen 443 ssl;/listen 66.212.18.106:443 ssl;/' /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf""", 10)
print('\n=== VERIFY ===')
r('grep listen /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf', 10)
print('\n=== TEST CONFIG ===')
r('nginx -t 2>&1', 10)
print('\n=== RELOAD ===')
r('systemctl reload nginx 2>&1', 10)
print('\n=== TEST HTTPS ===')
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 10)
print('\n=== CERT CHECK ===')
r("curl -skv https://mamboleo.online 2>&1 | grep 'subject:'", 10)
ssh.close()
