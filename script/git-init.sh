#!/bin/bash

# LiteWiki Git Initialization Script
# This script sets up Git repository and version control

set -e  # Exit on any error

echo "🔧 LiteWiki Git Initialization"
echo "=============================="

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

# Check if Git is installed
if ! command -v git &> /dev/null; then
    print_error "Git is not installed. Please install Git first."
    exit 1
fi

GIT_VERSION=$(git --version | cut -d' ' -f3)
print_status "Git version: $GIT_VERSION"

# Initialize Git repository if not already done
if [ ! -d ".git" ]; then
    print_header "Initializing Git repository..."
    git init
    print_status "Git repository initialized"
else
    print_status "Git repository already exists"
fi

# Create .gitignore if it doesn't exist
if [ ! -f ".gitignore" ]; then
    print_status "Creating .gitignore file..."
    cat > .gitignore << 'EOF'
# LiteWiki .gitignore

# Database files
storage/*.db
storage/*.db-*

# Vendor dependencies
vendor/

# OS generated files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# IDE files
.vscode/
.idea/
*.swp
*.swo
*~

# Logs
*.log
logs/

# Temporary files
tmp/
temp/

# Composer
composer.lock

# Environment files
.env
.env.local
.env.*.local

# Backup files
*.bak
*.backup
*~

# Node modules (if any)
node_modules/
EOF
    print_status ".gitignore created"
else
    print_status ".gitignore already exists"
fi

# Add all files to Git
print_header "Adding files to Git..."
git add .
print_status "Files added to staging area"

# Check if there are any changes to commit
if git diff --cached --quiet; then
    print_warning "No changes to commit. All files are already tracked."
else
    # Initial commit
    if ! git log --oneline -n 1 &> /dev/null; then
        git commit -m "Initial commit: LiteWiki wiki framework

- Complete PHP-based wiki with SQLite backend
- Markdown support with Mermaid.js diagrams
- User authentication and role management
- REST API for external integrations
- Full-text search functionality
- Revision history and page management
- Responsive web interface"
        print_status "Initial commit created"
    else
        print_status "Changes committed to existing repository"
        git commit -m "Update: LiteWiki development files"
    fi
fi

# Show Git status
print_header "Git Status:"
git status --short

print_header "Repository Information:"
echo "Current branch: $(git branch --show-current)"
echo "Latest commit: $(git log --oneline -n 1)"

print_status "Git initialization complete! 🎉"
print_status "Use 'git status' to check repository status"
print_status "Use 'git log --oneline' to view commit history"
