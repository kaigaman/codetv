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
    if err:
        clean_err = err.encode('ascii', errors='replace').decode()
        if clean_err: print(f'ERR: {clean_err[:300]}')

print('=== RESTART NGINX ===')
r('systemctl restart nginx 2>&1 && sleep 1 && systemctl status nginx --no-pager 2>&1 | head -10', 10)
print('\n=== CHECK PORTS ===')
r("ss -tlnp | grep -E '443|8080'", 10)
print('\n=== TEST HTTPS ===')
r("curl -sk -o /dev/null -w 'HTTPS: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 10)
print('\n=== CERT CHECK ===')
r("curl -skv https://mamboleo.online 2>&1 | grep 'subject:'", 10)
ssh.close()
