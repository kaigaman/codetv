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

print('=== FULL NGINX CONF (check main error_log) ===')
r('cat /etc/nginx/nginx.conf', 10)
print('\n=== CHECK ALL ERROR LOGS ===')
r("find /var/log/nginx -name '*.log' -type f 2>/dev/null | head -20", 10)
print('\n=== RECENT ALL ERRORS ===')
r("for f in /var/log/nginx/*.log; do echo '---' $f; tail -1 $f; done 2>&1", 10)
ssh.close()
