import paramiko, time

HOST = '66.212.18.106'; PORT = 22
USER = 'root'; PASSWORD = 'bC61sumTUP06JGp48o'

for attempt in range(20):
    try:
        print(f'Attempt {attempt+1}/20...')
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD,
                    look_for_keys=False, allow_agent=False, timeout=120, banner_timeout=120)
        print('CONNECTED!')
        
        def run(cmd, timeout=60):
            stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
            ec = stdout.channel.recv_exit_status()
            out = stdout.read().decode().strip()
            err = stderr.read().decode().strip()
            if out: print(out[:2000])
            if err: print(f'ERR: {err[:500]}')
            return ec, out

        # Kill any existing docker-compose process
        print('Killing stuck compose processes...')
        run("pkill -f 'docker compose' 2>/dev/null; pkill -f 'docker build' 2>/dev/null")
        time.sleep(5)
        
        # Check state
        print('\n=== STATE ===')
        run("docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
        run("free -m", 10)
        run("df -h /", 10)
        
        # Check if images were already pulled
        ec, out = run("docker images --format '{{.Repository}} {{.Size}}' 2>/dev/null", 10)
        has_images = len(out) > 10

        if not has_images:
            # Pull images ONE AT A TIME to avoid OOM
            print('\n=== PULLING IMAGES SEQUENTIALLY ===')
            images = [
                'redis:7-alpine',
                'mher/flower',
                'mysql:8.0',
                'ghcr.io/kpirnie/kptv-fast:latest',
                'guovern/iptv-api:latest',
            ]
            for img in images:
                print(f'  Pulling {img}...')
                ec, out = run(f'docker pull {img}', 600)
                if ec != 0:
                    print(f'  FAILED to pull {img}')
                else:
                    print(f'  DONE {img}')
                time.sleep(5)
        
        # Now run compose up (without --build since images are pulled)
        print('\n=== COMPOSE UP ===')
        run('cd /opt/codetv && docker compose up -d', 300)
        time.sleep(10)
        
        # Verify
        print('\n=== FINAL CHECK ===')
        run("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
        run("curl -s -o /dev/null -w 'HTTP %{http_code}\n' http://localhost --connect-timeout 15", 20)
        
        # Run sync
        ec, out = run("docker ps --format '{{.Names}}' | grep laravel", 10)
        if out:
            print('\n=== RUNNING SYNC ===')
            run('cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-global --sources=iptv-org', 600)
        
        ssh.close()
        break
    except Exception as e:
        print(f'  Failed: {str(e)[:100]}')
        time.sleep(30)
