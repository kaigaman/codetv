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

print('=== DIRECT LARAVEL ===')
r("curl -s -o /dev/null -w 'Direct 8080: HTTP %{http_code}\n' http://127.0.0.1:8080/", 10)
print('\n=== VERBOSE FULL HTTPS ===')
r("curl -skv https://mamboleo.online 2>&1 | grep -E '< HTTP|< Location|error|refused|upstream'", 10)
print('\n=== NGINX CONF TEST FOR MAMBOLEO ===')
r("nginx -T 2>&1 | grep -A 5 'server_name mamboleo'", 10)
print('\n=== TRY HTTP PORT 80 ===')
r("curl -s -o /dev/null -w 'HTTP 80: HTTP %{http_code}\n' http://mamboleo.online/", 10)
ssh.close()
