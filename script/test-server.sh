#!/bin/bash

# LiteWiki Test Server Script
# This script starts a PHP development server for testing

set -e  # Exit on any error

echo "🚀 LiteWiki Test Server"
echo "======================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}$1${NC}"
}

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install PHP 8.0 or higher."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_status "PHP version: $PHP_VERSION"

# Check PHP version
if ! php -r "exit(version_compare(PHP_VERSION, '8.0.0', '>=') ? 0 : 1);"; then
    print_error "PHP 8.0 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

# Check if SQLite extension is loaded
if ! php -r "exit(extension_loaded('sqlite3') ? 0 : 1);"; then
    print_error "SQLite3 extension is not loaded. Please install php-sqlite3."
    exit 1
fi

print_status "SQLite3 extension is available"

# Check if port 8000 is available
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    print_warning "Port 8000 is already in use. The server might not start properly."
    print_warning "You can try a different port: php -S localhost:8080 -t public/"
fi

print_header "Starting PHP development server..."
print_status "Server will be available at: http://localhost:8000"
print_status "Press Ctrl+C to stop the server"
print_warning "Remember to change the default admin password (admin/admin123) after first login!"

# Start PHP development server
php -S localhost:8000 -t public/

print_status "Server stopped. Goodbye! 👋"
