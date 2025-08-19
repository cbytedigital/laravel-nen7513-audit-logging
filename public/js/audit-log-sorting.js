/**
 * Audit Log Sorting Functionality
 */

let currentSort = {
    field: null,
    direction: null
};
function updateSortIcons(activeField, direction) {
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'sort-icon al-transition-all al-duration-200 al-w-3 al-inline-block al-text-center al-invisible';
    });
    const activeIcon = document.querySelector(`[data-sort="${activeField}"] .sort-icon`);
    if (activeIcon && direction) {
        activeIcon.classList.remove('al-invisible');
        activeIcon.classList.add('al-visible');
        if (direction === 'asc') {
            activeIcon.className = 'sort-icon fas fa-chevron-up al-text-indigo-600 al-transition-all al-duration-200 al-w-3 al-inline-block al-text-center al-visible';
        } else if (direction === 'desc') {
            activeIcon.className = 'sort-icon fas fa-chevron-down al-text-indigo-600 al-transition-all al-duration-200 al-w-3 al-inline-block al-text-center al-visible';
        }
    }
}

function initializeSorting() {
    document.querySelectorAll('.sortable').forEach(header => {
        const newHeader = header.cloneNode(true);
        header.parentNode.replaceChild(newHeader, header);
    });
    
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'sort-icon al-transition-all al-duration-200 al-w-3 al-inline-block al-text-center al-invisible';
    });

    const sortableHeaders = document.querySelectorAll('.sortable');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            
            const sortField = this.dataset.sort;
            
            let newDirection;
            if (currentSort.field !== sortField) {
                newDirection = 'asc';
            } else {
                if (currentSort.direction === 'asc') {
                    newDirection = 'desc';
                } else if (currentSort.direction === 'desc') {
                    newDirection = null;
                } else {
                    newDirection = 'asc';
                }
            }
            
            if (newDirection === null) {
                currentSort.field = null;
                currentSort.direction = null;
                
                document.querySelectorAll('.sort-icon').forEach(icon => {
                    icon.className = 'sort-icon al-transition-all al-duration-200 al-w-3 al-inline-block al-text-center al-invisible';
                });
                
                if (window.auditLogFilters) {
                    const sortFieldInput = document.querySelector('input[name="sort"]');
                    const sortDirectionInput = document.querySelector('input[name="direction"]');
                    if (sortFieldInput) sortFieldInput.value = '';
                    if (sortDirectionInput) sortDirectionInput.value = '';
                    window.auditLogFilters.updateActiveFilters();
                    window.auditLogFilters.applyFiltersAjax();
                }
            } else {
                currentSort.field = sortField;
                currentSort.direction = newDirection;
                
                updateSortIcons(sortField, newDirection);
                
                if (window.auditLogFilters) {
                    const sortFieldInput = document.querySelector('input[name="sort"]');
                    const sortDirectionInput = document.querySelector('input[name="direction"]');
                    if (sortFieldInput) sortFieldInput.value = sortField;
                    if (sortDirectionInput) sortDirectionInput.value = newDirection;
                    window.auditLogFilters.updateActiveFilters();
                    window.auditLogFilters.applyFiltersAjax();
                }
            }
        });
    });
    
    const sortFieldInput = document.querySelector('input[name="sort"]');
    const sortDirectionInput = document.querySelector('input[name="direction"]');
    
    if (sortFieldInput && sortFieldInput.value && sortDirectionInput && sortDirectionInput.value) {
        currentSort.field = sortFieldInput.value;
        currentSort.direction = sortDirectionInput.value;
        updateSortIcons(currentSort.field, currentSort.direction);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSorting);
} else {
    initializeSorting();
}