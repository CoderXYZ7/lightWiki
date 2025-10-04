# LiteWiki

LiteWiki is a lightweight wiki framework built with **PHP**, **SQLite**, **HTML**, **CSS**, and **JavaScript**.
It is designed to be fast, portable, and extensible — with support for **extended Markdown**, **Mermaid diagrams**, and a **REST-like API** for external automation.

---

## 📚 Table of Contents

- [✨ Features](#-features)
- [🚀 Installation](#-installation)
- [📂 Project Structure](#-project-structure)
- [🖼️ Image Support](#️-image-support)
- [🔌 API](#-api)
- [📝 Markdown Extensions](#-markdown-extensions)
- [🔧 Configuration](#-configuration)
- [📜 License](#-license)
- [🌱 Roadmap](#-roadmap)
- [🤝 Contributing](#-contributing)

---

## ✨ Features

- **Extended Markdown** support (tables, footnotes, syntax highlighting).
- **Mermaid.js integration** for diagrams and flowcharts.
- **SQLite backend** (no external DB required).
- **REST API** for creating, editing, and deleting pages externally.
- Page history and revision control.
- Full-text search.
- User authentication and roles.
- Mobile-friendly interface.
- Plugin and theme system.

---

## 🚀 Installation

1. Clone this repository:

    ```bash
    git clone https://github.com/yourusername/litewiki.git
    cd litewiki
    ```

2. Ensure you have **PHP 8+** and **SQLite3** installed.

3. Point your web server (Apache/Nginx) to the `public/` folder.

4. Run the setup script:

    ```bash
    php setup.php
    ```

5. Open your browser at:

    ```bash
    http://localhost/litewiki
    ```

---

## 📂 Project Structure

```bash
litewiki/
├── public/           # Public web root
│   ├── index.php     # Entry point
│   ├── api.php       # API endpoint
│   ├── css/          # Styles
│   ├── js/           # Scripts
│   └── assets/       # Uploaded files, diagrams, etc.
├── core/             # Core PHP logic
│   ├── db.php        # SQLite connection
│   ├── markdown.php  # Extended Markdown renderer
│   ├── auth.php      # Authentication system
│   └── wiki.php      # Page logic
├── storage/          # SQLite database & uploads
├── templates/        # HTML templates
├── setup.php         # Initial installer
└── README.md
```

---

## 🖼️ Image Support

While direct image uploads are on the roadmap, you can still embed images in your wiki pages:

1. Place your image file (e.g., `my-image.png`) inside the `public/assets/` directory.
2. Embed it in your Markdown using the following syntax:

    ```markdown
    ![My Image Description](/assets/my-image.png)
    ```

---

## 🔌 API

LiteWiki exposes a simple **REST-like API** via `public/api.php`.
Requests require an **API key** (configured in `core/config.php`).

### Endpoints

- `POST /api.php?page=create` → Create a new page
- `POST /api.php?page=edit` → Edit an existing page
- `POST /api.php?page=delete` → Delete a page
- `GET  /api.php?page=view&id={page_id}` → Get page content
- `GET  /api.php?page=list` → List all pages

### Example (cURL)

```bash
curl -X POST http://localhost/litewiki/api.php?page=create \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d "title=My Page" \
  -d "content=# Hello World"
```

---

## 📝 Markdown Extensions

- GitHub-flavored Markdown
- Mermaid.js code blocks:

  ```mermaid
  graph TD
    A[Start] --> B{Is LiteWiki cool?}
    B -->|Yes| C[Use it!]
    B -->|No| D[Contribute!]
  ```

- Syntax highlighting for code
- Tables, checklists, footnotes

---

## 🔧 Configuration

- `core/config.php` → change site title, API key, theme, etc.

---

## 📜 License

MIT License

---

## 🌱 Roadmap

- [ ] WYSIWYG Markdown editor
- [ ] File and image uploads
- [ ] Access control per page
- [ ] Webhooks for external integrations
- [ ] Plugin API

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a pull request or open an issue.

NICE PROJECT PEZEEE
