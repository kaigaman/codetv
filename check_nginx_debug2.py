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

print('=== NGINX ERROR LOG (after fresh request) ===')
r("curl -sk https://mamboleo.online 2>/dev/null; tail -3 /var/log/nginx/error.log", 10)
print('\n=== CHECK NGINX MAIN ERROR LOG PATH ===')
r("grep -i error_log /etc/nginx/nginx.conf", 10)
print('\n=== FIREWALL ===')
r("iptables -L INPUT -n 2>&1 | head -20", 10)
print('\n=== CURL VIA NGINX HTTP ===')
r("curl -s -o /dev/null -w 'HTTP via nginx: HTTP %{http_code}\n' -H 'Host: mamboleo.online' http://127.0.0.1:80", 10)
ssh.close()
