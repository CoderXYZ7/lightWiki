// LiteWiki Theme Switcher
// Dedicated JavaScript for theme switching functionality

document.addEventListener("DOMContentLoaded", function () {
  initializeThemeSwitcher();
});

/**
 * Initialize the theme switcher functionality
 */
function initializeThemeSwitcher() {
  const themeToggle = document.getElementById("theme-toggle");
  const themeDropdown = document.getElementById("theme-dropdown");
  const themeOptions = document.querySelectorAll(".theme-option");

  if (!themeToggle || !themeDropdown || !themeOptions.length) {
    return; // Theme switcher not present on this page
  }

  // Get current theme from the CSS link href
  const themeCSS = document.getElementById("theme-css");
  const currentTheme = getCurrentThemeFromCSS(themeCSS ? themeCSS.href : "");

  // Set active theme option
  setActiveThemeOption(themeOptions, currentTheme);

  // Setup event listeners
  setupToggleHandler(themeToggle, themeDropdown);
  setupOutsideClickHandler(themeDropdown);
  setupThemeSelectionHandlers(themeOptions, themeDropdown);

  // Setup keyboard navigation
  setupKeyboardNavigation(themeToggle, themeDropdown, themeOptions);
}

/**
 * Set the active theme option based on current theme
 */
function setActiveThemeOption(themeOptions, currentTheme) {
  themeOptions.forEach((option) => {
    option.classList.remove("active");
    if (option.dataset.theme === currentTheme) {
      option.classList.add("active");
    }
  });
}

/**
 * Setup toggle button click handler
 */
function setupToggleHandler(themeToggle, themeDropdown) {
  themeToggle.addEventListener("click", function (e) {
    e.stopPropagation();
    e.preventDefault();

    if (themeDropdown.classList.contains("show")) {
      closeDropdown(themeDropdown);
    } else {
      openDropdown(themeToggle, themeDropdown);
    }
  });
}

/**
 * Setup outside click handler to close dropdown
 */
function setupOutsideClickHandler(themeDropdown) {
  document.addEventListener("click", function (e) {
    if (!themeDropdown.contains(e.target)) {
      closeDropdown(themeDropdown);
    }
  });

  // Close on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && themeDropdown.classList.contains("show")) {
      closeDropdown(themeDropdown);
    }
  });
}

/**
 * Setup theme selection event handlers
 */
function setupThemeSelectionHandlers(themeOptions, themeDropdown) {
  themeOptions.forEach((option) => {
    option.addEventListener("click", function (e) {
      e.stopPropagation();
      const selectedTheme = this.dataset.theme;

      // Update active state
      setActiveThemeOption(themeOptions, selectedTheme);

      // Switch theme
      switchTheme(selectedTheme);

      // Close dropdown with slight delay for better UX
      setTimeout(() => {
        closeDropdown(themeDropdown);
      }, 150);
    });

    // Add hover effect for better accessibility
    option.addEventListener("mouseenter", function () {
      this.style.transform = "translateX(2px)";
    });

    option.addEventListener("mouseleave", function () {
      this.style.transform = "translateX(0)";
    });
  });
}

/**
 * Setup keyboard navigation for accessibility
 */
function setupKeyboardNavigation(themeToggle, themeDropdown, themeOptions) {
  themeToggle.addEventListener("keydown", function (e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      themeToggle.click();
    }
  });

  themeOptions.forEach((option, index) => {
    option.addEventListener("keydown", function (e) {
      switch (e.key) {
        case "Enter":
        case " ":
          e.preventDefault();
          option.click();
          break;
        case "ArrowDown":
          e.preventDefault();
          const nextIndex = (index + 1) % themeOptions.length;
          themeOptions[nextIndex].focus();
          break;
        case "ArrowUp":
          e.preventDefault();
          const prevIndex =
            (index - 1 + themeOptions.length) % themeOptions.length;
          themeOptions[prevIndex].focus();
          break;
      }
    });

    // Make options focusable
    option.setAttribute("tabindex", "0");
  });
}

/**
 * Open dropdown with proper positioning
 */
function openDropdown(themeToggle, themeDropdown) {
  // Create and use portal container to escape all stacking contexts
  createDropdownPortal(themeDropdown);

  // Position dropdown before showing
  positionDropdown(themeToggle, themeDropdown);

  // Add show class with animation
  themeDropdown.classList.add("show");

  // Focus first option for keyboard navigation
  const firstOption = themeDropdown.querySelector(".theme-option");
  if (firstOption) {
    setTimeout(() => firstOption.focus(), 100);
  }
}

/**
 * Close dropdown
 */
function closeDropdown(themeDropdown) {
  themeDropdown.classList.remove("show");
  // Return dropdown to original location after animation
  setTimeout(() => {
    returnDropdownFromPortal(themeDropdown);
  }, 200);
}

/**
 * Create portal container to completely escape all stacking contexts
 */
function createDropdownPortal(dropdown) {
  let portal = document.getElementById("theme-dropdown-portal");

  if (!portal) {
    portal = document.createElement("div");
    portal.id = "theme-dropdown-portal";
    portal.className = "theme-dropdown-portal";
    portal.style.cssText = `
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100vw !important;
      height: 100vh !important;
      pointer-events: none !important;
      z-index: 2147483647 !important;
      isolation: isolate !important;
      contain: layout style paint !important;
    `;
    document.body.appendChild(portal);
  }

  // Move dropdown to portal
  if (dropdown.parentElement !== portal) {
    // Store original parent for restoration
    dropdown.originalParent = dropdown.parentElement;
    dropdown.originalNextSibling = dropdown.nextElementSibling;
    portal.appendChild(dropdown);
  }

  dropdown.style.pointerEvents = "auto";
}

/**
 * Return dropdown from portal to original location
 */
function returnDropdownFromPortal(dropdown) {
  if (dropdown.originalParent && !dropdown.classList.contains("show")) {
    if (dropdown.originalNextSibling) {
      dropdown.originalParent.insertBefore(
        dropdown,
        dropdown.originalNextSibling,
      );
    } else {
      dropdown.originalParent.appendChild(dropdown);
    }
    dropdown.style.pointerEvents = "";
    dropdown.originalParent = null;
    dropdown.originalNextSibling = null;
  }
}

/**
 * Position dropdown to avoid z-index conflicts and stay within viewport
 */
function positionDropdown(button, dropdown) {
  const buttonRect = button.getBoundingClientRect();
  const dropdownWidth = 180;
  const dropdownMaxHeight = 320; // approximate max height
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const scrollY = window.scrollY;
  const scrollX = window.scrollX;

  // Calculate initial position
  let left = buttonRect.right - dropdownWidth;
  let top = buttonRect.bottom + 8;

  // Adjust horizontal position to stay within viewport
  if (left < 10) {
    left = buttonRect.left;
  }
  if (left + dropdownWidth > viewportWidth - 10) {
    left = viewportWidth - dropdownWidth - 10;
  }

  // Adjust vertical position if dropdown would go below viewport
  if (top + dropdownMaxHeight > viewportHeight + scrollY) {
    // Show above button instead
    top = buttonRect.top - dropdownMaxHeight - 8;

    // If still doesn't fit, position at top of viewport
    if (top < scrollY + 10) {
      top = scrollY + 10;
    }
  }

  // Apply positioning with maximum z-index to ensure it's above everything
  dropdown.style.position = "fixed";
  dropdown.style.left = left + "px";
  dropdown.style.top = top + "px";
  dropdown.style.zIndex = "2147483647";
  dropdown.style.maxHeight = "300px";
  dropdown.style.overflowY = "auto";
  dropdown.style.isolation = "isolate";
  dropdown.style.contain = "layout style paint";
  dropdown.style.transform = "translateZ(0)";
  dropdown.style.webkitTransform = "translateZ(0)";

  // Add backdrop blur effect if supported
  if (CSS.supports("backdrop-filter", "blur(8px)")) {
    dropdown.style.backdropFilter = "blur(8px)";
  }
}

/**
 * Get current theme from CSS link href
 */
function getCurrentThemeFromCSS(cssHref) {
  const themePaths = {
    "/css/style.css": "default",
    "/css/dark.css": "dark",
    "/css/minimal.css": "minimal",
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

/**
 * Switch theme with smooth transition
 */
function switchTheme(theme) {
  // Add transition class for smooth theme switching
  document.body.classList.add("theme-transition");

  // Update CSS immediately for smooth transition
  const themeCSS = document.getElementById("theme-css");
  const themePaths = {
    default: "/css/style.css",
    dark: "/css/dark.css",
    minimal: "/css/minimal.css",
    corporate: "/css/corporate.css",
    retro: "/css/retro.css",
  };

  if (themePaths[theme] && themeCSS) {
    // Preload the new theme CSS to avoid flash
    preloadThemeCSS(themePaths[theme])
      .then(() => {
        themeCSS.href = themePaths[theme];
        // Update body data attribute for theme-specific styling
        document.body.setAttribute("data-theme", theme);
      })
      .catch(() => {
        // Fallback: just change the CSS href
        themeCSS.href = themePaths[theme];
        document.body.setAttribute("data-theme", theme);
      });
  }

  // Send AJAX request to save preference
  saveThemePreference(theme);

  // Remove transition class after animation
  setTimeout(() => {
    document.body.classList.remove("theme-transition");
  }, 300);

  // Show brief confirmation (optional)
  showThemeChangeConfirmation(theme);
}

/**
 * Preload theme CSS to avoid visual flash
 */
function preloadThemeCSS(href) {
  return new Promise((resolve, reject) => {
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = href;
    link.onload = resolve;
    link.onerror = reject;
    document.head.appendChild(link);

    // Remove the preload link after a delay
    setTimeout(() => {
      if (link.parentNode) {
        link.parentNode.removeChild(link);
      }
    }, 1000);
  });
}

/**
 * Save theme preference to server
 */
function saveThemePreference(theme) {
  fetch("/?action=switch_theme", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ theme: theme }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (!data.success) {
        console.error("Failed to save theme preference:", data.message);
        // Could show user notification here
      }
    })
    .catch((error) => {
      console.error("Error switching theme:", error);
      // Still allow theme change locally even if server request fails
      // Could show offline notification here
    });
}

/**
 * Show brief confirmation of theme change
 */
function showThemeChangeConfirmation(theme) {
  // Create and show a brief toast notification
  const toast = document.createElement("div");
  toast.className = "theme-change-toast";
  toast.textContent = `Switched to ${theme.charAt(0).toUpperCase() + theme.slice(1)} theme`;

  // Style the toast
  Object.assign(toast.style, {
    position: "fixed",
    top: "20px",
    right: "20px",
    background: "rgba(0, 0, 0, 0.8)",
    color: "white",
    padding: "12px 20px",
    borderRadius: "6px",
    fontSize: "14px",
    zIndex: "2147483646",
    opacity: "0",
    transition: "opacity 0.3s ease",
    pointerEvents: "none",
    isolation: "isolate",
    contain: "layout style paint",
  });

  document.body.appendChild(toast);

  // Animate in
  setTimeout(() => {
    toast.style.opacity = "1";
  }, 10);

  // Animate out and remove
  setTimeout(() => {
    toast.style.opacity = "0";
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }, 2000);
}

/**
 * Handle window resize to reposition dropdown if open
 */
window.addEventListener("resize", function () {
  const themeDropdown = document.getElementById("theme-dropdown");
  const themeToggle = document.getElementById("theme-toggle");

  if (
    themeDropdown &&
    themeToggle &&
    themeDropdown.classList.contains("show")
  ) {
    positionDropdown(themeToggle, themeDropdown);
  }
});

/**
 * Handle scroll to reposition dropdown if needed
 */
window.addEventListener("scroll", function () {
  const themeDropdown = document.getElementById("theme-dropdown");
  const themeToggle = document.getElementById("theme-toggle");

  if (
    themeDropdown &&
    themeToggle &&
    themeDropdown.classList.contains("show")
  ) {
    // Debounce scroll repositioning
    clearTimeout(window.themeScrollTimeout);
    window.themeScrollTimeout = setTimeout(() => {
      positionDropdown(themeToggle, themeDropdown);
    }, 10);
  }
});

// Add CSS for smooth theme transitions
const themeTransitionCSS = document.createElement("style");
themeTransitionCSS.textContent = `
  .theme-transition * {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
  }

  .theme-option {
    transition: transform 0.2s ease, background-color 0.2s ease !important;
  }
`;
document.head.appendChild(themeTransitionCSS);
