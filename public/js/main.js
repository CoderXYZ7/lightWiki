// LiteWiki Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', autoResize);
        autoResize.call(textarea);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', confirmDelete);
    });

    // Search functionality
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearch);
    }

    // Initialize tooltips if needed
    initializeTooltips();

    // Initialize search filters
    initializeSearchFilters();
});

function autoResize() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
}

function confirmDelete(e) {
    if (!confirm('Are you sure you want to delete this?')) {
        e.preventDefault();
    }
}

function handleSearch(e) {
    const query = e.target.querySelector('input[name="q"]').value.trim();
    if (!query) {
        e.preventDefault();
        alert('Please enter a search term');
    }
}

function initializeTooltips() {
    // Add tooltips to elements with data-tooltip attribute
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.getAttribute('data-tooltip');
    document.body.appendChild(tooltip);

    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
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
const style = document.createElement('style');
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
    const tagSearchInput = document.querySelector('.tag-search-input');
    if (tagSearchInput) {
        tagSearchInput.addEventListener('input', debounce(filterTags, 300));
    }

    // Author search functionality
    const authorSearchInput = document.querySelector('.author-search-input');
    if (authorSearchInput) {
        authorSearchInput.addEventListener('input', debounce(filterAuthors, 300));
    }

    // Author selection functionality
    const authorSelector = document.getElementById('author-selector');
    if (authorSelector) {
        const authorOptions = authorSelector.querySelectorAll('.author-option');
        authorOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                authorOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                // Update hidden input
                const selectedAuthor = document.getElementById('selected-author');
                selectedAuthor.value = this.getAttribute('data-value');
            });
        });
    }
}

function filterTags(e) {
    const searchTerm = e.target.value.toLowerCase();
    const tagSelector = document.getElementById('tag-selector');
    const tagCheckboxes = tagSelector.querySelectorAll('.tag-checkbox');

    tagCheckboxes.forEach(checkbox => {
        const tagLabel = checkbox.querySelector('.tag-label');
        const tagText = tagLabel.textContent.toLowerCase();

        if (tagText.includes(searchTerm)) {
            checkbox.style.display = 'flex';
        } else {
            checkbox.style.display = 'none';
        }
    });
}

function filterAuthors(e) {
    const searchTerm = e.target.value.toLowerCase();
    const authorSelector = document.getElementById('author-selector');
    const authorOptions = authorSelector.querySelectorAll('.author-option');

    authorOptions.forEach(option => {
        const authorText = option.textContent.toLowerCase();

        if (authorText.includes(searchTerm)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}
