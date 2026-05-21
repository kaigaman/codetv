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

print('=== CHECK PORT 3306 ===')
r("ss -tlnp | grep 3306", 10)
print('\n=== KILL HOST MYSQL ===')
r('systemctl stop mysql 2>&1; systemctl stop mysqld 2>&1; pkill -9 mysql 2>&1; sleep 1; ss -tlnp | grep 3306', 10)
print('\n=== START MYSQL CONTAINER ===')
r('cd /opt/codetv && docker compose up -d mysql 2>&1', 30)
print('\n=== CHECK CONTAINERS ===')
r("docker ps --format '{{.Names}} {{.Status}}' | head -10", 10)
print('\n=== RESTART LARAVEL ===')
r('cd /opt/codetv && docker compose up -d laravel 2>&1', 30)
r('sleep 3; docker ps --format "{{.Names}} {{.Status}}" | grep laravel', 10)
print('\n=== TEST DB ===')
r("docker exec codetv-laravel-1 php -r \"echo gethostbyname('mysql') . PHP_EOL;\" 2>&1", 10)
ssh.close()
