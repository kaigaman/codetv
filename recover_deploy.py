import paramiko, time

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

def connect(retries=10, wait=30):
    for i in range(retries):
        try:
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD,
                        look_for_keys=False, allow_agent=False, timeout=30)
            print(f'Connected (attempt {i+1})')
            return ssh
        except Exception as e:
            print(f'  attempt {i+1}/{retries} failed: {str(e)[:80]}')
            time.sleep(wait)
    raise Exception('Failed to connect')

def run(ssh, cmd, timeout=120):
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

ssh = connect()
print('=== CURRENT STATE ===')
run(ssh, "docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
run(ssh, "free -m", 10)
run(ssh, "docker images --format '{{.Repository}}:{{.Tag}} {{.Size}}' 2>/dev/null", 10)

# Check if compose is already running
ec, out = run(ssh, "ps aux | grep 'docker compose up' | grep -v grep || echo 'NO_COMPOSE'", 10)
if 'NO_COMPOSE' in out:
    print('\n=== NOT RUNNING - STARTING COMPOSE ===')
    # First add swap to help with memory
    run(ssh, "swapon --show; free -h", 10)
    
    # Start compose in background, capture output to file
    run(ssh, "cd /opt/codetv && nohup docker compose up -d --build > /tmp/compose.log 2>&1 &", 10)
    print('Compose started in background, waiting...')
    time.sleep(30)
    
    for i in range(20):
        ec, out = run(ssh, "tail -5 /tmp/compose.log 2>/dev/null", 10)
        ec2, out2 = run(ssh, "docker ps --format '{{.Names}} {{.Status}}' 2>/dev/null || echo 'EMPTY'", 10)
        if 'Up ' in out2:
            print(f'\nContainers UP after ~{i*15}s!')
            break
        time.sleep(15)
else:
    print('\n=== COMPOSE STILL RUNNING ===')
    run(ssh, "tail -20 /tmp/compose.log 2>/dev/null || echo 'no log'", 10)

print('\n=== FINAL STATE ===')
run(ssh, "docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
run(ssh, "curl -s -o /dev/null -w 'LOCAL: HTTP %{http_code}\n' http://localhost --connect-timeout 15", 20)

# Run sync if containers are running
ec, out = run(ssh, 'docker ps --format "{{.Names}}" 2>/dev/null | grep laravel || echo "NO_LARAVEL"', 10)
if 'laravel' in out:
    print('\n=== RUNNING GLOBAL SYNC ===')
    run(ssh, 'cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-global --sources=iptv-org', 600)
else:
    print(f'\nLaravel container not found. Output: {out[:200]}')

ssh.close()
