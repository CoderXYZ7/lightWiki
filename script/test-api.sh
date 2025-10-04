#!/bin/bash

# Test LiteWiki API - Create a second page

echo "Starting LiteWiki test server..."
php -S localhost:8001 -t public/ &
SERVER_PID=$!

# Wait for server to start
sleep 2

echo "Testing API page creation..."

# Create a second test page via API
curl -X POST http://localhost:8001/api.php?action=create \
  -H "Authorization: Bearer test-api-key" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "title=API Test Page" \
  -d "content=# API Test Page

This page was created via the LiteWiki API!

## Features Tested
- API authentication
- Page creation endpoint
- Markdown rendering

## Code Example
\`\`\`javascript
console.log('API test successful!');
\`\`\`

## Mermaid Diagram
\`\`\`mermaid
graph TD
    A[API Request] --> B{Authentication}
    B -->|Valid| C[Create Page]
    B -->|Invalid| D[Error Response]
    C --> E[Success]
\`\`\`"

echo -e "\nAPI test completed."

# Stop the server
kill $SERVER_PID

echo "Server stopped."
