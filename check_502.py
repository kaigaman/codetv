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
    if clean: print(clean[:2000])
    if err and ec != 0:
        clean_err = err.encode('ascii', errors='replace').decode()
        print(f'ERR: {clean_err[:200]}')

print('=== PORT 8080 ===')
r('ss -tlnp | findstr 8080', 10)
print('\n=== DOCKER PORTS ===')
r("docker ps --format '{{.Names}} {{.Ports}}'", 10)
print('\n=== LARAVEL CONTAINER IP ===')
r('docker inspect -f "{{.Name}} {{.NetworkSettings.Networks.codetv_codetv.IPAddress}}" codetv-laravel-1 2>&1', 10)
print('\n=== TEST NGINX PROXY ===')
r("curl -s -o /dev/null -w 'Nginx proxy: HTTP %{http_code}\n' --connect-timeout 10 http://127.0.0.1:8080", 10)
ssh.close()
