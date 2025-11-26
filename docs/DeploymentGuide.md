# Reel Stock Management System - Deployment Guide

## Prerequisites
- **Operating System**: Windows 10/11 or Ubuntu 20.04+
- **Software Requirements**:
  - PHP ≥ 8.1 with extensions: intl, mbstring, openssl, pdo_mysql, xml, ctype, json, tokenizer, bcmath
  - Composer (latest stable)
  - Node.js ≥ 18 and npm ≥ 9
  - MySQL 8 or MariaDB
  - Git
  - Web server: Apache/Nginx or XAMPP for Windows
- **Hardware**: 4GB RAM, 10GB disk space
- **Network**: Internet access for dependency downloads

## Step-by-Step Deployment

### 1. Download Source Code
```bash
git clone https://github.com/YourOrg/ReelStock.git
cd ReelStock
```

### 2. Environment Configuration
- Copy `.env.example` to `.env`
- Edit `.env` with database and app settings:
  ```
  APP_NAME="Reel Stock Management"
  APP_ENV=production
  APP_KEY=base64:your_generated_key
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=reelstock
  DB_USERNAME=db_user
  DB_PASSWORD=db_password
  ```
- Generate app key: `php artisan key:generate`

### 3. Install Dependencies
- Backend: `composer install --no-dev --optimize-autoloader`
- Frontend: `npm install --production`

### 4. Database Setup
- Create MySQL database `reelstock`
- Run migrations: `php artisan migrate`
- Seed data (if applicable): `php artisan db:seed`

### 5. Build Assets
- Production build: `npm run build`

### 6. Web Server Configuration
- **Apache**: Point document root to `public/` directory
- **Nginx**: Example config:
  ```
  server {
      listen 80;
      server_name yourdomain.com;
      root /path/to/ReelStock/public;
      index index.php;
      location / { try_files $uri $uri/ /index.php?$query_string; }
      location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; }
  }
  ```
- Enable `mod_rewrite` (Apache) or equivalent

### 7. Permissions & Security
- Set `storage/` and `bootstrap/cache/` to 755 permissions
- Configure SSL/TLS
- Set environment to production: `APP_ENV=production`

### 8. Testing & Optimization
- Run `php artisan optimize`
- Test login and basic functions
- Monitor logs in `storage/logs/`

### 9. Backup Strategy
- Schedule daily database dumps
- Backup application files weekly
- Store backups offsite
