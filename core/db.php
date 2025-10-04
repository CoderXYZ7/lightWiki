<?php
// LiteWiki Database Connection and Basic Operations

class Database
{
    private $pdo;
    private $dbPath;

    public function __construct($dbPath = __DIR__ . "/../storage/litewiki.db")
    {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->initializeSchema();
        $this->initializeTagsSchema();
    }

    private function connect()
    {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC,
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    private function initializeSchema()
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT UNIQUE NOT NULL,
                content TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                author_id INTEGER,
                embedding BLOB,
                FOREIGN KEY (author_id) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS revisions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                page_id INTEGER NOT NULL,
                content TEXT,
                author_id INTEGER,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (page_id) REFERENCES pages(id),
                FOREIGN KEY (author_id) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS star (
                user_id INTEGER NOT NULL,
                page_id INTEGER NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (page_id) REFERENCES pages(id),
                PRIMARY KEY (user_id, page_id)
            );

            CREATE VIRTUAL TABLE IF NOT EXISTS pages_fts USING fts5(title, content);
        ");
    }

    private function initializeTagsSchema()
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS page_tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                page_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
                UNIQUE(page_id, tag_id)
            );
        ");
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    // Basic query methods
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $columns = array_keys($data);
        $setParts = [];
        foreach ($columns as $i => $col) {
            $setParts[] = "$col = ?";
        }
        $set = implode(", ", $setParts);
        $sql = "UPDATE $table SET $set WHERE $where";
        $params = array_merge(array_values($data), array_values($whereParams));
        $this->query($sql, $params);
    }

    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $params);
    }

    // Advanced search with filters
    public function search($query = "", $filters = [])
    {
        $params = [];
        $whereConditions = [];

        // Base query
        $sql = "SELECT DISTINCT p.id, p.title, p.content, p.updated_at, u.username as author
                FROM pages p
                LEFT JOIN users u ON p.author_id = u.id";

        // Add tag filtering if specified
        if (!empty($filters["tags"])) {
            $sql .= " JOIN page_tags pt ON p.id = pt.page_id
                     JOIN tags t ON pt.tag_id = t.id";
            $tagPlaceholders =
                str_repeat("?,", count($filters["tags"]) - 1) . "?";
            $whereConditions[] = "t.name IN ($tagPlaceholders)";
            $params = array_merge($params, $filters["tags"]);
        }

        // Add date filtering
        if (!empty($filters["date_from"])) {
            $whereConditions[] = "p.updated_at >= ?";
            $params[] = $filters["date_from"];
        }
        if (!empty($filters["date_to"])) {
            $whereConditions[] = "p.updated_at <= ?";
            $params[] = $filters["date_to"];
        }

        // Add author filtering
        if (!empty($filters["author"])) {
            $whereConditions[] = "u.username = ?";
            $params[] = $filters["author"];
        }

        // Add text search
        if (!empty($query)) {
            $searchTerm = "%" . $query . "%";
            $whereConditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Build WHERE clause
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $sql .= " ORDER BY p.updated_at DESC";

        return $this->fetchAll($sql, $params);
    }

    // Legacy search method for backward compatibility
    public function searchLegacy($query)
    {
        return $this->search($query);
    }

    // Tag management methods
    public function getTagsForPage($pageId)
    {
        $sql = "SELECT t.id, t.name
                FROM tags t
                JOIN page_tags pt ON t.id = pt.tag_id
                WHERE pt.page_id = ?
                ORDER BY t.name";
        return $this->fetchAll($sql, [$pageId]);
    }

    public function addTagToPage($pageId, $tagName)
    {
        // First, ensure the tag exists
        $tagId = $this->getOrCreateTag($tagName);

        // Then add the relationship (ignore if it already exists due to UNIQUE constraint)
        try {
            $this->insert("page_tags", [
                "page_id" => $pageId,
                "tag_id" => $tagId,
            ]);
        } catch (PDOException $e) {
            // Ignore duplicate key errors
            if ($e->getCode() != 23000) {
                throw $e;
            }
        }
    }

    public function removeTagFromPage($pageId, $tagId)
    {
        $this->delete("page_tags", "page_id = ? AND tag_id = ?", [
            $pageId,
            $tagId,
        ]);
    }

    public function getOrCreateTag($tagName)
    {
        // Try to find existing tag
        $existing = $this->fetch("SELECT id FROM tags WHERE name = ?", [
            $tagName,
        ]);
        if ($existing) {
            return $existing["id"];
        }

        // Create new tag
        return $this->insert("tags", ["name" => $tagName]);
    }

    public function getAllTags()
    {
        return $this->fetchAll("SELECT id, name FROM tags ORDER BY name");
    }

    public function searchPagesByTags($tagNames)
    {
        if (empty($tagNames)) {
            return [];
        }

        $placeholders = str_repeat("?,", count($tagNames) - 1) . "?";
        $sql = "SELECT DISTINCT p.id, p.title, p.content, p.updated_at, u.username as author
                FROM pages p
                LEFT JOIN users u ON p.author_id = u.id
                JOIN page_tags pt ON p.id = pt.page_id
                JOIN tags t ON pt.tag_id = t.id
                WHERE t.name IN ($placeholders)
                ORDER BY p.updated_at DESC";

        return $this->fetchAll($sql, $tagNames);
    }

    public function getPagesWithTag($tagName)
    {
        $sql = "SELECT p.id, p.title, p.content, p.updated_at, u.username as author
                FROM pages p
                LEFT JOIN users u ON p.author_id = u.id
                JOIN page_tags pt ON p.id = pt.page_id
                JOIN tags t ON pt.tag_id = t.id
                WHERE t.name = ?
                ORDER BY p.updated_at DESC";

        return $this->fetchAll($sql, [$tagName]);
    }
}
?>
