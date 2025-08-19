/**
 * Audit Log Table Functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            if (window.auditLogFilters) {
                window.auditLogFilters.applyFiltersAjax();
            }
        }, 500));
    }
}

function updateTableWithData(data) {
    const tbody = document.querySelector('.audit-table tbody');
    if (!tbody) return;
    
    const expandedRows = new Set();
    document.querySelectorAll('.audit-row-expanded').forEach(row => {
        const logId = row.getAttribute('data-log-id');
        if (logId) expandedRows.add(logId);
    });
    
    if (data.total > 0 && data.html && data.html.trim() !== '') {
        tbody.innerHTML = data.html;
        
        expandedRows.forEach(logId => {
            const row = tbody.querySelector(`[data-log-id="${logId}"]`);
            const detailRow = tbody.querySelector(`#details-${logId}`);
            
            if (row && detailRow) {
                row.classList.add('audit-row-expanded');
                detailRow.classList.remove('al-hidden');
                detailRow.style.display = 'table-row';
                
                const chevron = row.querySelector(`#chevron-${logId}`);
                if (chevron) {
                    chevron.classList.remove('fa-chevron-right');
                    chevron.classList.add('fa-chevron-down');
                }
            }
        });
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="al-px-6 al-py-8 al-text-center al-text-gray-500">
                    Geen audit logs gevonden met de huidige filters.
                </td>
            </tr>
        `;
    }
    
    updatePagination(data);
    restoreSortState();
    rebindTableEvents();
}

function updatePagination(data) {
    const paginationInfo = document.querySelector('.pagination-info');
    if (paginationInfo && data) {
        const from = data.from || (data.current_page > 0 ? ((data.current_page - 1) * data.per_page) + 1 : 0);
        const to = data.to || Math.min(from + data.per_page - 1, data.total || 0);
        const total = data.total || 0;
        
        paginationInfo.textContent = `${from || 0} - ${to || 0} van ${total} items`;
    }
    
    // Update per-page selector to reflect current value
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect && data && data.per_page) {
        perPageSelect.value = data.per_page;
    }
}

function restoreSortState() {
    const sortField = document.querySelector('input[name="sort"]');
    const sortDirection = document.querySelector('input[name="direction"]');
    
    if (sortField && sortField.value && sortDirection && sortDirection.value) {
        if (typeof currentSort !== 'undefined') {
            currentSort.field = sortField.value;
            currentSort.direction = sortDirection.value;
        }
        if (typeof updateSortIcons === 'function') {
            updateSortIcons(sortField.value, sortDirection.value);
        }
    }
}

function rebindTableEvents() {
    if (typeof initializeCopyFunctionality === 'function') {
        initializeCopyFunctionality();
    }
    
    if (typeof initializeSorting === 'function') {
        initializeSorting();
    }
}

function initializeTable() {
    initializeSearch();
    rebindTableEvents();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTable);
} else {
    initializeTable();
}