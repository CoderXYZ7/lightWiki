// LiteWiki Main JavaScript

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

// Initialize theme switcher
initializeThemeSwitcher();

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

// Theme switcher functionality
function initializeThemeSwitcher() {
  const themeToggle = document.getElementById("theme-toggle");
  const themeDropdown = document.getElementById("theme-dropdown");
  const themeOptions = document.querySelectorAll(".theme-option");

  if (!themeToggle || !themeDropdown || !themeOptions.length) {
    return; // Theme switcher not present on this page
  }

  // Get current theme from the CSS link href
  const themeCSS = document.getElementById("theme-css");
  const currentTheme = getCurrentThemeFromCSS(themeCSS.href);

  // Set active theme option
  themeOptions.forEach((option) => {
    if (option.dataset.theme === currentTheme) {
      option.classList.add("active");
    }
  });

  // Toggle dropdown
  themeToggle.addEventListener("click", function (e) {
    e.stopPropagation();
    themeDropdown.classList.toggle("show");
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", function () {
    themeDropdown.classList.remove("show");
  });

  // Handle theme selection
  themeOptions.forEach((option) => {
    option.addEventListener("click", function () {
      const selectedTheme = this.dataset.theme;

      // Update active state
      themeOptions.forEach((opt) => opt.classList.remove("active"));
      this.classList.add("active");

      // Switch theme
      switchTheme(selectedTheme);

      // Close dropdown
      themeDropdown.classList.remove("show");
    });
  });
}

function getCurrentThemeFromCSS(cssHref) {
  const themePaths = {
    "/css/style.css": "default",
    "/css/dark.css": "dark",
    "/css/minimal.css": "minimal",
    "/css/minimalist.css": "minimalist",
    "/css/vibrant.css": "vibrant",
    "/css/nature.css": "nature",
    "/css/corporate.css": "corporate",
    "/css/retro.css": "retro",
  };

  // Find theme from CSS path
  for (const [path, theme] of Object.entries(themePaths)) {
    if (cssHref.includes(path)) {
      return theme;
    }
  }

  return "default";
}

function switchTheme(theme) {
  // Update CSS immediately for smooth transition
  const themeCSS = document.getElementById("theme-css");
  const themePaths = {
    default: "/css/style.css",
    dark: "/css/dark.css",
    minimal: "/css/minimal.css",
    minimalist: "/css/minimalist.css",
    vibrant: "/css/vibrant.css",
    nature: "/css/nature.css",
    corporate: "/css/corporate.css",
    retro: "/css/retro.css",
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
document.addEventListener('mouseup', () => {
  const selection = window.getSelection();
  if (!selection.isCollapsed) {
    const range = selection.getRangeAt(0);
    const rect = range.getBoundingClientRect();

    let btn = document.getElementById('ask-ai-btn');
    if (!btn) {
      btn = document.createElement('button');
      btn.id = 'ask-ai-btn';
      btn.textContent = 'Chiedi all\'AI';
      btn.style.position = 'absolute';
      btn.style.zIndex = '9999';
      btn.style.padding = '6px 12px';
      btn.style.backgroundColor = '#0056b3'; 
      btn.style.color = '#fff';
      btn.style.border = 'none';
      btn.style.borderRadius = '6px';
      btn.style.fontWeight = '600';
      btn.style.fontSize = '14px';
      btn.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
      btn.style.cursor = 'pointer';
      btn.style.transition = 'background-color 0.3s ease';
      btn.style.userSelect = 'none'; 
      btn.style.whiteSpace = 'nowrap';

      btn.addEventListener('mouseenter', () => {
        btn.style.backgroundColor = '#003d80';
      });

      btn.addEventListener('mouseleave', () => {
        btn.style.backgroundColor = '#0056b3';
      });

      document.body.appendChild(btn);

      btn.addEventListener('click', () => {
        const selectedText = selection.toString();
        alert('Testo selezionato da inviare all\'AI: ' + selectedText);
        btn.style.display = 'none';
        selection.removeAllRanges();
      });
    }

    btn.style.display = 'block';
    
    requestAnimationFrame(() => {
      const scrollTop = window.scrollY || window.pageYOffset;
      const scrollLeft = window.scrollX || window.pageXOffset;
      
      const btnHeight = btn.offsetHeight || 34;
      
      const topPos = scrollTop + rect.top - btnHeight - 12;
      const leftPos = Math.max(scrollLeft + rect.left, 5);

      btn.style.top = `${topPos}px`;
      btn.style.left = `${leftPos}px`;
    });
  } else {
    const btn = document.getElementById('ask-ai-btn');
    if (btn) btn.style.display = 'none';
  }
});

// Nascondi il bottone quando l'utente clicca da qualche parte
document.addEventListener('mousedown', (e) => {
  const btn = document.getElementById('ask-ai-btn');
  // Se il click non è sul bottone stesso, nascondilo
  if (btn && e.target !== btn) {
    btn.style.display = 'none';
  }
});

