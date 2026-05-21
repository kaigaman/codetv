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
    if out: print(out[:4000])
    if err and exit_code != 0: print(f'ERR: {err[:500]}')
    return exit_code, out

print('=== NGINX CONFIG ===')
run('ls -la /etc/nginx/conf.d/')
run('cat /etc/nginx/nginx.conf')
for f in ['code5.online.conf', 'codetv.conf', 'sacco.conf', 'default.conf']:
    run(f'cat /etc/nginx/conf.d/{f} 2>/dev/null || cat /etc/nginx/sites-available/{f} 2>/dev/null || echo "  -> no {f}"')

print('\n=== EXISTING APP ===')
run('cat /etc/nginx/conf.d/*.conf 2>/dev/null')
run('ss -tlnp | grep php || echo "no php listener"')
run('ls -la /opt/sacco/ 2>/dev/null || echo "no sacco"')

ssh.close()
print('\nDone')
