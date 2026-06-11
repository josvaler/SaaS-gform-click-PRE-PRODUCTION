# Ubuntu Installation & Setup Guide

## Prerequisites Installation

### PHP 8.4 with Required Extensions

```bash
# Update package list
sudo apt-get update

# Install PHP 8.4 and required extensions
sudo apt-get install -y php8.4 php8.4-cli php8.4-curl php8.4-mysql php8.4-mbstring php8.4-openssl php8.4-xml php8.4-zip

# Verify installation
php -v
php -m | grep -E 'curl|pdo_mysql|mbstring|openssl|json'
```

### Composer Installation

```bash
# If not already installed
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verify
composer --version
```

### Node.js and npm Installation

```bash
# Install Node.js 18.x (LTS)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify
node -v
npm -v
```

### MariaDB/MySQL Installation

```bash
# Install MariaDB
sudo apt-get install -y mariadb-server mariadb-client

# Secure installation
sudo mysql_secure_installation

# Start and enable service
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Verify
sudo systemctl status mariadb
```

## Project Setup

### 1. Clone/Extract Project

```bash
cd /var/www/html
# Extract project files here
```

### 2. Set Permissions

```bash
# Fix ownership (if needed)
sudo chown -R www-data:www-data /var/www/html

# Add user to www-data group
sudo usermod -aG www-data $USER
# Log out and log back in for group changes to take effect

# Set directory permissions
sudo chmod -R 755 /var/www/html
sudo chmod -R 775 /var/www/html/uploads /var/www/html/processed /var/www/html/transformed /var/www/html/cache
```

### 3. Create Required Directories

```bash
cd /var/www/html
sudo mkdir -p uploads processed transformed cache/avatars public/processed public/transformed
sudo chown -R www-data:www-data uploads processed transformed cache public/processed public/transformed
sudo chmod -R 775 uploads processed transformed cache public/processed public/transformed
```

### 4. Install Dependencies

```bash
cd /var/www/html

# PHP dependencies
composer install --no-interaction

# Node.js dependencies
npm install
```

### 5. Database Setup

```bash
# Create database
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS background_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT
EOF

# Import schema
mysql -u root -p background_saas < database/schema.sql

# Apply migration (if needed)
mysql -u root -p background_saas < database/migration_add_operation_type.sql
```

### 6. Environment Configuration

Create `.env` file:

```bash
cd /var/www/html
cp .env.example .env
# Edit .env with your actual values
nano .env
```

### 7. Start Development Server

```bash
cd /var/www/html
php -S localhost:3000 -t public
```

## Web Server Configuration

### Apache Configuration

If using Apache, ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Create virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
```

## Troubleshooting

### Permission Issues

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html

# Fix permissions
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/html/uploads /var/www/html/processed
```

### PHP Extensions Not Loading

```bash
# Check if extensions are installed
dpkg -l | grep php8.4

# Restart PHP-FPM (if using)
sudo systemctl restart php8.4-fpm

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

### Database Connection Issues

```bash
# Test MySQL connection
mysql -u root -p -e "SELECT VERSION();"

# Check if database exists
mysql -u root -p -e "SHOW DATABASES;"

# Grant privileges (if needed)
mysql -u root -p << EOF
GRANT ALL PRIVILEGES ON background_saas.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EXIT
EOF
```

### Composer Issues

```bash
# Clear Composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor/ composer.lock
composer install --no-interaction
```

### npm Issues

```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules/ package-lock.json
npm install
```

## Security Considerations

1. **File Permissions**: Ensure sensitive files (`.env`, `config/*.php`) have restricted permissions:
   ```bash
   sudo chmod 600 /var/www/html/.env
   sudo chmod 600 /var/www/html/config/*.php
   ```

2. **Database Security**: Use strong passwords and avoid using `root` user in production.

3. **Environment Variables**: Never commit `.env` file to version control.

4. **HTTPS**: Use SSL/TLS certificates in production.

## Local Pre-Production Setup

For local development:

1. Use PHP built-in server: `php -S localhost:3000 -t public`
2. Use ngrok for webhook testing: `ngrok http 3000`
3. Configure Google OAuth with local redirect URI
4. Use Stripe test keys for development

## Next Steps

Once the project is stable:
1. Create GitHub repository
2. Set up CI/CD pipeline
3. Configure production environment
4. Set up monitoring and logging
