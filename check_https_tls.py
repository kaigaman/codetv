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

print('=== TEST 8443 ===')
r("curl -s -o /dev/null -w 'Port 8443: HTTP %{http_code}\n' --connect-timeout 5 https://127.0.0.1:8443 2>&1", 10)
print('\n=== DIRECT 8080 WITH HOST HEADER ===')
r("curl -s -o /dev/null -w 'Direct 8080+mamboleo: HTTP %{http_code}\n' -H 'Host: mamboleo.online' http://127.0.0.1:8080/", 10)
print('\n=== TLS DEBUG ===')
r("echo | openssl s_client -connect 127.0.0.1:443 -servername mamboleo.online 2>&1 | grep -E 'subject=|issuer=|return code'", 10)
print('\n=== LISTEN 443 DETAIL ===')
r("ss -tlnp 'sport = :443'", 10)
ssh.close()
