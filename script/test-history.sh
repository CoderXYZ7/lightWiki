#!/bin/bash

# Test LiteWiki History System

echo "Starting LiteWiki test server..."
php -S localhost:8002 -t public/ &
SERVER_PID=$!

# Wait for server to start
sleep 2

echo "Testing page history system..."

# Create a test page
echo "Creating test page..."
curl -X POST http://localhost:8002/api.php?action=create \
  -H "Authorization: Bearer test-api-key" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "title=History Test Page" \
  -d "content=# History Test Page

This is the initial version of the page."

echo -e "\nPage created."

# Edit the page multiple times to create revisions
echo "Creating revisions..."

curl -X POST http://localhost:8002/api.php?action=edit \
  -H "Authorization: Bearer test-api-key" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "page=History Test Page" \
  -d "content=# History Test Page

This is the second version of the page.

## Changes Made
- Added a heading
- Added some content"

echo -e "\nSecond revision created."

curl -X POST http://localhost:8002/api.php?action=edit \
  -H "Authorization: Bearer test-api-key" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "page=History Test Page" \
  -d "content=# History Test Page

This is the third version of the page.

## Changes Made
- Added a heading
- Added some content
- Added a list

### Features
- Revision tracking
- History viewing
- Restore functionality"

echo -e "\nThird revision created."

echo "Testing history retrieval..."
curl -s "http://localhost:8002/?action=view&page=History Test Page&revisions=1" | grep -o "Version [0-9]" | wc -l

echo -e "\nHistory system test completed."

# Stop the server
kill $SERVER_PID

echo "Server stopped."
