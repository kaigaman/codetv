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

print('=== MYSQL NETWORKS ===')
r("docker inspect codetv-mysql-1 --format '{{json .NetworkSettings.Networks}}' 2>&1", 10)
print('\n=== LARAVEL NETWORKS ===')
r("docker inspect codetv-laravel-1 --format '{{json .NetworkSettings.Networks}}' 2>&1", 10)
print('\n=== RESOLVE HOSTS ===')
r("docker exec codetv-laravel-1 sh -c 'getent hosts mysql' 2>&1", 10)
r("docker exec codetv-laravel-1 sh -c 'getent hosts redis' 2>&1", 10)
r("docker exec codetv-laravel-1 sh -c 'getent hosts codetv-mysql-1' 2>&1", 10)
print('\n=== MYSQL CONTAINER LOGS ===')
r('docker logs codetv-mysql-1 2>&1 | tail -10', 10)
print('\n=== LARAVEL LOGS ===')
r('docker logs codetv-laravel-1 2>&1 | tail -10', 10)
ssh.close()
