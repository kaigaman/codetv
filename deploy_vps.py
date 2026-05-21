import paramiko
import time
import sys
import os

HOST = "66.212.18.106"
PORT = 22
USER = "root"
PASSWORD = "bC61sumTUP06JGp48o"

def run(ssh, cmd, timeout=60, print_output=True):
    if print_output:
        print(f"\n$ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    exit_code = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out and print_output:
        print(out)
    if err and print_output:
        print(f"STDERR: {err}")
    return exit_code, out, err

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    print("Connecting to VPS...")
    ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, look_for_keys=False, allow_agent=False, timeout=30)
    print("Connected!")
    
    # Step 1: Check current state
    print("\n===== STEP 1: CHECKING SERVER STATE =====")
    run(ssh, "hostname")
    run(ssh, "lsb_release -a 2>/dev/null || cat /etc/os-release | head -3")
    run(ssh, "df -h /")
    run(ssh, "free -m")
    run(ssh, "ss -tlnp | grep -E ':(80|443|8080|8081|8082|3306|6379|8000|5555) ' || echo 'No services on those ports'")
    
    # Step 2: Check for existing web server / application
    print("\n===== STEP 2: CHECKING EXISTING APPLICATIONS =====")
    run(ssh, "which nginx && nginx -v 2>&1 || echo 'No nginx'")
    run(ssh, "which apache2 && apache2 -v 2>&1 || echo 'No apache'")
    run(ssh, "which httpd && httpd -v 2>&1 || echo 'No httpd'")
    run(ssh, "systemctl list-units --type=service --state=running | grep -E 'nginx|apache|httpd|caddy|docker' || echo 'No relevant services'")
    
    # Step 3: Check if Docker is installed
    print("\n===== STEP 3: CHECKING DOCKER =====")
    rc, docker_out, _ = run(ssh, "which docker && docker --version || echo 'DOCKER_NOT_FOUND'", print_output=True)
    docker_installed = "DOCKER_NOT_FOUND" not in docker_out
    
    rc, compose_out, _ = run(ssh, "which docker-compose || docker compose version 2>/dev/null || echo 'COMPOSE_NOT_FOUND'", print_output=True)
    compose_installed = "COMPOSE_NOT_FOUND" not in compose_out
    
    # Step 4: Remove existing web apps
    print("\n===== STEP 4: REMOVING EXISTING APPLICATIONS =====")
    existing = run(ssh, "ss -tlnp | grep -E ' :80 | :443 '", timeout=10)
    if existing[1]:
        print("Existing services on port 80/443 found. Removing...")
        # Kill any process on 80/443
        run(ssh, "fuser -k 80/tcp 2>/dev/null || true")
        run(ssh, "fuser -k 443/tcp 2>/dev/null || true")
        # Remove nginx/apache if present
        run(ssh, "apt-get remove -y nginx nginx-common apache2 httpd 2>/dev/null || true")
        run(ssh, "apt-get autoremove -y 2>/dev/null || true")
        # Clean any web root
        run(ssh, "rm -rf /var/www/html /var/www/codetv /etc/nginx/sites-enabled/* 2>/dev/null || true")
        print("Existing applications removed.")
    else:
        print("No existing services on port 80/443.")
    
    # Step 5: Install Docker if not present
    print("\n===== STEP 5: INSTALLING DOCKER =====")
    if not docker_installed:
        print("Installing Docker...")
        run(ssh, "curl -fsSL https://get.docker.com -o get-docker.sh", timeout=30)
        run(ssh, "sh get-docker.sh", timeout=180)
        run(ssh, "rm get-docker.sh")
        run(ssh, "systemctl enable docker && systemctl start docker")
        print("Docker installed.")
    else:
        print("Docker already installed.")
    
    # Step 6: Install certbot for SSL
    print("\n===== STEP 6: INSTALLING CERTBOT =====")
    run(ssh, "apt-get update -qq", timeout=60)
    run(ssh, "apt-get install -y certbot python3-certbot-nginx 2>/dev/null || apt-get install -y certbot 2>/dev/null || pip3 install certbot 2>/dev/null || echo 'certbot install attempted'", timeout=120)
    run(ssh, "which certbot && certbot --version 2>&1 || echo 'certbot not found via apt, trying snap'") 
    
    # Step 7: Clone the repository
    print("\n===== STEP 7: CLONING CODETV REPOSITORY =====")
    run(ssh, "cd /opt && git clone https://github.com/kaigaman/codetv.git 2>/dev/null || (cd /opt/codetv && git pull)", timeout=60)
    run(ssh, "ls -la /opt/codetv/docker-compose.yml")
    
    # Step 8: Create .env from example
    print("\n===== STEP 8: CONFIGURING ENVIRONMENT =====")
    run(ssh, "cd /opt/codetv && cp backend/.env.example backend/.env 2>/dev/null || true")
    run(ssh, "cd /opt/codetv/backend && php -r \"echo file_get_contents('.env.example');\" 2>/dev/null > .env || true")
    
    # Set production domain
    run(ssh, """cd /opt/codetv && cat > backend/.env << 'ENVEOF'
APP_NAME=CODETV
APP_ENV=production
APP_DEBUG=false
APP_URL=https://code5.online

DB_HOST=mysql
DB_DATABASE=codetv
DB_USERNAME=codetv
DB_PASSWORD=codetv_pass

REDIS_HOST=redis
PYTHON_API=http://python:8000
KPTV_FAST_API=http://kptv-fast:8080
IPTV_API_URL=http://iptv-api:8080

SANCTUM_STATEFUL_DOMAINS=code5.online
SESSION_DOMAIN=.code5.online
ENVEOF
""")
    print(".env configured for code5.online")
    
    # Step 9: Deploy with Docker Compose
    print("\n===== STEP 9: DEPLOYING DOCKER STACK =====")
    run(ssh, "cd /opt/codetv && docker compose up -d --build", timeout=300)
    
    # Step 10: Set up Nginx reverse proxy (on host) with certbot
    print("\n===== STEP 10: SETTING UP NGINX REVERSE PROXY + SSL =====")
    nginx_conf = """server {
    listen 80;
    server_name code5.online www.code5.online;
    
    location / {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
        proxy_cache_bypass $http_upgrade;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
"""
    # Install nginx as reverse proxy
    run(ssh, "apt-get install -y nginx", timeout=120)
    
    # Write nginx config
    transport = ssh.get_transport()
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    with sftp.open("/etc/nginx/sites-available/codetv", "w") as f:
        f.write(nginx_conf)
    
    run(ssh, "ln -sf /etc/nginx/sites-available/codetv /etc/nginx/sites-enabled/")
    run(ssh, "rm -f /etc/nginx/sites-enabled/default")
    run(ssh, "nginx -t && systemctl restart nginx")
    
    print("Nginx reverse proxy configured on port 80.")
    
    # Step 11: Get SSL certificate
    print("\n===== STEP 11: OBTAINING SSL CERTIFICATE =====")
    run(ssh, "certbot --nginx -d code5.online -d www.code5.online --non-interactive --agree-tos --email kaigaman@gmail.com --redirect || certbot --nginx -d code5.online -d www.code5.online --non-interactive --agree-tos --email kaigaman@gmail.com --redirect --no-certificate || echo 'Certbot failed, will retry manually'", timeout=120)
    
    # Step 12: Run sync
    print("\n===== STEP 12: RUNNING GLOBAL SYNC =====")
    # Delay to let services start
    print("Waiting 30s for services to stabilize...")
    time.sleep(30)
    run(ssh, "cd /opt/codetv && docker compose exec -T laravel php artisan iptv:sync-global --sources=iptv-org", timeout=600)
    
    # Final check
    print("\n===== DEPLOYMENT SUMMARY =====")
    run(ssh, "curl -s -o /dev/null -w 'HTTP %{http_code}' https://code5.online || curl -s -o /dev/null -w 'HTTP %{http_code}' http://code5.online")
    run(ssh, "docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", timeout=10)
    
    sftp.close()
    ssh.close()
    print("\n✅ Deployment complete!")

if __name__ == "__main__":
    main()
