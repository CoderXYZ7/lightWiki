<?php
// Script to create example wiki pages

require_once "core/config.php";
require_once "core/db.php";
require_once "core/auth.php";
require_once "core/wiki.php";

$config = include "core/config.php";
$db = new Database($config["db_path"]);
$auth = new Auth();
$wiki = new Wiki();

// Login as admin
if (!$auth->login("admin", "admin123")) {
    die("Could not login as admin\n");
}

$pages = [
    "Home" => "

<div class='text-center margin-y-2'>
<h1 class='h1-large'>LightWiki</h1>
<p class='subtitle'>The free & open-source wiki with an AI assistant</p>
<p class='subtitle-small'>24h speed coding project</p>
<div style='font-size: 20px'><i class='fa-solid fa-bolt fa-2x' style='color: var(--text-light); padding-bottom: 4vw;'></i></div>


<div style='border-radius: 15px; overflow: hidden; width: 95%; position: relative;'>
    <iframe src='index3d.html' width='100%' height='500vw' frameborder='0'>
        Il tuo browser non supporta gli iframe.
    </iframe>
</div>





<h2><i class='fa-solid fa-square-up-right'></i> Explore LightWiki</h2>

<div class='grid-3'>

    <div class='card'>
    <h3 class='card-title-small'><i class='fas fa-search margin-right-half'></i>Search</h3>
    <p class='card-text-small'>Find articles, images, and more with our powerful search engine.</p>
    <a href='/?action=search' class='link-primary'>Search LightWiki →</a>
    </div>

    <div class='card'>
    <h3 class='card-title-small'><i class='fas fa-list margin-right-half'></i>All pages</h3>
    <p class='card-text-small'>Browse the complete list of all articles in LightWiki.</p>
    <a href='/?action=list' class='link-primary'>Browse articles →</a>
    </div>

    <div class='card'>
    <h3 class='card-title-small'><i class='fas fa-code margin-right-half'></i>API</h3>
    <p class='card-text-small'>Integrate with LightWiki using our REST API for external applications.</p>
    <a href='/?action=view&page=API+Documentation' class='link-primary'>View documentation →</a>
    </div>

</div>

<div class='footer-section'>
    <p class='footer-text'>LightWiki a innovational way to visualize datas and share knowledge</p>
    <div class='footer-links'>
        <a href='/?action=view&page=System+Architecture' class='link-light'>About LightWiki</a>
        <a href='/?action=view&page=Markdown+Guide' class='link-light'>Help</a>
        <a href='/?action=view&page=API+Documentation' class='link-light'>Github</a>
        <a href='/?action=list' class='link-light'>All pages</a>
    </div>
</div>",

    "Getting Started" => "# 🚀 Getting Started with LightWiki

Welcome to your new LightWiki! This lightweight wiki framework is designed to be fast, portable, and extensible.

## 📋 Quick Start

1. **Login**: Use the admin credentials (admin/admin123)
2. **Create Pages**: Click \"Create Page\" in the navigation
3. **Edit Content**: Use Markdown syntax for formatting
4. **Explore Features**: Try search, revisions, and API

## ✨ Key Features

- **Markdown Support**: Full GitHub Flavored Markdown
- **Code Highlighting**: Syntax highlighting for 100+ languages
- **Mermaid Diagrams**: Flowcharts, sequence diagrams, and more
- **Revision History**: Track all changes with full history
- **Search**: Fast full-text search across all pages
- **REST API**: External integrations and automation
- **Responsive Design**: Works great on all devices

## 🎯 What You Can Do

### Create Rich Content
- Write documentation
- Create tutorials
- Build knowledge bases
- Share code examples
- Design system architectures

### Collaborate
- Multiple users can edit
- Revision history tracks changes
- API allows external tools
- Search finds content quickly

## 📖 Next Steps

1. [Create your first page](http://localhost:8000/?action=create)
2. [Explore existing pages](http://localhost:8000/?action=list)
3. [Try the search feature](http://localhost:8000/?action=search)
4. [Check the API documentation](API Documentation)
5. [Learn about Markdown formatting](Markdown Guide)
6. [Understand the system architecture](System Architecture)

---

*Happy wiki-ing! 📚*",

    "Markdown Guide" => "# 📝 Markdown Guide

LightWiki supports full **GitHub Flavored Markdown** with additional features like syntax highlighting and diagrams.

## 🎨 Text Formatting

### Headers
```markdown
# H1 Header
## H2 Header
### H3 Header
#### H4 Header
```

### Text Styles
```markdown
**Bold text** or __bold text__
*Italic text* or _italic text_
***Bold and italic***
~~Strikethrough~~
```

### Lists
```markdown
- Unordered list item
- Another item
  - Nested item

1. Ordered list item
2. Another ordered item
   1. Nested ordered item
```

### Links and Images
```markdown
[Link text](https://example.com)
![Alt text](image.jpg)
```

### Code
```markdown
Inline `code` in text

```javascript
// Code block with syntax highlighting
function hello() {
    console.log('Hello, LightWiki!');
}
```
```

### Tables
| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| Row 1    | Data     | More     |
| Row 2    | Info     | Content  |

### Blockquotes
> This is a blockquote
>
> It can span multiple lines

### Horizontal Rules
---

## 🎯 LightWiki Extensions

### Mermaid Diagrams
```mermaid
graph TD
    A[Start] --> B{Decision}
    B -->|Yes| C[Action 1]
    B -->|No| D[Action 2]
    C --> E[End]
    D --> E
```

### Task Lists
- [x] Completed task
- [ ] Pending task
- [x] Another completed task

## 🔧 Tips

- Use `Ctrl+Enter` to save (when editing)
- Search works across all page content
- Revision history shows all changes
- API allows external integrations

---

*For more advanced features, check the [API Documentation](API)*",

    "API Documentation" => "# <i class='fas fa-code'></i> LightWiki API Documentation

LightWiki provides a comprehensive REST API for external integrations and automation.

## <i class='fas fa-key'></i> Authentication

All API requests require authentication using an API key in the `Authorization` header:

```
Authorization: Bearer your-api-key-here
```

**Default API Key**: `your-api-key-here` (change this in `core/config.php`)

**Note**: The API automatically creates a default user account (`api`/`api_password`) for API access if it doesn't exist.

## <i class='fas fa-rocket'></i> Endpoints

### GET /api.php?action=view&page={page_title}

Retrieve a specific page with full content and metadata.

**Parameters:**
- `page` (required): The title of the page to retrieve

**Example:**
```bash
curl -H \"Authorization: Bearer your-api-key-here\" \
     \"http://localhost:8000/api.php?action=view&page=Home\"
```

**Response (200):**
Returns JSON with page title, content, rendered HTML, author, and timestamps.

**Error Responses:**
- `400`: Missing page parameter
- `401`: Invalid API key
- `404`: Page not found

### GET /api.php?action=list&limit={n}&offset={n}

List all pages with pagination support.

**Parameters:**
- `limit` (optional): Number of pages to return (default: 50, max: 100)
- `offset` (optional): Number of pages to skip (default: 0)

**Example:**
```bash
curl -H \"Authorization: Bearer your-api-key-here\" \
     \"http://localhost:8000/api.php?action=list&limit=10&offset=0\"
```

**Response (200):**
Returns JSON array of pages with id, title, author, and timestamps.

### GET /api.php?action=search&q={query}

Search pages by content or title with advanced filtering.

**Parameters:**
- `q` (required): Search query string
- Additional filters can be applied via POST data or query parameters

**Example:**
```bash
curl -H \"Authorization: Bearer your-api-key-here\" \
     \"http://localhost:8000/api.php?action=search&q=markdown\"
```

**Response (200):**
Returns JSON array of search results with page details.

### POST /api.php?action=create

Create a new wiki page.

**Headers:**
- `Content-Type: application/json`

**Body (JSON):**
```json
{
  \"title\": \"New Page Title\",
  \"content\": \"# New Page Content\\n\\nThis is the content of the new page.\"
}
```

**Example:**
```bash
curl -X POST \
  -H \"Authorization: Bearer your-api-key-here\" \
  -H \"Content-Type: application/json\" \
  -d '{\"title\": \"My New Page\", \"content\": \"# Hello World\\n\\nThis is my new page.\"}' \
  \"http://localhost:8000/api.php?action=create\"
```

**Response (201):**
```json
{
  \"message\": \"Page created\",
  \"page_id\": 123
}
```

**Error Responses:**
- `400`: Missing title or content
- `401`: Authentication failed

### PUT /api.php?action=edit&page={page_title}

Update an existing wiki page.

**Parameters:**
- `page` (required): Title of the page to update

**Headers:**
- `Content-Type: application/json`

**Body (JSON):**
```json
{
  \"content\": \"# Updated Content\\n\\nThis content has been updated.\"
}
```

**Example:**
```bash
curl -X PUT \
  -H \"Authorization: Bearer your-api-key-here\" \
  -H \"Content-Type: application/json\" \
  -d '{\"content\": \"# Updated Page\\n\\nThis page has been updated.\"}' \
  \"http://localhost:8000/api.php?action=edit&page=My+Page\"
```

**Response (200):**
```json
{
  \"message\": \"Page updated\"
}
```

### DELETE /api.php?action=delete&page={page_title}

Delete an existing wiki page.

**Parameters:**
- `page` (required): Title of the page to delete

**Example:**
```bash
curl -X DELETE \
  -H \"Authorization: Bearer your-api-key-here\" \
  \"http://localhost:8000/api.php?action=delete&page=Old+Page\"
```

**Response (200):**
```json
{
  \"message\": \"Page deleted\"
}
```

## <i class='fas fa-info-circle'></i> Response Codes

- `200`: Success
- `201`: Created (for POST requests)
- `400`: Bad Request (missing parameters)
- `401`: Unauthorized (invalid API key)
- `404`: Not Found (page doesn't exist)
- `405`: Method Not Allowed
- `500`: Internal Server Error

## <i class='fas fa-code'></i> Integration Examples

### JavaScript/Node.js
```javascript
const API_KEY = 'your-api-key-here';
const BASE_URL = 'http://localhost:8000/api.php';

async function getPage(title) {
    const response = await fetch(BASE_URL + '?action=view&page=' + encodeURIComponent(title), {
        headers: {
            'Authorization': 'Bearer ' + API_KEY
        }
    });
    return response.json();
}

async function createPage(title, content) {
    const response = await fetch(BASE_URL + '?action=create', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + API_KEY,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ title, content })
    });
    return response.json();
}
```

### Python
```python
import requests
import json

API_KEY = 'your-api-key-here'
BASE_URL = 'http://localhost:8000/api.php'
HEADERS = {
    'Authorization': f'Bearer {API_KEY}',
    'Content-Type': 'application/json'
}

def get_page(title):
    response = requests.get(f'{BASE_URL}?action=view&page={title}', headers=HEADERS)
    return response.json()

def create_page(title, content):
    data = {'title': title, 'content': content}
    response = requests.post(f'{BASE_URL}?action=create',
                           headers=HEADERS,
                           data=json.dumps(data))
    return response.json()

def update_page(title, content):
    data = {'content': content}
    response = requests.put(f'{BASE_URL}?action=edit&page={title}',
                          headers=HEADERS,
                          data=json.dumps(data))
    return response.json()
```

### PHP
```php
<?php
$apiKey = 'your-api-key-here';
$baseUrl = 'http://localhost:8000/api.php';

function apiRequest($endpoint, $method = 'GET', $data = null) {
    $url = $baseUrl . $endpoint;
    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Usage examples
$page = apiRequest('?action=view&page=Home');
$newPage = apiRequest('?action=create', 'POST', [
    'title' => 'New Page',
    'content' => '# Hello World'
]);
?>
```

## <i class='fas fa-shield-alt'></i> Security & Best Practices

### Authentication
- Always use HTTPS in production
- Rotate API keys regularly
- Store API keys securely (environment variables, not in code)
- Use different API keys for different applications

### Rate Limiting
- Implement rate limiting on your server
- Consider API usage quotas
- Monitor API usage patterns

### Data Validation
- Validate all input data on both client and server
- Sanitize content before storing
- Use prepared statements (handled automatically by LightWiki)

### CORS Configuration
The API includes CORS headers for cross-origin requests:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization`

### Error Handling
- Always check response status codes
- Handle network errors gracefully
- Implement retry logic for transient failures
- Log API errors for debugging

## <i class='fas fa-tools'></i> API Features

- **CORS Support**: Cross-origin requests enabled
- **JSON Format**: All responses in JSON format
- **UTF-8 Encoding**: Full Unicode support
- **Automatic User Creation**: API creates default user if needed
- **Input Validation**: All inputs validated and sanitized
- **Error Logging**: Comprehensive error logging
- **Session Management**: Automatic session handling

---

*For more examples and advanced usage, check the [Getting Started](Getting Started) guide",

    "System Architecture" => "# 🏗️ LightWiki System Architecture

## 📊 High-Level Overview

```mermaid
graph TB
    A[Web Browser] --> B[PHP Server]
    B --> C[LightWiki Core]
    C --> D[(SQLite Database)]
    C --> E[Markdown Processor]
    C --> F[Authentication System]

    B --> G[REST API]
    G --> C

    E --> H[Mermaid.js]
    E --> I[Prism.js]
```

## 🏛️ Core Components

### 1. Web Interface (`public/`)
- **Entry Point**: `index.php` - Main application router
- **API Endpoint**: `api.php` - REST API handler
- **Assets**: CSS, JavaScript, and static files
- **Templates**: HTML structure and layout

### 2. Core Logic (`core/`)
- **Database Layer**: `db.php` - SQLite connection and queries
- **Authentication**: `auth.php` - User management and sessions
- **Wiki Engine**: `wiki.php` - Page operations and business logic
- **Markdown Processor**: `markdown.php` - Content rendering
- **Configuration**: `config.php` - System settings

### 3. Data Storage (`storage/`)
- **Database**: `litewiki.db` - SQLite database file
- **Schema**: Users, pages, revisions tables
- **FTS**: Full-text search indexes

## 🔄 Request Flow

```mermaid
sequenceDiagram
    participant U as User
    participant W as Web Server
    participant R as Router
    participant A as Auth
    participant C as Controller
    participant D as Database

    U->>W: HTTP Request
    W->>R: Route Request
    R->>A: Check Authentication
    A->>D: Validate User
    D-->>A: User Data
    A-->>R: Auth Result
    R->>C: Execute Action
    C->>D: Database Query
    D-->>C: Data Result
    C-->>R: Render Response
    R-->>W: HTML Response
    W-->>U: Web Page
```

## 📁 File Structure

```
litewiki/
├── core/                 # Core PHP classes
│   ├── config.php       # Configuration settings
│   ├── db.php          # Database abstraction
│   ├── auth.php        # Authentication system
│   ├── wiki.php        # Wiki business logic
│   └── markdown.php    # Content processing
├── public/              # Web interface
│   ├── index.php       # Main entry point
│   ├── api.php         # REST API
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── assets/         # Static files
├── storage/             # Data storage
│   └── litewiki.db     # SQLite database
├── templates/           # HTML templates
│   ├── header.php      # Page header
│   └── footer.php      # Page footer
├── vendor/              # Composer dependencies
├── setup.php           # Installation script
├── test-server.sh      # Development server
├── git-init.sh         # Git initialization
└── automated-test.sh   # Testing script
```

## 🗄️ Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Pages Table
```sql
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT UNIQUE NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    author_id INTEGER,
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

### Revisions Table
```sql
CREATE TABLE revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    content TEXT,
    author_id INTEGER,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

## 🔧 Key Technologies

- **Backend**: PHP 8.0+ with SQLite
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Database**: SQLite 3 with FTS5
- **Markdown**: Parsedown library
- **Syntax Highlighting**: Prism.js
- **Diagrams**: Mermaid.js
- **Styling**: Modern CSS with CSS Variables
- **Architecture**: MVC-inspired structure

## 🚀 Performance Features

- **Lazy Loading**: Components loaded on demand
- **Caching**: Database connection pooling
- **Optimization**: Efficient SQL queries
- **Compression**: Gzip compression for responses
- **CDN**: External libraries served from CDN

## 🔒 Security Features

- **Input Validation**: All user input sanitized
- **CSRF Protection**: Tokens for form submissions
- **Session Management**: Secure PHP sessions
- **API Authentication**: Bearer token validation
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Content escaping

---

*For development setup, see [Getting Started](Getting Started)*",
];

$created = 0;
$errors = 0;

foreach ($pages as $title => $content) {
    // Try to update first, if it fails then create
    $result = $wiki->updatePage($title, $content);
    if ($result["success"]) {
        echo "✅ Updated page: $title\n";
        $created++;
    } else {
        // Page doesn't exist, try to create it
        $result = $wiki->createPage($title, $content);
        if ($result["success"]) {
            echo "✅ Created page: $title\n";
            $created++;
        } else {
            echo "❌ Failed to create/update page: $title - " .
                $result["message"] .
                "\n";
            $errors++;
        }
    }
}

echo "\n📊 Summary:\n";
echo "Created: $created pages\n";
echo "Errors: $errors\n";
echo "\n🎉 Example wiki pages created successfully!\n";

?>
