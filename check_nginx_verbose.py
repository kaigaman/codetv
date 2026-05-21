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
    if err and ec != 0:
        clean_err = err.encode('ascii', errors='replace').decode()
        print(f'ERR: {clean_err[:200]}')

print('=== CURL HTTPS VERBOSE ===')
r("curl -skv https://mamboleo.online 2>&1 | head -30", 10)
print('\n=== NGINX ERROR LOG ===')
r('tail -20 /var/log/nginx/error.log 2>&1', 10)
print('\n=== ALL ENABLED NGINX CONFIGS ===')
r('ls -la /etc/nginx/conf.d/domains/', 10)
ssh.close()
