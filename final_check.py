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

print('=== GIT STATUS ===')
r('cd /opt/codetv && git status --short 2>&1', 10)
print('\n=== SITE VERIFICATION ===')
r("curl -sk -o /dev/null -w 'Home: HTTP %{http_code} | ' https://mamboleo.online/", 10)
r("curl -sk -o /dev/null -w 'Sports: HTTP %{http_code} | ' https://mamboleo.online/sports", 10)
r("curl -sk -o /dev/null -w 'Countries: HTTP %{http_code}\n' https://mamboleo.online/countries", 10)
print('\n=== CHANNELS STATS ===')
r("docker exec codetv-mysql-1 mysql -ucodetv -pcodetv_pass codetv -e 'SELECT source, COUNT(*) as cnt FROM channels GROUP BY source;' 2>/dev/null", 10)
print('\n=== NGINX CONFIG CHANGE ===')
r('cat /etc/nginx/conf.d/domains/mamboleo.online.ssl.conf', 10)
ssh.close()
