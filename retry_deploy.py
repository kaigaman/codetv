import paramiko, time

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=30)

def run(cmd, timeout=120):
    print(f'$ {cmd}')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    exit_code = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out: print(out[:3000])
    if err: 
        show = err[:1000]
        if exit_code != 0: print(f'ERR: {show}')
        else: print(f'STDERR: {show}')
    return exit_code, out

print('=== CHECKING STATE ===')
run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' 2>/dev/null || echo 'docker not running'")
run("docker images --format '{{.Repository}} {{.Tag}} {{.Size}}' 2>/dev/null | head -10")
run("docker compose -f /opt/codetv/docker-compose.yml ps 2>&1 || echo 'compose check failed'")

# Check if compose up is still running
run("ps aux | grep 'docker compose up' | grep -v grep || echo 'no compose running'")

# Try starting compose again
print('\n=== STARTING DOCKER COMPOSE ===')
run("cd /opt/codetv && docker compose up -d --build 2>&1", timeout=600)

print('\n=== FINAL CHECK ===')
time.sleep(10)
run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", timeout=10)

ssh.close()
print('\nDone')
