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

    public function createPage($title, $content, $tags = []) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $user = $this->auth->getCurrentUser();

        try {
            $pageId = $this->db->insert('pages', [
                'title' => $title,
                'content' => $content,
                'author_id' => $user['id']
            ]);

            // Create initial revision
            $this->createRevision($pageId, $content, $user['id']);

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
        $page = $this->db->fetch("SELECT p.*, u.username as author FROM pages p LEFT JOIN users u ON p.author_id = u.id WHERE p.title = ?", [$title]);

        if ($page) {
            $page['rendered_content'] = $this->markdown->process($page['content']);
            return $page;
        }

        return null;
    }

    public function getPageById($id): ?array {
        $page = $this->db->fetch("SELECT p.*, u.username as author FROM pages p LEFT JOIN users u ON p.author_id = u.id WHERE p.id = ?", [$id]);

        if ($page) {
            $page['rendered_content'] = $this->markdown->process($page['content']);
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
        return $this->db->fetchAll("SELECT p.title, p.updated_at, u.username as author FROM pages p LEFT JOIN users u ON p.author_id = u.id ORDER BY p.updated_at DESC LIMIT ? OFFSET ?", [$limit, $offset]);
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
        return $this->db->fetchAll("SELECT DISTINCT u.username FROM users u JOIN pages p ON u.id = p.author_id ORDER BY u.username");
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
