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
    if clean: print(clean[:3000])

print('=== LARAVEL CONTAINER LOGS ===')
r('docker logs codetv-laravel-1 2>&1 | tail -20', 15)
print('\n=== ALL CONTAINERS ===')
r("docker ps -a --format '{{.Names}} {{.Status}}' | grep laravel", 10)
print('\n=== RESTART ATTEMPT ===')
r('docker start codetv-laravel-1 2>&1', 15)
r('sleep 2; docker logs codetv-laravel-1 2>&1 | tail -20', 15)
ssh.close()
