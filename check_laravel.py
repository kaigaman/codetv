import paramiko, time
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=30):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode().strip()
    err = e.read().decode().strip()
    if out: print(out[:2000])
    if err: print(err[:500])

print('=== ALL CONTAINERS ===')
r("docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
print('\n=== LARAVEL LOGS ===')
r("docker logs codetv-laravel-1 2>&1 | tail -30", 15)
print('\n=== PORT 8080 ===')
r("ss -tlnp | grep 8080 || echo '8080 FREE'", 10)

ssh.close()
