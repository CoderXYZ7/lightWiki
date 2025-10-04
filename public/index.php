<?php
// LiteWiki Main Entry Point

require_once __DIR__ . "/../core/config.php";
require_once __DIR__ . "/../core/db.php";
require_once __DIR__ . "/../core/auth.php";
require_once __DIR__ . "/../core/markdown.php";
require_once __DIR__ . "/../core/wiki.php";

$config = include __DIR__ . "/../core/config.php";
$auth = new Auth();
$wiki = new Wiki();
$markdown = new MarkdownProcessor();

// Handle actions
$action = $_GET["action"] ?? "view";
$page = $_GET["page"] ?? "Home";

$message = "";
$messageType = "";

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    handlePost($action, $auth, $wiki, $message, $messageType);
}

// Include header
require_once __DIR__ . "/../templates/header.php";

// Route to appropriate action
switch ($action) {
    case "view":
        if (isset($_GET["revisions"])) {
            showRevisions($wiki, $auth, $page);
        } elseif (isset($_GET["revision"])) {
            showRevision($wiki, $_GET["revision"]);
        } else {
            showPage($wiki, $page);
        }
        break;
    case "edit":
        showEditForm($wiki, $auth, $page);
        break;
    case "create":
        showCreateForm($auth);
        break;
    case "list":
        showPageList($wiki);
        break;
    case "search":
        showSearchForm();
        break;
    case "login":
        showLoginForm();
        break;
    case "register":
        showRegisterForm();
        break;
    case "logout":
        $auth->logout();
        header("Location: /");
        exit();
    case "restore":
        handleRestore($wiki, $auth);
        break;
    case "switch_theme":
        handleThemeSwitch();
        break;
    default:
        showHome($wiki);
        break;
}

// Include footer
require_once __DIR__ . "/../templates/footer.php";

function handlePost($action, $auth, $wiki, &$message, &$messageType)
{
    if (
        !isset($_POST["csrf_token"]) ||
        !$auth->validateCSRFToken($_POST["csrf_token"])
    ) {
        $message = "Invalid request";
        $messageType = "error";
        return;
    }

    switch ($action) {
        case "login":
            $result = $auth->login($_POST["username"], $_POST["password"]);
            if ($result) {
                header("Location: /");
                exit();
            } else {
                $message = "Invalid username or password";
                $messageType = "error";
            }
            break;
        case "register":
            $result = $auth->register(
                $_POST["username"],
                $_POST["password"],
                $_POST["role"] ?? "user",
            );
            if ($result["success"]) {
                $message = "Registration successful! Please log in.";
                $messageType = "success";
            } else {
                $message = $result["message"];
                $messageType = "error";
            }
            break;
        case "create":
            if (!$auth->isLoggedIn()) {
                $message = "Authentication required";
                $messageType = "error";
                return;
            }
            $tags = isset($_POST["tags"]) ? explode(",", $_POST["tags"]) : [];
            $result = $wiki->createPage(
                $_POST["title"],
                $_POST["content"],
                $tags,
            );
            if ($result["success"]) {
                header(
                    "Location: /?action=view&page=" .
                        urlencode($_POST["title"]),
                );
                exit();
            } else {
                $message = $result["message"];
                $messageType = "error";
            }
            break;
        case "edit":
            if (!$auth->isLoggedIn()) {
                $message = "Authentication required";
                $messageType = "error";
                return;
            }
            $pageTitle = $_GET["page"] ?? "";
            $result = $wiki->updatePage($pageTitle, $_POST["content"]);
            if ($result["success"]) {
                // Handle tags update
                if (isset($_POST["tags"])) {
                    $tags = explode(",", $_POST["tags"]);
                    $wiki->updatePageTags($pageTitle, $tags);
                }
                header("Location: /?action=view&page=" . urlencode($pageTitle));
                exit();
            } else {
                $message = $result["message"];
                $messageType = "error";
            }
            break;
        case "search":
            // Search is handled in GET
            break;
    }
}

function showHome($wiki)
{
    global $config;
    $pageTitle = "Home";

    $recentPages = $wiki->listPages(10);

    echo "<h1>Welcome to " . htmlspecialchars($config["site_title"]) . "</h1>";
    echo "<p>" . htmlspecialchars($config["site_description"]) . "</p>";

    if (!empty($recentPages)) {
        echo "<h2>Recent Pages</h2>";
        echo '<ul class="page-list">
';
        foreach ($recentPages as $page) {
            echo "<li>";
            echo '<a href="/?action=view&page=' .
                urlencode($page["title"]) .
                '">' .
                htmlspecialchars($page["title"]) .
                "</a>";
            echo '<div class="page-meta">Updated ' .
                date("M j, Y", strtotime($page["updated_at"])) .
                " by " .
                htmlspecialchars($page["author"]) .
                "</div>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo '<p>No pages yet. <a href="/?action=create">Create the first page</a>!</p>';
    }
}

function showPage($wiki, $title)
{
    global $auth, $pageTitle;

    $page = $wiki->getPageWithTags($title);

    if (!$page) {
        $pageTitle = "Page Not Found";
        echo "<h1>Page Not Found</h1>";
        echo '<p>The page "' .
            htmlspecialchars($title) .
            '" does not exist.</p>';
        echo '<p><a href="/?action=create" class="btn">Create this page</a></p>';
        return;
    }

    $pageTitle = $page["title"];

    // Display tags if any
    if (!empty($page["tags"])) {
        echo '<div class="page-tags margin-bottom-1">';
        echo "<strong>Tags:</strong> ";
        $tagLinks = [];
        foreach ($page["tags"] as $tag) {
            $tagLinks[] =
                '<a href="' .
                createTagSearchUrl($tag["name"]) .
                '" class="tag-link-inline">' .
                htmlspecialchars($tag["name"]) .
                "</a>";
        }
        echo implode(" ", $tagLinks);
        echo "</div>";
    }

    echo '<div class="page-content">' . $page["rendered_content"] . "</div>";

    if ($auth->isLoggedIn()) {
        echo '<div class="page-actions">';
        echo '<a href="/?action=edit&page=' .
            urlencode($title) .
            '" class="btn">Edit Page</a>';
        echo '<a href="/?action=view&page=' .
            urlencode($title) .
            '&revisions=1" class="btn">View History</a>';
        echo '<a href="#" class="btn btn-danger" onclick="if(confirm(\'Delete this page?\')) window.location=\'?action=delete&page=' .
            urlencode($title) .
            '\'">Delete Page</a>';
        echo "</div>";
    }
}

function showEditForm($wiki, $auth, $title)
{
    global $pageTitle, $message, $messageType;

    if (!$auth->isLoggedIn()) {
        echo "<h1>Access Denied</h1>";
        echo "<p>You must be logged in to edit pages.</p>";
        return;
    }

    $page = $wiki->getPageWithTags($title);
    $pageTitle = "Edit: " . $title;

    // Get current tags as comma-separated string
    $currentTags = "";
    if ($page && isset($page["tags"])) {
        $tagNames = array_column($page["tags"], "name");
        $currentTags = implode(", ", $tagNames);
    }

    if ($message) {
        echo '<div class="message ' .
            $messageType .
            '">' .
            htmlspecialchars($message) .
            "</div>";
    }

    echo "<h1>Edit: " . htmlspecialchars($title) . "</h1>";
    echo '<form method="post">';
    echo '<input type="hidden" name="csrf_token" value="' .
        $auth->generateCSRFToken() .
        '">';
    echo '<div class="form-group">';
    echo '<label for="tags">Tags (comma-separated):</label>';
    echo '<input type="text" name="tags" id="tags" value="' .
        htmlspecialchars($currentTags) .
        '" placeholder="documentation, tutorial, guide">';
    echo '<small class="text-light">Separate multiple tags with commas</small>';
    echo "</div>";
    echo '<div class="form-group">';
    echo '<label for="content">Content (Markdown):</label>';
    echo '<textarea name="content" id="content" required>' .
        htmlspecialchars($page["content"] ?? "") .
        "</textarea>";
    echo "</div>";
    echo '<button type="submit" class="btn">Save Changes</button>';
    echo '<a href="/?action=view&page=' .
        urlencode($title) .
        '" class="btn">Cancel</a>';
    echo "</form>";
}

function showCreateForm($auth)
{
    global $pageTitle, $message, $messageType, $wiki;

    if (!$auth->isLoggedIn()) {
        echo "<h1>Access Denied</h1>";
        echo "<p>You must be logged in to create pages.</p>";
        return;
    }

    $pageTitle = "Create New Page";

    if ($message) {
        echo '<div class="message ' .
            $messageType .
            '">' .
            htmlspecialchars($message) .
            "</div>";
    }

    echo "<h1>Create New Page</h1>";
    echo '<form method="post">';
    echo '<input type="hidden" name="csrf_token" value="' .
        $auth->generateCSRFToken() .
        '">';
    echo '<div class="form-group">';
    echo '<label for="title">Page Title:</label>';
    echo '<input type="text" name="title" id="title" required>';
    echo "</div>";
    echo '<div class="form-group">';
    echo '<label for="tags">Tags (comma-separated):</label>';
    echo '<input type="text" name="tags" id="tags" placeholder="documentation, tutorial, guide">';
    echo '<small class="text-light">Separate multiple tags with commas</small>';
    echo "</div>";
    echo '<div class="form-group">';
    echo '<label for="content">Content (Markdown):</label>';
    echo '<textarea name="content" id="content" required></textarea>';
    echo "</div>";
    echo '<button type="submit" class="btn">Create Page</button>';
    echo "</form>";
}

function showPageList($wiki)
{
    global $pageTitle;

    $pageTitle = "All Pages";
    $pages = $wiki->getAllPages();

    echo "<h1>All Pages</h1>";

    if (empty($pages)) {
        echo "<p>No pages yet.</p>";
    } else {
        echo '<ul class="page-list">';
        foreach ($pages as $page) {
            echo '<li><a href="/?action=view&page=' .
                urlencode($page["title"]) .
                '">' .
                htmlspecialchars($page["title"]) .
                "</a></li>";
        }
        echo "</ul>";
    }
}

function showSearchForm()
{
    global $pageTitle, $wiki;

    $pageTitle = "Search";

    $query = $_GET["q"] ?? "";
    $selectedTags = isset($_GET["tags"])
        ? (is_array($_GET["tags"])
            ? $_GET["tags"]
            : [$_GET["tags"]])
        : [];
    $dateFrom = $_GET["date_from"] ?? "";
    $dateTo = $_GET["date_to"] ?? "";
    $author = $_GET["author"] ?? "";

    $results = [];
    $filters = [];

    // Build filters
    if (!empty($selectedTags)) {
        $filters["tags"] = $selectedTags;
    }
    if (!empty($dateFrom)) {
        $filters["date_from"] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $filters["date_to"] = $dateTo;
    }
    if (!empty($author)) {
        $filters["author"] = $author;
    }

    if ($query || !empty($filters)) {
        $results = $wiki->searchPages($query, $filters);
    }

    // Get all available tags and authors for filter dropdowns
    $allTags = $wiki->getAllTags();
    $allAuthors = $wiki->getAllAuthors();

    // Hero section with search
    echo '<div class="search-hero">';
    echo '<div class="search-hero-content">';
    echo '<h1><i class="fas fa-search"></i> Search Wiki Pages</h1>';
    echo '<p>Find exactly what you\'re looking for with advanced filters and full-text search</p>';

    echo '<div class="search-input-wrapper">';
    echo '<form method="get" id="quick-search-form" class="quick-search-form">';
    echo '<input type="hidden" name="action" value="search">';
    echo '<div class="search-input-group">';
    echo '<input type="text" name="q" id="quick-q" value="' .
        htmlspecialchars($query) .
        '" placeholder="Search pages..." class="search-input">';
    echo '<button type="submit" class="search-btn">Search</button>';
    echo "</div>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Advanced filters section
    echo '<div class="search-filters-section">';
    echo '<div class="container">';
    echo '<details class="filters-toggle">';
    echo '<summary class="filters-summary">';
    echo '<span class="filters-icon"><i class="fas fa-cog"></i></span>';
    echo "<span>Advanced Filters</span>";
    echo '<span class="filters-chevron">▼</span>';
    echo "</summary>";

    echo '<div class="filters-content">';
    echo '<form method="get" id="advanced-search-form" class="advanced-search-form">';
    echo '<input type="hidden" name="action" value="search">';

    echo '<div class="filters-grid">';

    // Search query
    echo '<div class="filter-card">';
    echo '<label for="adv-q" class="filter-label">Search Query</label>';
    echo '<input type="text" name="q" id="adv-q" value="' .
        htmlspecialchars($query) .
        '" placeholder="Keywords, phrases..." class="filter-input">';
    echo '<small class="filter-help">Search in titles and content</small>';
    echo "</div>";

    // Tags filter
    echo '<div class="filter-card">';
    echo '<label for="adv-tags" class="filter-label">Filter by Tags</label>';
    echo '<div class="tag-filter-container">';
    echo '<input type="text" class="tag-search-input" placeholder="Search tags..." data-filter="tags">';
    echo '<div class="tag-selector" id="tag-selector">';
    foreach ($allTags as $tag) {
        $checked = in_array($tag["name"], $selectedTags) ? "checked" : "";
        echo '<label class="tag-checkbox">';
        echo '<input type="checkbox" name="tags[]" value="' .
            htmlspecialchars($tag["name"]) .
            '" ' .
            $checked .
            ">";
        echo '<span class="tag-label">' .
            htmlspecialchars($tag["name"]) .
            "</span>";
        echo "</label>";
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Date filters
    echo '<div class="filter-card">';
    echo '<label class="filter-label">Date Range</label>';
    echo '<div class="date-inputs">';
    echo '<div class="date-input-group">';
    echo '<label for="date_from" class="date-label">From:</label>';
    echo '<input type="date" name="date_from" id="date_from" value="' .
        htmlspecialchars($dateFrom) .
        '" class="filter-input">';
    echo "</div>";
    echo '<div class="date-input-group">';
    echo '<label for="date_to" class="date-label">To:</label>';
    echo '<input type="date" name="date_to" id="date_to" value="' .
        htmlspecialchars($dateTo) .
        '" class="filter-input">';
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Author filter
    echo '<div class="filter-card">';
    echo '<label for="adv-author" class="filter-label">Filter by Author</label>';
    echo '<div class="author-filter-container">';
    echo '<input type="text" class="author-search-input" placeholder="Search authors..." data-filter="authors">';
    echo '<select name="author" id="adv-author" class="filter-select hidden" size="6">';
    echo '<option value="">All Authors</option>';
    foreach ($allAuthors as $authUser) {
        $selected = $author === $authUser["username"] ? "selected" : "";
        echo '<option value="' .
            htmlspecialchars($authUser["username"]) .
            '" ' .
            $selected .
            ">" .
            htmlspecialchars($authUser["username"]) .
            "</option>";
    }
    echo "</select>";
    echo '<div class="author-selector" id="author-selector">';
    $allOptionSelected = empty($author) ? "selected" : "";
    echo '<div class="author-option ' .
        $allOptionSelected .
        '" data-value="">All Authors</div>';
    foreach ($allAuthors as $authUser) {
        $selected = $author === $authUser["username"] ? "selected" : "";
        echo '<div class="author-option ' .
            $selected .
            '" data-value="' .
            htmlspecialchars($authUser["username"]) .
            '">' .
            htmlspecialchars($authUser["username"]) .
            "</div>";
    }
    echo "</div>";
    echo '<input type="hidden" name="author" id="selected-author" value="' .
        htmlspecialchars($author) .
        '">';
    echo "</div>";
    echo "</div>";

    echo "</div>";

    echo '<div class="filter-actions">';
    echo '<button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>';
    echo '<a href="/?action=search" class="btn btn-secondary"><i class="fas fa-redo"></i> Clear All</a>';
    echo "</div>";
    echo "</form>";
    echo "</div>";
    echo "</details>";
    echo "</div>";
    echo "</div>";

    // Active filters display
    $activeFilters = [];
    if ($query) {
        $activeFilters[] = [
            "type" => "query",
            "label" => "Query",
            "value" => "\"$query\"",
        ];
    }
    if (!empty($selectedTags)) {
        $activeFilters[] = [
            "type" => "tags",
            "label" => "Tags",
            "value" => implode(", ", $selectedTags),
        ];
    }
    if ($dateFrom) {
        $activeFilters[] = [
            "type" => "date_from",
            "label" => "From",
            "value" => $dateFrom,
        ];
    }
    if ($dateTo) {
        $activeFilters[] = [
            "type" => "date_to",
            "label" => "To",
            "value" => $dateTo,
        ];
    }
    if ($author) {
        $activeFilters[] = [
            "type" => "author",
            "label" => "Author",
            "value" => $author,
        ];
    }

    if (!empty($activeFilters)) {
        echo '<div class="active-filters">';
        echo '<div class="container">';
        echo "<h3>Active Filters</h3>";
        echo '<div class="filter-tags">';
        foreach ($activeFilters as $filter) {
            echo '<span class="filter-tag">';
            echo "<strong>" .
                htmlspecialchars($filter["label"]) .
                ":</strong> " .
                htmlspecialchars($filter["value"]);
            echo '<a href="' .
                removeFilterFromUrl($filter["type"]) .
                '" class="filter-remove" title="Remove this filter">×</a>';
            echo "</span>";
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    // Search results
    if (($query || !empty($filters)) && !empty($results)) {
        echo '<div class="search-results">';
        echo '<div class="container">';
        echo '<div class="results-header">';
        echo "<h2>Search Results</h2>";
        echo '<span class="results-count">' .
            count($results) .
            " pages found</span>";
        echo "</div>";

        echo '<div class="results-grid">';
        foreach ($results as $result) {
            echo '<div class="result-card">';
            echo '<div class="result-header">';
            echo '<h3><a href="/?action=view&page=' .
                urlencode($result["title"]) .
                '">' .
                htmlspecialchars($result["title"]) .
                "</a></h3>";
            echo '<div class="result-meta">';
            echo '<span class="result-author"><i class="fas fa-user"></i> ' .
                htmlspecialchars($result["author"]) .
                "</span>";
            echo '<span class="result-date"><i class="fas fa-calendar"></i> ' .
                date("M j, Y", strtotime($result["updated_at"])) .
                "</span>";
            echo "</div>";
            echo "</div>";

            if (!empty($result["content"])) {
                $preview = substr(strip_tags($result["content"]), 0, 200);
                if (strlen(strip_tags($result["content"])) > 200) {
                    $preview .= "...";
                }
                echo '<div class="result-preview">' .
                    htmlspecialchars($preview) .
                    "</div>";
            }

            echo '<div class="result-actions">';
            echo '<a href="/?action=view&page=' .
                urlencode($result["title"]) .
                '" class="btn btn-sm">View Page</a>';
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
    } elseif ($query || !empty($filters)) {
        echo '<div class="no-results">';
        echo '<div class="container">';
        echo '<div class="no-results-content">';
        echo '<div class="no-results-icon"><i class="fas fa-search"></i></div>';
        echo "<h3>No results found</h3>";
        echo '<p>We couldn\'t find any pages matching your search criteria.</p>';
        echo '<div class="no-results-suggestions">';
        echo "<h4>Try:</h4>";
        echo "<ul>";
        echo "<li>Using different keywords</li>";
        echo "<li>Removing some filters</li>";
        echo "<li>Checking your spelling</li>";
        echo "<li>Using broader search terms</li>";
        echo "</ul>";
        echo "</div>";
        echo '<a href="/?action=search" class="btn btn-primary">Clear Filters & Try Again</a>';
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    // Popular tags section
    if (!empty($allTags)) {
        echo '<div class="popular-tags-section">';
        echo '<div class="container">';
        echo "<h3>Popular Tags</h3>";
        echo '<div class="popular-tags">';
        foreach (array_slice($allTags, 0, 15) as $tag) {
            $isSelected = in_array($tag["name"], $selectedTags);
            $tagClass = $isSelected ? "tag-link active" : "tag-link";
            echo '<a href="' .
                addTagToUrl($tag["name"]) .
                '" class="' .
                $tagClass .
                '">' .
                htmlspecialchars($tag["name"]) .
                "</a>";
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
}

function removeFilterFromUrl($filterType)
{
    $params = $_GET;

    switch ($filterType) {
        case "query":
            unset($params["q"]);
            break;
        case "tags":
            unset($params["tags"]);
            break;
        case "date_from":
            unset($params["date_from"]);
            break;
        case "date_to":
            unset($params["date_to"]);
            break;
        case "author":
            unset($params["author"]);
            break;
        default:
            unset($params[$filterType]);
            break;
    }

    $queryString = http_build_query($params);
    return "/?action=search" . (!empty($queryString) ? "&" . $queryString : "");
}

function addTagToUrl($tagName)
{
    $params = $_GET;
    if (!isset($params["tags"])) {
        $params["tags"] = [];
    } elseif (!is_array($params["tags"])) {
        $params["tags"] = [$params["tags"]];
    }

    if (!in_array($tagName, $params["tags"])) {
        $params["tags"][] = $tagName;
    }

    $queryString = http_build_query($params);
    return "/?action=search" . (!empty($queryString) ? "&" . $queryString : "");
}

function createTagSearchUrl($tagName)
{
    // Start fresh with just search action and the tag
    $params = [
        "action" => "search",
        "tags" => [$tagName],
    ];

    $queryString = http_build_query($params);
    return "/?" . $queryString;
}

function showLoginForm()
{
    global $pageTitle, $message, $messageType, $auth;

    $pageTitle = "Login";

    if ($message) {
        echo '<div class="message ' .
            $messageType .
            '">' .
            htmlspecialchars($message) .
            "</div>";
    }

    echo "<h1>Login</h1>";
    echo '<form method="post">';
    echo '<input type="hidden" name="csrf_token" value="' .
        $auth->generateCSRFToken() .
        '">';
    echo '<div class="form-group">';
    echo '<label for="username">Username:</label>';
    echo '<input type="text" name="username" id="username" required>';
    echo "</div>";
    echo '<div class="form-group">';
    echo '<label for="password">Password:</label>';
    echo '<input type="password" name="password" id="password" required>';
    echo "</div>";
    echo '<button type="submit" class="btn">Login</button>';
    echo "</form>";
    echo '<p>Don\'t have an account? <a href="/?action=register">Register here</a>.</p>';
}

function showRegisterForm()
{
    global $pageTitle, $message, $messageType, $auth;

    $pageTitle = "Register";

    if ($message) {
        echo '<div class="message ' .
            $messageType .
            '">' .
            htmlspecialchars($message) .
            "</div>";
    }

    echo "<h1>Register</h1>";
    echo '<form method="post">';
    echo '<input type="hidden" name="csrf_token" value="' .
        $auth->generateCSRFToken() .
        '">';
    echo '<div class="form-group">';
    echo '<label for="username">Username:</label>';
    echo '<input type="text" name="username" id="username" required>';
    echo "</div>";
    echo '<div class="form-group">';
    echo '<label for="password">Password:</label>';
    echo '<input type="password" name="password" id="password" required>';
    echo "</div>";
    echo '<button type="submit" class="btn">Register</button>';
    echo "</form>";
    echo '<p>Already have an account? <a href="/?action=login">Login here</a>.</p>';
}

function showRevisions($wiki, $auth, $title)
{
    global $pageTitle;

    $pageTitle = "History: " . $title;

    $revisions = $wiki->getRevisions($title);

    if (empty($revisions)) {
        echo "<h1>Page History: " . htmlspecialchars($title) . "</h1>";
        echo "<p>No revisions found for this page.</p>";
        echo '<p><a href="/?action=view&page=' .
            urlencode($title) .
            '" class="btn">Back to Page</a></p>';
        return;
    }

    echo "<h1>Page History: " . htmlspecialchars($title) . "</h1>";
    echo '<p><a href="/?action=view&page=' .
        urlencode($title) .
        '" class="btn">Back to Current Page</a></p>';

    echo '<div class="revisions-list">';
    $count = count($revisions);
    foreach ($revisions as $index => $revision) {
        $isCurrent = $index === 0;
        $version = $count - $index;

        echo '<div class="revision-item' .
            ($isCurrent ? " current" : "") .
            '">';
        echo '<div class="revision-header">';
        echo '<div class="revision-meta">';
        echo '<span class="revision-version">Version ' . $version . "</span>";
        echo '<span class="revision-author"><i class="fas fa-user"></i> ' .
            htmlspecialchars($revision["author"]) .
            "</span>";
        echo '<span class="revision-date"><i class="fas fa-calendar"></i> ' .
            date("M j, Y H:i", strtotime($revision["timestamp"])) .
            "</span>";
        if ($isCurrent) {
            echo '<span class="revision-current">Current</span>';
        }
        echo "</div>";
        echo '<div class="revision-actions">';
        echo '<a href="/?action=view&page=' .
            urlencode($title) .
            "&revision=" .
            $revision["id"] .
            '" class="btn btn-sm">View</a>';
        if (!$isCurrent && $auth->isLoggedIn()) {
            echo '<a href="/?action=restore&revision=' .
                $revision["id"] .
                "&page=" .
                urlencode($title) .
                '" class="btn btn-sm btn-warning" onclick="return confirm(\'Are you sure you want to restore this version?\')">Restore</a>';
        }
        echo "</div>";
        echo "</div>";

        // Show a preview of the content
        $preview = substr(strip_tags($revision["content"]), 0, 200);
        if (strlen(strip_tags($revision["content"])) > 200) {
            $preview .= "...";
        }
        echo '<div class="revision-preview">' .
            htmlspecialchars($preview) .
            "</div>";
        echo "</div>";
    }
    echo "</div>";
}

function showRevision($wiki, $revisionId)
{
    global $pageTitle, $auth;

    $revision = $wiki->getRevision($revisionId);

    if (!$revision) {
        $pageTitle = "Revision Not Found";
        echo "<h1>Revision Not Found</h1>";
        echo "<p>The requested revision does not exist.</p>";
        return;
    }

    $pageTitle = "Revision: " . $revision["title"];

    echo "<h1>Revision of: " . htmlspecialchars($revision["title"]) . "</h1>";
    echo '<div class="revision-info">';
    echo "<p><strong>Author:</strong> " .
        htmlspecialchars($revision["author"]) .
        "</p>";
    echo "<p><strong>Date:</strong> " .
        date("M j, Y H:i", strtotime($revision["timestamp"])) .
        "</p>";
    echo "</div>";

    echo '<div class="page-content">' .
        $revision["rendered_content"] .
        "</div>";

    echo '<div class="page-actions">';
    echo '<a href="/?action=view&page=' .
        urlencode($revision["title"]) .
        '&revisions=1" class="btn">Back to History</a>';
    echo '<a href="/?action=view&page=' .
        urlencode($revision["title"]) .
        '" class="btn">Back to Current Page</a>';
    if ($auth->isLoggedIn()) {
        echo '<a href="/?action=restore&revision=' .
            $revisionId .
            "&page=" .
            urlencode($revision["title"]) .
            '" class="btn btn-warning" onclick="return confirm(\'Are you sure you want to restore this version?\')">Restore This Version</a>';
    }
    echo "</div>";
}

function handleRestore($wiki, $auth)
{
    if (!$auth->isLoggedIn()) {
        header("Location: /?action=login");
        exit();
    }

    $revisionId = $_GET["revision"] ?? "";
    $pageTitle = $_GET["page"] ?? "";

    if (!$revisionId || !$pageTitle) {
        header("Location: /");
        exit();
    }

    $result = $wiki->restoreRevision($revisionId);

    if ($result["success"]) {
        header(
            "Location: /?action=view&page=" .
                urlencode($pageTitle) .
                "&message=Revision restored successfully",
        );
    } else {
        header(
            "Location: /?action=view&page=" .
                urlencode($pageTitle) .
                "&error=Failed to restore revision",
        );
    }
    exit();
}

function handleThemeSwitch()
{
    // Handle AJAX theme switch request
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header("Content-Type: application/json");

        $input = json_decode(file_get_contents("php://input"), true);
        $theme = $input["theme"] ?? "";

        // Validate theme
        $validThemes = ["minimal", "dark", "corporate", "retro"];

        if (!in_array($theme, $validThemes)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid theme",
            ]);
            return;
        }

        // Store theme preference in session/cookie
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION["user_theme"] = $theme;

        // Also set a cookie for non-logged in users
        setcookie("theme_preference", $theme, time() + 86400 * 365, "/");

        echo json_encode(["success" => true, "theme" => $theme]);
        exit();
    }

    // If not POST, redirect to home
    header("Location: /");
    exit();
}
?>
