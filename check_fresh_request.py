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

print('=== MAKE FRESH REQUEST ===')
r("curl -sk https://mamboleo.online 2>&1 | head -5", 10)
print('\n=== NGINX ERROR LOG (last 5 lines) ===')
r('tail -5 /var/log/nginx/error.log 2>&1', 10)
print('\n=== NGINX ACCESS LOG (last 5 lines) ===')
r('tail -5 /var/log/nginx/access.log 2>&1', 10)
ssh.close()
