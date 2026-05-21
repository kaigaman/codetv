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

print('=== NGINX PROCESSES ===')
r('ps aux | grep nginx | grep -v grep', 10)
print('\n=== RELOAD IN DETAIL ===')
r('systemctl reload nginx 2>&1; echo EXIT: $?', 10)
print('\n=== RESTART && TEST ===')
r('systemctl restart nginx 2>&1; sleep 1; curl -skv https://mamboleo.online 2>&1 | grep -E "subject:|HTTP/"', 10)
ssh.close()
