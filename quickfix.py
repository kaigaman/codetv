import paramiko, time
H, P, U, PW = '66.212.18.106', 22, 'root', 'bC61sumTUP06JGp48o'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(H, port=P, username=U, password=PW, look_for_keys=False, allow_agent=False, timeout=30, banner_timeout=30)

def r(c, t=120):
    i,o,e = ssh.exec_command(c, timeout=t)
    ec = o.channel.recv_exit_status()
    out = o.read().decode().strip()
    err = e.read().decode().strip()
    if out: print(out[:1000])
    if err and ec != 0: print(f'ERR: {err[:200]}')

# Kill ONLY the things blocking Docker ports
r("kill -9 66568 2>/dev/null; systemctl stop redis 2>/dev/null; sleep 1", 10)
r('docker kill $(docker ps -q) 2>/dev/null; docker rm -f $(docker ps -aq) 2>/dev/null; sleep 2', 30)
r('cd /opt/codetv && docker compose up -d 2>&1', 600)
time.sleep(15)
r("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", 10)
r("curl -s -o /dev/null -w 'SITE: HTTP %{http_code}\n' --connect-timeout 10 https://mamboleo.online", 15)
ssh.close()
print('DONE')
