<?php
// Import script for papers_data.json into LiteWiki database
require_once 'core/db.php';

class PapersDataImporter
{
    private $db;
    private $systemUserId;
    private $systemAuthorId;

    public function __construct()
    {
        $this->db = new Database();
        $this->initializeSystemUser();
    }

    private function initializeSystemUser()
    {
        // Get or create System user
        $systemUser = $this->db->fetch("SELECT id FROM users WHERE username = ?", ["System"]);
        if (!$systemUser) {
            $this->systemUserId = $this->db->insert("users", [
                "username" => "System",
                "password_hash" => password_hash("system", PASSWORD_DEFAULT),
                "role" => "admin"
            ]);
        } else {
            $this->systemUserId = $systemUser["id"];
        }

        // Get or create System author
        $systemAuthor = $this->db->fetch("SELECT id FROM authors WHERE name = ?", ["System"]);
        if (!$systemAuthor) {
            $this->systemAuthorId = $this->db->insert("authors", ["name" => "System"]);
        } else {
            $this->systemAuthorId = $systemAuthor["id"];
        }
    }

    public function importFromJson($jsonFilePath)
    {
        if (!file_exists($jsonFilePath)) {
            throw new Exception("JSON file not found: $jsonFilePath");
        }

        $jsonContent = file_get_contents($jsonFilePath);
        $papers = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format: " . json_last_error_msg());
        }

        echo "Found " . count($papers) . " papers to import.\n";

        $importedCount = 0;
        $skippedCount = 0;

        foreach ($papers as $index => $paper) {
            try {
                $this->importPaper($paper);
                $importedCount++;
                echo "Imported: " . $paper["title"] . "\n";
            } catch (Exception $e) {
                $skippedCount++;
                echo "Skipped '{$paper["title"]}': " . $e->getMessage() . "\n";
            }

            // Progress indicator
            if (($index + 1) % 50 === 0) {
                echo "Progress: " . ($index + 1) . "/" . count($papers) . " papers processed\n";
            }
        }

        echo "\nImport completed:\n";
        echo "- Imported: $importedCount papers\n";
        echo "- Skipped: $skippedCount papers\n";
    }

    private function importPaper($paper)
    {
        // Check if paper already exists (by title)
        $existingPage = $this->db->fetch("SELECT id FROM pages WHERE title = ?", [$paper["title"]]);
        if ($existingPage) {
            throw new Exception("Paper with this title already exists");
        }

        // Prepare content - combine title and pagecontent
        $content = "# " . $paper["title"] . "\n\n";
        if (!empty($paper["pagecontent"])) {
            $content .= $paper["pagecontent"];
        }

        // Add URL as reference if available
        if (!empty($paper["page"]["url"])) {
            $content .= "\n\n---\n*Source: " . $paper["page"]["url"] . "*";
        }

        // Insert the page
        $pageId = $this->db->insert("pages", [
            "title" => $paper["title"],
            "content" => $content,
            "created_by" => $this->systemUserId,
            "discoverable" => 1,
            "created_at" => $paper["date"] . " 00:00:00",
            "updated_at" => $paper["date"] . " 00:00:00"
        ]);

        // Add System as author
        $this->db->addAuthorToPage($pageId, "System");

        // Add all tags
        if (!empty($paper["tags"]) && is_array($paper["tags"])) {
            foreach ($paper["tags"] as $tag) {
                if (!empty(trim($tag))) {
                    $this->db->addTagToPage($pageId, trim($tag));
                }
            }
        }

        // Create initial revision
        $this->db->insert("revisions", [
            "page_id" => $pageId,
            "content" => $content,
            "author_id" => $this->systemUserId,
            "timestamp" => $paper["date"] . " 00:00:00"
        ]);

        return $pageId;
    }
}

// Main execution
try {
    $importer = new PapersDataImporter();
    
    $jsonFilePath = 'lightWikiBackEnd/scraper/papers_data.json';
    echo "Starting import from: $jsonFilePath\n";
    
    $importer->importFromJson($jsonFilePath);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
