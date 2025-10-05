// LiteWiki Main JavaScript

// Carica marked.js dinamicamente se non è già presente
if (typeof marked === "undefined") {
  const script = document.createElement("script");
  script.src = "https://cdn.jsdelivr.net/npm/marked/marked.min.js";
  script.async = false;
  document.head.appendChild(script);
}

// Nascondi scrollbar della pagina principale
const hideScrollbarStyle = document.createElement('style');
hideScrollbarStyle.textContent = `
  /* Nascondi scrollbar per Chrome, Safari e Opera */
  body::-webkit-scrollbar {
    display: none;
  }
  
  /* Nascondi scrollbar per IE, Edge e Firefox */
  body {
    -ms-overflow-style: none;  /* IE e Edge */
    scrollbar-width: none;  /* Firefox */
  }
  
  html::-webkit-scrollbar {
    display: none;
  }
  
  html {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
`;
document.head.appendChild(hideScrollbarStyle);


document.addEventListener("DOMContentLoaded", function () {
  // Auto-resize textareas
  const textareas = document.querySelectorAll("textarea");
  textareas.forEach((textarea) => {
    textarea.addEventListener("input", autoResize);
    autoResize.call(textarea);
  });

  // Confirm delete actions
  const deleteButtons = document.querySelectorAll(".btn-danger");
  deleteButtons.forEach((button) => {
    button.addEventListener("click", confirmDelete);
  });

  // Search functionality
  const searchForm = document.getElementById("search-form");
  if (searchForm) {
    searchForm.addEventListener("submit", handleSearch);
  }

  // Initialize tooltips if needed
  initializeTooltips();

  // Initialize search filters
  initializeSearchFilters();
});

function autoResize() {
  this.style.height = "auto";
  this.style.height = this.scrollHeight + "px";
}

function confirmDelete(e) {
  if (!confirm("Are you sure you want to delete this?")) {
    e.preventDefault();
  }
}

function handleSearch(e) {
  const query = e.target.querySelector('input[name="q"]').value.trim();
  if (!query) {
    e.preventDefault();
    alert("Please enter a search term");
  }
}

function initializeTooltips() {
  // Add tooltips to elements with data-tooltip attribute
  const tooltipElements = document.querySelectorAll("[data-tooltip]");
  tooltipElements.forEach((element) => {
    element.addEventListener("mouseenter", showTooltip);
    element.addEventListener("mouseleave", hideTooltip);
  });
}

function showTooltip(e) {
  const tooltip = document.createElement("div");
  tooltip.className = "tooltip";
  tooltip.textContent = e.target.getAttribute("data-tooltip");
  document.body.appendChild(tooltip);

  const rect = e.target.getBoundingClientRect();
  tooltip.style.left =
    rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
  tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px";
}

function hideTooltip() {
  const tooltip = document.querySelector(".tooltip");
  if (tooltip) {
    tooltip.remove();
  }
}

// Utility functions
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Add CSS for tooltips
const style = document.createElement("style");
style.textContent = `
.tooltip {
    position: absolute;
    background-color: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 1000;
    pointer-events: none;
}
`;
document.head.appendChild(style);

// Search filter functionality
function initializeSearchFilters() {
  // Tag search functionality
  const tagSearchInput = document.querySelector(".tag-search-input");
  if (tagSearchInput) {
    tagSearchInput.addEventListener("input", debounce(filterTags, 300));
  }

  // Author search functionality
  const authorSearchInput = document.querySelector(".author-search-input");
  if (authorSearchInput) {
    authorSearchInput.addEventListener("input", debounce(filterAuthors, 300));
  }

  // Author selection functionality
  const authorSelector = document.getElementById("author-selector");
  if (authorSelector) {
    const authorOptions = authorSelector.querySelectorAll(".author-option");
    authorOptions.forEach((option) => {
      option.addEventListener("click", function () {
        // Remove selected class from all options
        authorOptions.forEach((opt) => opt.classList.remove("selected"));
        // Add selected class to clicked option
        this.classList.add("selected");
        // Update hidden input
        const selectedAuthor = document.getElementById("selected-author");
        selectedAuthor.value = this.getAttribute("data-value");
      });
    });
  }
}

function filterTags(e) {
  const searchTerm = e.target.value.toLowerCase();
  const tagSelector = document.getElementById("tag-selector");
  const tagCheckboxes = tagSelector.querySelectorAll(".tag-checkbox");

  tagCheckboxes.forEach((checkbox) => {
    const tagLabel = checkbox.querySelector(".tag-label");
    const tagText = tagLabel.textContent.toLowerCase();

    if (tagText.includes(searchTerm)) {
      checkbox.style.display = "flex";
    } else {
      checkbox.style.display = "none";
    }
  });
}

function filterAuthors(e) {
  const searchTerm = e.target.value.toLowerCase();
  const authorSelector = document.getElementById("author-selector");
  const authorOptions = authorSelector.querySelectorAll(".author-option");

  authorOptions.forEach((option) => {
    const authorText = option.textContent.toLowerCase();

    if (authorText.includes(searchTerm)) {
      option.style.display = "block";
    } else {
      option.style.display = "none";
    }
  });
}

function getCurrentThemeFromCSS(cssHref) {
  const themePaths = {
    "/css/white.css": "white",
    "/css/dark.css": "dark",
    "/css/highVisibility.css": "highVisibility",
  };

  // Find theme from CSS path
  for (const [path, theme] of Object.entries(themePaths)) {
    if (cssHref.includes(path)) {
      return theme;
    }
  }

  return "white";
  return "white";
}

function switchTheme(theme) {
  // Update CSS immediately for smooth transition
  const themeCSS = document.getElementById("theme-css");
  const themePaths = {
    white: "/css/white.css",
    dark: "/css/dark.css",
    highVisibility: "/css/highVisibility.css",
  };

  if (themePaths[theme] && themeCSS) {
    themeCSS.href = themePaths[theme];
  }

  // Send AJAX request to save preference
  fetch("/?action=switch_theme", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ theme: theme }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        console.error("Failed to save theme preference:", data.message);
        // Optionally show user-friendly error message
      }
    })
    .catch((error) => {
      console.error("Error switching theme:", error);
      // Still allow theme change locally even if server request fails
    });
}

function toggleThemeDropdown() {
  const themeDropdown = document.getElementById("theme-dropdown");
  if (!themeDropdown) return;

  if (themeDropdown.classList.contains("show")) {
    closeThemeDropdown();
  } else {
    openThemeDropdown();
  }
}

function openThemeDropdown() {
  const themeDropdown = document.getElementById("theme-dropdown");
  if (!themeDropdown) return;

  // Force display first for animation
  themeDropdown.style.display = "block";

  // Small delay to ensure display change is processed
  requestAnimationFrame(() => {
    themeDropdown.classList.add("show");
  });
}

function closeThemeDropdown() {
  const themeDropdown = document.getElementById("theme-dropdown");
  if (!themeDropdown) return;

  themeDropdown.classList.remove("show");

  // Hide after animation completes
  setTimeout(() => {
    if (!themeDropdown.classList.contains("show")) {
      themeDropdown.style.display = "none";
    }
  }, 200); // Match CSS transition duration
}

function updateDropdownThemeStyle(theme) {
  const themeDropdown = document.getElementById("theme-dropdown");
  if (!themeDropdown) return;

  // Remove existing theme classes
  themeDropdown.classList.remove("theme-dark", "theme-light");

  // Add appropriate theme class based on current theme
  if (theme === "dark") {
    themeDropdown.classList.add("theme-dark");
  } else {
    themeDropdown.classList.add("theme-light");
  }

  // Set data attribute for CSS targeting
  document.body.setAttribute("data-current-theme", theme);
}

// Inserisci questa parte in fondo al main.js, prima di eventuali altre chiusure </script>
document.addEventListener("mouseup", () => {
  // Controlla se siamo in una delle pagine escluse
  const urlParams = new URLSearchParams(window.location.search);
  const currentAction = urlParams.get("action");
  const currentPage = urlParams.get("page");
  const excludedActions = ["search", "list", "login", "register"];
  
  // Escludi le action specificate e la pagina Home
  if (excludedActions.includes(currentAction) || (currentAction === "view" && currentPage === "Home")) {
    // Nascondi i bottoni se esistono e esci
    const btnSearch = document.getElementById("search-ai-btn");
    const btnAsk = document.getElementById("ask-ai-btn");
    if (btnSearch) btnSearch.style.display = "none";
    if (btnAsk) btnAsk.style.display = "none";
    return;
  }

  // Piccolo ritardo per assicurarsi che la selezione sia aggiornata
  setTimeout(() => {
    const selection = window.getSelection();
    if (!selection.isCollapsed) {
      const range = selection.getRangeAt(0);
      const rect = range.getBoundingClientRect();

      // Controlla TUTTI i container della selezione (inizio, fine, e comune)
      const startContainer = range.startContainer;
      const endContainer = range.endContainer;
      const commonContainer = range.commonAncestorContainer;
      
      const startElement = startContainer.nodeType === 3 ? startContainer.parentElement : startContainer;
      const endElement = endContainer.nodeType === 3 ? endContainer.parentElement : endContainer;
      const commonElement = commonContainer.nodeType === 3 ? commonContainer.parentElement : commonContainer;
      
      // Verifica se qualsiasi parte della selezione è dentro header o footer
      const isStartInHeaderOrFooter = startElement.closest('header') || startElement.closest('footer');
      const isEndInHeaderOrFooter = endElement.closest('header') || endElement.closest('footer');
      const isCommonInHeaderOrFooter = commonElement.closest('header') || commonElement.closest('footer');
      
      // Se qualsiasi parte della selezione tocca header o footer, nascondi i bottoni
      if (isStartInHeaderOrFooter || isEndInHeaderOrFooter || isCommonInHeaderOrFooter) {
        const btnSearch = document.getElementById("search-ai-btn");
        const btnAsk = document.getElementById("ask-ai-btn");
        if (btnSearch) btnSearch.style.display = "none";
        if (btnAsk) btnAsk.style.display = "none";
        return;
      }

      // Bottone "Search with AI"
      let btnSearch = document.getElementById("search-ai-btn");
      if (!btnSearch) {
        btnSearch = document.createElement("button");
        btnSearch.id = "search-ai-btn";
        btnSearch.textContent = "Search with AI";
        btnSearch.style.position = "absolute";
        btnSearch.style.zIndex = "9999";
        btnSearch.style.padding = "6px 12px";
        btnSearch.style.backgroundColor = "#0056b3";
        btnSearch.style.color = "#fff";
        btnSearch.style.border = "none";
        btnSearch.style.borderRadius = "6px";
        btnSearch.style.fontWeight = "600";
        btnSearch.style.fontSize = "14px";
        btnSearch.style.boxShadow = "0 2px 6px rgba(0,0,0,0.2)";
        btnSearch.style.cursor = "pointer";
        btnSearch.style.transition = "background-color 0.3s ease";
        btnSearch.style.userSelect = "none";
        btnSearch.style.whiteSpace = "nowrap";
        btnSearch.style.marginRight = "8px";

        btnSearch.addEventListener("mouseenter", () => {
          btnSearch.style.backgroundColor = "#003d80";
        });

        btnSearch.addEventListener("mouseleave", () => {
          btnSearch.style.backgroundColor = "#0056b3";
        });

        document.body.appendChild(btnSearch);

        btnSearch.addEventListener("click", () => {
          const selectedText = selection.toString();
          const encodedText = encodeURIComponent(selectedText);
          window.open(
            `/?action=ai-search&q=${encodedText}`,
            "_blank",
          );
          btnSearch.style.display = "none";
          btnAsk.style.display = "none";
          selection.removeAllRanges();
        });
      }

      // Bottone "Ask to AI"
      let btnAsk = document.getElementById("ask-ai-btn");
      if (!btnAsk) {
        btnAsk = document.createElement("button");
        btnAsk.id = "ask-ai-btn";
        btnAsk.textContent = "Ask to AI";
        btnAsk.style.position = "absolute";
        btnAsk.style.zIndex = "9999";
        btnAsk.style.padding = "6px 12px";
        btnAsk.style.backgroundColor = "#28a745";
        btnAsk.style.color = "#fff";
        btnAsk.style.border = "none";
        btnAsk.style.borderRadius = "6px";
        btnAsk.style.fontWeight = "600";
        btnAsk.style.fontSize = "14px";
        btnAsk.style.boxShadow = "0 2px 6px rgba(0,0,0,0.2)";
        btnAsk.style.cursor = "pointer";
        btnAsk.style.transition = "background-color 0.3s ease";
        btnAsk.style.userSelect = "none";
        btnAsk.style.whiteSpace = "nowrap";

        btnAsk.addEventListener("mouseenter", () => {
          btnAsk.style.backgroundColor = "#1e7e34";
        });

        btnAsk.addEventListener("mouseleave", () => {
          btnAsk.style.backgroundColor = "#28a745";
        });

        document.body.appendChild(btnAsk);

        btnAsk.addEventListener("click", () => {
          const selectedText = selection.toString();

          const urlParams = new URLSearchParams(window.location.search);
          const pageId = urlParams.get("page") || "";

          btnSearch.style.display = "none";
          btnAsk.style.display = "none";
          selection.removeAllRanges();

          showAIModal(selectedText, pageId);
        });
      }

      btnSearch.style.display = "block";
      btnAsk.style.display = "block";

      requestAnimationFrame(() => {
        const scrollTop = window.scrollY || window.pageYOffset;
        const scrollLeft = window.scrollX || window.pageXOffset;

        const btnHeight = btnSearch.offsetHeight || 34;

        const topPos = scrollTop + rect.top - btnHeight - 12;
        const leftPos = Math.max(scrollLeft + rect.left, 5);

        btnSearch.style.top = `${topPos}px`;
        btnSearch.style.left = `${leftPos}px`;

        const btnSearchWidth = btnSearch.offsetWidth || 120;
        btnAsk.style.top = `${topPos}px`;
        btnAsk.style.left = `${leftPos + btnSearchWidth + 8}px`;
      });
    } else {
      // Nessuna selezione - nascondi i bottoni
      const btnSearch = document.getElementById("search-ai-btn");
      const btnAsk = document.getElementById("ask-ai-btn");
      if (btnSearch) btnSearch.style.display = "none";
      if (btnAsk) btnAsk.style.display = "none";
    }
  }, 10); // Ritardo di 10ms per lasciare tempo alla selezione di aggiornarsi
});

document.addEventListener("mousedown", (e) => {
  const btnSearch = document.getElementById("search-ai-btn");
  const btnAsk = document.getElementById("ask-ai-btn");
  
  // Se clicchi sui bottoni, non nasconderli
  if (e.target === btnSearch || e.target === btnAsk) {
    return;
  }
  
  // Nascondi immediatamente i bottoni quando clicchi altrove
  if (btnSearch) btnSearch.style.display = "none";
  if (btnAsk) btnAsk.style.display = "none";
});

function showAIModal(selectedText, pageId) {
  let modal = document.getElementById("ai-modal");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "ai-modal";
    modal.style.position = "fixed";
    modal.style.top = "50%";
    modal.style.left = "50%";
    modal.style.transform = "translate(-50%, -50%)";
    modal.style.width = "600px";
    modal.style.maxWidth = "90%";
    modal.style.maxHeight = "80vh";
    modal.style.backgroundColor = "#ffffff";
    modal.style.borderRadius = "8px";
    modal.style.boxShadow = "0 4px 20px rgba(0,0,0,0.3)";
    modal.style.zIndex = "10000";
    modal.style.padding = "20px";
    modal.style.display = "none";
    modal.style.overflow = "hidden";
    modal.style.flexDirection = "column";

    const title = document.createElement("div");
    title.textContent = "Description";
    title.style.fontSize = "18px";
    title.style.fontWeight = "600";
    title.style.marginBottom = "15px";
    title.style.color = "#1a1a1a";
    modal.appendChild(title);

    const contentDiv = document.createElement("div");
    contentDiv.id = "ai-response-text";
    contentDiv.style.width = "100%";
    contentDiv.style.flex = "1";
    contentDiv.style.padding = "15px";
    contentDiv.style.border = "1px solid #e0e0e0";
    contentDiv.style.borderRadius = "4px";
    contentDiv.style.fontSize = "14px";
    contentDiv.style.fontFamily =
      '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    contentDiv.style.overflowY = "auto";
    contentDiv.style.backgroundColor = "#fafafa";
    contentDiv.style.lineHeight = "1.6";
    contentDiv.style.color = "#1a1a1a";
    modal.appendChild(contentDiv);

    const closeBtn = document.createElement("button");
    closeBtn.textContent = "Close";
    closeBtn.style.marginTop = "15px";
    closeBtn.style.padding = "8px 16px";
    closeBtn.style.backgroundColor = "#dc3545";
    closeBtn.style.color = "#fff";
    closeBtn.style.border = "none";
    closeBtn.style.borderRadius = "4px";
    closeBtn.style.cursor = "pointer";
    closeBtn.style.fontWeight = "600";
    closeBtn.style.fontSize = "14px";
    closeBtn.style.transition = "all 0.3s ease";
    closeBtn.style.boxShadow = "0 2px 4px rgba(0,0,0,0.2)";

    closeBtn.addEventListener("mouseenter", () => {
      closeBtn.style.backgroundColor = "#c82333";
      closeBtn.style.transform = "scale(1.05)";
      closeBtn.style.boxShadow = "0 4px 8px rgba(0,0,0,0.3)";
    });

    closeBtn.addEventListener("mouseleave", () => {
      closeBtn.style.backgroundColor = "#dc3545";
      closeBtn.style.transform = "scale(1)";
      closeBtn.style.boxShadow = "0 2px 4px rgba(0,0,0,0.2)";
    });

    closeBtn.addEventListener("mousedown", () => {
      closeBtn.style.transform = "scale(0.95)";
    });

    closeBtn.addEventListener("mouseup", () => {
      closeBtn.style.transform = "scale(1.05)";
    });

    closeBtn.addEventListener("click", () => {
      modal.style.display = "none";
      overlay.style.display = "none";
    });

    modal.appendChild(closeBtn);

    const overlay = document.createElement("div");
    overlay.id = "ai-modal-overlay";
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0,0,0,0.6)";
    overlay.style.zIndex = "9999";
    overlay.style.display = "none";
    overlay.addEventListener("click", () => {
      modal.style.display = "none";
      overlay.style.display = "none";
    });

    document.body.appendChild(overlay);
    document.body.appendChild(modal);

    addMarkdownStyles();
  }

  const contentDiv = document.getElementById("ai-response-text");
  const overlay = document.getElementById("ai-modal-overlay");

  contentDiv.innerHTML = "<p>Loading...</p>";
  modal.style.display = "flex";
  overlay.style.display = "block";

  const encodedText = encodeURIComponent(selectedText);
  const apiUrl = `test-api.php?text=${encodedText}&page_id=${pageId}`;

  fetch(apiUrl)
    .then((response) => response.json())
    .then((data) => {
      const markdownText =
        data.response || data.text || "Nessuna risposta disponibile";

      const renderMarkdown = () => {
        if (typeof marked !== "undefined") {
          contentDiv.innerHTML = marked.parse(markdownText);
        } else {
          setTimeout(renderMarkdown, 100);
        }
      };
      renderMarkdown();
    })
    .catch((error) => {
      contentDiv.innerHTML =
        '<p style="color: #dc3545;">Error: ' + error.message + "</p>";
    });
}

function addMarkdownStyles() {
  if (document.getElementById('ai-modal-markdown-styles')) return;

  const style = document.createElement('style');
  style.id = 'ai-modal-markdown-styles';
  style.textContent = `
    /* Nascondi scrollbar del contenuto AI mantenendolo scrollabile */
    #ai-response-text::-webkit-scrollbar {
      display: none;
    }
    
    #ai-response-text {
      -ms-overflow-style: none;  /* IE e Edge */
      scrollbar-width: none;  /* Firefox */
    }

    #ai-response-text h1,
    #ai-response-text h2,
    #ai-response-text h3,
    #ai-response-text h4,
    #ai-response-text h5,
    #ai-response-text h6 {
      color: #1a1a1a !important;
      margin-top: 16px !important;
      margin-bottom: 8px !important;
      font-weight: 600 !important;
    }

    #ai-response-text h1 { font-size: 24px !important; }
    #ai-response-text h2 { font-size: 20px !important; }
    #ai-response-text h3 { font-size: 18px !important; }
    #ai-response-text h4 { font-size: 16px !important; }

    #ai-response-text p {
      color: #1a1a1a !important;
      margin: 8px 0 !important;
    }

    #ai-response-text a {
      color: #0056b3 !important;
      text-decoration: underline !important;
    }

    #ai-response-text a:hover {
      color: #003d80 !important;
    }

    #ai-response-text ul,
    #ai-response-text ol {
      color: #1a1a1a !important;
      margin: 8px 0 !important;
      padding-left: 24px !important;
    }

    #ai-response-text li {
      color: #1a1a1a !important;
      margin: 4px 0 !important;
    }

    #ai-response-text code {
      background-color: #e8e8e8 !important;
      color: #c7254e !important;
      padding: 2px 6px !important;
      border-radius: 3px !important;
      font-family: 'Courier New', Courier, monospace !important;
      font-size: 13px !important;
    }

    #ai-response-text pre {
      background-color: #2d2d2d !important;
      color: #f8f8f2 !important;
      padding: 12px !important;
      border-radius: 4px !important;
      overflow-x: auto !important;
      margin: 12px 0 !important;
    }

    #ai-response-text pre code {
      background-color: transparent !important;
      color: #f8f8f2 !important;
      padding: 0 !important;
    }

    #ai-response-text blockquote {
      border-left: 4px solid #0056b3 !important;
      padding-left: 12px !important;
      margin: 12px 0 !important;
      color: #4a4a4a !important;
      font-style: italic !important;
    }

    #ai-response-text table {
      border-collapse: collapse !important;
      width: 100% !important;
      margin: 12px 0 !important;
    }

    #ai-response-text table th,
    #ai-response-text table td {
      border: 1px solid #d0d0d0 !important;
      padding: 8px !important;
      text-align: left !important;
      color: #1a1a1a !important;
    }

    #ai-response-text table th {
      background-color: #e8e8e8 !important;
      font-weight: 600 !important;
    }

    #ai-response-text table tr:nth-child(even) {
      background-color: #f5f5f5 !important;
    }

    #ai-response-text strong {
      color: #1a1a1a !important;
      font-weight: 600 !important;
    }

    #ai-response-text em {
      color: #1a1a1a !important;
    }
  `;
  document.head.appendChild(style);
}


