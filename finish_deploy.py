import paramiko, time

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=30)

def run(cmd, timeout=60):
    print(f'$ {cmd}')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    exit_code = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out: print(out[:3000])
    if err and exit_code != 0: print(f'ERR: {err[:500]}')
    return exit_code, out

print('=== WAITING FOR IMAGES TO PULL ===')
for i in range(30):
    rc, out = run("docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null | head -10", timeout=10)
    if 'laravel' in out and 'Up' in out:
        print(f'\nContainers are up after {i*10}s!')
        break
    print(f'  waiting... ({i*10}s)')
    time.sleep(10)

print('\n=== CONTAINERS ===')
run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", timeout=10)

print('\n=== TESTING SITE ===')
run('curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost --connect-timeout 15', timeout=20)
run('curl -s http://localhost | head -5', timeout=15)

print('\n=== RUNNING GLOBAL SYNC ===')
run('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-global --sources=iptv-org', timeout=600)

print('\n=== FINAL TEST ===')
run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", timeout=10)
run('curl -s -o /dev/null -w "HTTPS: HTTP %{http_code}\n" --connect-timeout 15 http://localhost', timeout=20)

ssh.close()
print('\nDone')
