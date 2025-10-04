<?php
// LiteWiki Core Wiki Logic

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/markdown.php';

class Wiki {
    private $db;
    private $auth;
    private $markdown;

    public function __construct() {
        $config = include __DIR__ . '/config.php';
        $this->db = new Database($config['db_path']);
        $this->auth = new Auth();
        $this->markdown = new MarkdownProcessor();
    }

    public function createPage($title, $content, $tags = [], $authors = [], $discoverable = true) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $user = $this->auth->getCurrentUser();

        try {
            $pageId = $this->db->insert('pages', [
                'title' => $title,
                'content' => $content,
                'created_by' => $user['id'],
                'discoverable' => $discoverable ? 1 : 0
            ]);

            // Create initial revision
            $this->createRevision($pageId, $content, $user['id']);

            // Add authors if provided
            if (!empty($authors)) {
                foreach ($authors as $authorName) {
                    $authorName = trim($authorName);
                    if (!empty($authorName)) {
                        $this->db->addAuthorToPage($pageId, $authorName);
                    }
                }
            }

            // Add tags if provided
            if (!empty($tags)) {
                foreach ($tags as $tagName) {
                    $tagName = trim($tagName);
                    if (!empty($tagName)) {
                        $this->db->addTagToPage($pageId, $tagName);
                    }
                }
            }

            return ['success' => true, 'page_id' => $pageId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Page title already exists'];
        }
    }

    public function getPage($title): ?array {
        $page = $this->db->fetch("SELECT p.*, u.username as created_by FROM pages p LEFT JOIN users u ON p.created_by = u.id WHERE p.title = ?", [$title]);

        if ($page) {
            $page['rendered_content'] = $this->markdown->process($page['content']);
            $page['authors'] = $this->getAuthorsForPage($page['id']);
            return $page;
        }

        return null;
    }

    public function getPageById($id): ?array {
        $page = $this->db->fetch("SELECT p.*, u.username as created_by FROM pages p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ?", [$id]);

        if ($page) {
            $page['rendered_content'] = $this->markdown->process($page['content']);
            $page['authors'] = $this->getAuthorsForPage($page['id']);
            return $page;
        }

        return null;
    }

    public function updatePage($title, $content) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $user = $this->auth->getCurrentUser();
        $page = $this->getPage($title);

        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        // Create revision before updating
        $this->createRevision($page['id'], $page['content'], $user['id']);

        $this->db->update('pages', [
            'content' => $content,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'title = ?', ['title' => $title]);

        return ['success' => true];
    }

    public function deletePage($title) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $page = $this->getPage($title);
        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        // Delete revisions first
        $this->db->delete('revisions', 'page_id = ?', [$page['id']]);

        // Delete page
        $this->db->delete('pages', 'title = ?', [$title]);

        return ['success' => true];
    }

    public function listPages($limit = 50, $offset = 0) {
        $sql = "SELECT p.title, p.updated_at, GROUP_CONCAT(a.name) as authors
                FROM pages p
                LEFT JOIN page_authors pa ON p.id = pa.page_id
                LEFT JOIN authors a ON pa.author_id = a.id
                WHERE p.discoverable = 1
                GROUP BY p.id
                ORDER BY p.updated_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function searchPages($query = '', $filters = []) {
        return $this->db->search($query, $filters);
    }

    private function createRevision($pageId, $content, $authorId) {
        $this->db->insert('revisions', [
            'page_id' => $pageId,
            'content' => $content,
            'author_id' => $authorId
        ]);
    }

    public function getRevisions($title) {
        $page = $this->getPage($title);
        if (!$page) return [];

        return $this->db->fetchAll("SELECT r.*, u.username as author FROM revisions r LEFT JOIN users u ON r.author_id = u.id WHERE r.page_id = ? ORDER BY r.timestamp DESC", [$page['id']]);
    }

    public function getRevision($revisionId) {
        $revision = $this->db->fetch("SELECT r.*, p.title, u.username as author FROM revisions r JOIN pages p ON r.page_id = p.id LEFT JOIN users u ON r.author_id = u.id WHERE r.id = ?", [$revisionId]);

        if ($revision) {
            $revision['rendered_content'] = $this->markdown->process($revision['content']);
        }

        return $revision;
    }

    public function restoreRevision($revisionId) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $revision = $this->getRevision($revisionId);
        if (!$revision) {
            return ['success' => false, 'message' => 'Revision not found'];
        }

        $user = $this->auth->getCurrentUser();

        // Create a new revision with current content before restoring
        $currentPage = $this->getPage($revision['title']);
        $this->createRevision($currentPage['id'], $currentPage['content'], $user['id']);

        // Update page with revision content
        $this->db->update('pages', [
            'content' => $revision['content'],
            'updated_at' => date('Y-m-d H:i:s')
        ], 'title = ?', ['title' => $revision['title']]);

        return ['success' => true];
    }

    public function getAllPages() {
        return $this->db->fetchAll("SELECT title FROM pages ORDER BY title");
    }

    public function getAllAuthors() {
        return $this->db->getAllAuthors();
    }

    // Author management methods
    public function getAuthorsForPage($pageId) {
        return $this->db->getAuthorsForPage($pageId);
    }

    public function getAuthorsForPageByTitle($title) {
        $page = $this->getPage($title);
        if (!$page) return [];

        return $this->getAuthorsForPage($page['id']);
    }

    public function updatePageAuthors($title, $authors) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $page = $this->getPage($title);
        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        // Remove all existing authors
        $existingAuthors = $this->getAuthorsForPage($page['id']);
        foreach ($existingAuthors as $author) {
            $this->db->removeAuthorFromPage($page['id'], $author['id']);
        }

        // Add new authors
        if (!empty($authors)) {
            foreach ($authors as $authorName) {
                $authorName = trim($authorName);
                if (!empty($authorName)) {
                    $this->db->addAuthorToPage($page['id'], $authorName);
                }
            }
        }

        return ['success' => true];
    }

    public function updatePageDiscoverable($title, $discoverable) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $page = $this->getPage($title);
        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        $this->db->updatePageDiscoverable($page['id'], $discoverable);
        return ['success' => true];
    }

    // Tag management methods
    public function getTagsForPage($pageId) {
        return $this->db->getTagsForPage($pageId);
    }

    public function getTagsForPageByTitle($title) {
        $page = $this->getPage($title);
        if (!$page) return [];

        return $this->getTagsForPage($page['id']);
    }

    public function updatePageTags($title, $tags) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $page = $this->getPage($title);
        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        // Remove all existing tags
        $existingTags = $this->getTagsForPage($page['id']);
        foreach ($existingTags as $tag) {
            $this->db->removeTagFromPage($page['id'], $tag['id']);
        }

        // Add new tags
        if (!empty($tags)) {
            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if (!empty($tagName)) {
                    $this->db->addTagToPage($page['id'], $tagName);
                }
            }
        }

        return ['success' => true];
    }

    public function getAllTags() {
        return $this->db->getAllTags();
    }

    public function getPagesWithTag($tagName) {
        return $this->db->getPagesWithTag($tagName);
    }

    public function getPageWithTags($title) {
        $page = $this->getPage($title);
        if ($page) {
            $page['tags'] = $this->getTagsForPage($page['id']);
        }
        return $page;
    }

    public function getPageByIdWithTags($id) {
        $page = $this->getPageById($id);
        if ($page) {
            $page['tags'] = $this->getTagsForPage($page['id']);
        }
        return $page;
    }
}
?>
