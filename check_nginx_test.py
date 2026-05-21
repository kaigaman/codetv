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

print('=== NGINX TEST ===')
r('nginx -t 2>&1', 10)
print('\n=== FRESH HTTPS REQUEST ===')  
r("curl -skv https://mamboleo.online 2>&1 | grep -E 'subject:|HTTP/|SSL|error|refused'", 10)
print('\n=== NGINX ERROR (fresh) ===')
r('tail -5 /var/log/nginx/error.log', 10)
ssh.close()
