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

print('=== DOMAINS DIR ===')
run('ls -la /etc/nginx/conf.d/domains/')
for f in run('ls /etc/nginx/conf.d/domains/', timeout=10)[1].split('\n'):
    if f:
        print(f'\n--- {f} ---')
        run(f'cat /etc/nginx/conf.d/domains/{f}')

print('\n=== CHECKING WHAT CODE5.ONLINE SERVES ===')
run('curl -s -o /dev/null -w "HTTP %{http_code}\n" http://code5.online --connect-timeout 10')
run('curl -s -o /dev/null -w "HTTP %{http_code}\n" https://code5.online --connect-timeout 10')
run('curl -s http://code5.online | head -20')

ssh.close()
print('\nDone')
