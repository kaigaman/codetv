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
    if clean: print(clean[:5000])

print('=== NGINX -T FULL ===')
r("nginx -T 2>&1 | grep -A 2 'server_name' | head -40", 10)
print('\n=== CLOUD MPESA SSL CONFIG ===')
r('cat /etc/nginx/conf.d/domains/cloud.mpesa.online.ssl.conf', 10)
print('\n=== LIST FILES ===')
r("ls -la /etc/nginx/conf.d/domains/*.conf | head -20", 10)
ssh.close()
