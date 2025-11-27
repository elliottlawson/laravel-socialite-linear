#!/bin/bash
set -euo pipefail

# SessionStart hook for Laravel development in Claude Code on the web
# This script sets up MariaDB and installs dependencies

# Only run in remote (web) environment
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

# Fix /tmp permissions for MariaDB (sandbox restriction)
chmod 1777 /tmp 2>/dev/null || true

# Install MariaDB if not present
if ! command -v mariadbd &> /dev/null; then
  apt-get update -qq
  apt-get install -y -qq mariadb-server
fi

# Initialize database if needed
if [ ! -d "/var/lib/mysql/mysql" ]; then
  mariadb-install-db --user=mysql --datadir=/var/lib/mysql 2>/dev/null
fi

# Start MariaDB if not running
if [ ! -S "/run/mysqld/mysqld.sock" ]; then
  mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
  /usr/bin/mariadbd-safe --datadir='/var/lib/mysql' &
  sleep 3
fi

# Create test database and user (idempotent)
mysql -u mysql -e "
CREATE DATABASE IF NOT EXISTS laravel_test;
CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON laravel_test.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
" 2>/dev/null || true

# Set environment variables for Laravel
if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
  cat >> "$CLAUDE_ENV_FILE" << 'ENVEOF'
export DB_CONNECTION=mysql
export DB_HOST=localhost
export DB_PORT=3306
export DB_DATABASE=laravel_test
export DB_USERNAME=laravel
export DB_PASSWORD=password
export DB_SOCKET=/run/mysqld/mysqld.sock
ENVEOF
fi

# Install PHP dependencies
cd "$CLAUDE_PROJECT_DIR"
composer install --no-interaction --quiet

exit 0
