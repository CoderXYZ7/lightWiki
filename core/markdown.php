<?php
// LiteWiki Markdown Processing with Extensions

class MarkdownProcessor {
    private $config;
    /** @intelephense-ignore-next-line */
    /** @var Parsedown|null $parsedown */
    private $parsedown;

    public function __construct() {
        $configData = include __DIR__ . '/config.php';
        $this->config = $configData;
        $this->initializeParsedown();
    }

    private function initializeParsedown() {
        // Load Composer autoloader if available
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        // Use Parsedown if available, otherwise fallback to basic processing
        if (class_exists('Parsedown')) {
            $this->parsedown = new Parsedown();
            $this->configureParsedown();
        }
    }

    private function configureParsedown() {
        if ($this->config['markdown_extensions']['extra']) {
            $this->parsedown->setBreaksEnabled(true);
            $this->parsedown->setMarkupEscaped(false); // Allow HTML tags
            $this->parsedown->setUrlsLinked(true);
        }
    }

    public function process($markdown) {
        if (!$markdown) return '';

        // Process special blocks BEFORE markdown conversion to prevent HTML encoding
        $markdown = $this->processMermaid($markdown);
        $markdown = $this->processCodeBlocks($markdown);

        // Process internal wiki links
        $markdown = $this->processInternalLinks($markdown);

        // Convert Markdown to HTML
        $html = $this->convertToHtml($markdown);

        // Process footnotes if enabled
        if ($this->config['markdown_extensions']['footnotes']) {
            $html = $this->processFootnotes($html);
        }

        // Process task lists if enabled
        if ($this->config['markdown_extensions']['task_lists']) {
            $html = $this->processTaskLists($html);
        }

        return $html;
    }

    private function processMermaid($markdown) {
        // Replace Mermaid code blocks with div containers
        $pattern = '/```mermaid\s*\n(.*?)\n```/s';
        $replacement = '<div class="mermaid">$1</div>';
        return preg_replace($pattern, $replacement, $markdown);
    }

    private function processCodeBlocks($markdown) {
        // Add language classes for syntax highlighting
        $pattern = '/```(\w+)?\s*\n(.*?)\n```/s';
        $replacement = '<pre><code class="language-$1">$2</code></pre>';
        return preg_replace($pattern, $replacement, $markdown);
    }

    private function processInternalLinks($markdown) {
        // Convert internal wiki links to proper URLs
        // Pattern matches [text](url) where url doesn't start with http://, https://, ftp://, etc.
        $pattern = '/\[([^\]]+)\]\(([^)]+)\)/';
        $markdown = preg_replace_callback($pattern, function($matches) {
            $text = $matches[1];
            $url = $matches[2];

            // Check if it's an external link (has protocol)
            if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//', $url)) {
                // External link, leave as is
                return $matches[0];
            }

            // Check if it's an action URL (starts with /?action=)
            if (preg_match('/^\/\?action=/', $url)) {
                // Action URL, leave as is
                return $matches[0];
            }

            // Internal wiki link, convert to wiki URL
            return '[' . $text . '](/?action=view&page=' . urlencode($url) . ')';
        }, $markdown);

        return $markdown;
    }

    private function convertToHtml($markdown) {
        if ($this->parsedown) {
            return $this->parsedown->text($markdown);
        }

        // Basic fallback Markdown processing
        return $this->basicMarkdown($markdown);
    }

    private function basicMarkdown($markdown) {
        // Basic Markdown to HTML conversion
        $html = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');

        // Headers
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);

        // Bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // Lists
        $html = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);

        // Paragraphs
        $html = '<p>' . preg_replace('/\n\n/', '</p><p>', $html) . '</p>';

        return $html;
    }

    private function processFootnotes($html) {
        // Basic footnote processing (simplified)
        $footnotePattern = '/\[\^(\d+)\]:\s*(.*)/';
        $referencePattern = '/\[\^(\d+)\]/';

        $footnotes = [];
        preg_match_all($footnotePattern, $html, $matches);
        foreach ($matches[1] as $i => $num) {
            $footnotes[$num] = $matches[2][$i];
        }

        $html = preg_replace($footnotePattern, '', $html);

        if (!empty($footnotes)) {
            $html .= '<div class="footnotes"><hr>';
            foreach ($footnotes as $num => $text) {
                $html .= "<p>[^$num]: $text</p>";
            }
            $html .= '</div>';
        }

        return $html;
    }

    private function processTaskLists($html) {
        // Convert - [ ] and - [x] to checkboxes
        $html = preg_replace('/- \[ \] (.*)/', '<li><input type="checkbox"> $1</li>', $html);
        $html = preg_replace('/- \[x\] (.*)/', '<li><input type="checkbox" checked> $1</li>', $html);
        return $html;
    }

    public function getMermaidConfig() {
        return $this->config['mermaid_config'];
    }
}
?>
