import paramiko

HOST = '66.212.18.106'
PORT = 22
USER = 'root'
PASSWORD = 'bC61sumTUP06JGp48o'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=30)

def run(cmd, timeout=30):
    print(f'$ {cmd}')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    exit_code = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out: print(out[:3000])
    if err and exit_code != 0: print(f'ERR: {err[:500]}')
    return exit_code, out

print('=== EXISTING CONFIG ===')
run('cat /etc/nginx/sites-enabled/* 2>/dev/null || echo "NO_SITES"')
run('cat /etc/nginx/conf.d/* 2>/dev/null || echo "NO_CONF"')
run('ls -la /etc/nginx/sites-available/ 2>/dev/null')
run('certbot certificates 2>&1 || echo "NO_CERTS"')
run('docker compose version 2>&1 || echo "NO_COMPOSE_V2"')
run('docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "NO_CONTAINERS"')
run('cat /opt/sacco/.env 2>/dev/null | head -5 || echo "NO_SACCO_ENV"')
run('systemctl list-units --type=service --state=running | grep -E "docker|nginx|php|sacco" || echo "NO_SERVICES"')

ssh.close()
print('\nDone')
