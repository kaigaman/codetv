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
    if clean: print(clean[:5000])
    if err:
        clean_err = err.encode('ascii', errors='replace').decode()
        if clean_err: print(f'ERR: {clean_err[:500]}')

print('=== NGINX CONF INCLUDE ===')
r("grep -E 'include|conf\.d' /etc/nginx/nginx.conf", 10)
print('\n=== SYNTAX OF MAMBOLEO CONFIG ===')
r("nginx -c /etc/nginx/nginx.conf -T 2>&1 | grep -A 30 'server_name mamboleo' | head -40", 10)
print('\n=== ACTIVE LISTENERS ===')
r("ss -tlnp | grep -E '443|8080'", 10)
ssh.close()
