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

print('=== DNS CHECK ===')
r('nslookup mamboleo.online 2>&1', 10)
print('\n=== CURL WITH --RESOLVE ===')
r("curl -sk --resolve mamboleo.online:443:127.0.0.1 https://mamboleo.online 2>&1 | head -5", 10)
print('\n=== CURL VERBOSE SHOWING SNI ===')
r("curl -skv https://mamboleo.online 2>&1 | grep -E 'subject:|Server:|TLS|SSL connection|ALPN|GET|Host'", 10)
print('\n=== OPENSSL CERT MATCH ===')
r("echo | openssl s_client -connect mamboleo.online:443 -servername mamboleo.online 2>&1 | grep -E 'subject='", 10)
ssh.close()
