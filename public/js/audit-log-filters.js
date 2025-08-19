/**
 * Audit Log Filter System
 */

class AuditLogFilters {
    constructor() {
        this.sidebar = document.getElementById('filterSidebar');
        this.form = document.getElementById('filterForm');
        this.activeFilters = new Map();
        
        if (!this.sidebar || !this.form) {
            return;
        }
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadFiltersFromUrl();
        this.updateActiveFilters();
        this.updateUI();
    }

    bindEvents() {
        const filterToggle = document.getElementById('filterToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        
        if (filterToggle) {
            filterToggle.addEventListener('click', () => this.toggleSidebar());
        }
        
        if (closeSidebar) {
            closeSidebar.addEventListener('click', () => this.closeSidebar());
        }

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
        });

        document.getElementById('clearAllFilters').addEventListener('click', () => this.clearAllFilters());

        this.form.addEventListener('change', (e) => {
            this.updateActiveFilters();
            this.updateUI();
            
            if (e.target.type === 'checkbox' || e.target.type === 'date') {
                this.applyFiltersAjax();
            }
        });
        
        this.form.addEventListener('input', debounce((e) => {
            this.updateActiveFilters();
            this.updateUI();
            
            if (e.target.type === 'text') {
                this.applyFiltersAjax();
            }
        }, 500)); // 500ms delay for text inputs

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeSidebar();
        });
    }

    toggleSidebar() {
        if (this.sidebar.classList.contains('al-translate-x-full')) {
            this.openSidebar();
        } else {
            this.closeSidebar();
        }
    }

    openSidebar() {
        this.sidebar.classList.remove('al-translate-x-full');
    }

    closeSidebar() {
        this.sidebar.classList.add('al-translate-x-full');
    }

    loadFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        
        this.setInputValue('dateFrom', params.get('date_from'));
        this.setInputValue('dateTo', params.get('date_to'));
        
        this.setInputValue('actorSearch', params.get('actor_search'));
        this.setInputValue('sourceSearch', params.get('source_search'));
        this.setInputValue('objectIdSearch', params.get('object_search'));
        this.setInputValue('objectTypeSearch', params.get('object_type_search'));
        this.setInputValue('sourceTypeSearch', params.get('source_type_search'));
        this.setInputValue('userAgentSearch', params.get('user_agent_search'));

        this.loadCheckboxFilters('actions[]', params.getAll('actions[]'));
        this.loadCheckboxFilters('outcomes[]', params.getAll('outcomes[]'));
    }

    setInputValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element && value) {
            element.value = value;
        }
    }

    loadCheckboxFilters(name, values) {
        values.forEach(value => {
            const checkbox = document.querySelector(`input[name="${name}"][value="${value}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }

    updateActiveFilters() {
        this.activeFilters.clear();
        const formData = new FormData(this.form);

        for (const [key, value] of formData.entries()) {
            if (key === 'sort' || key === 'direction') {
                continue;
            }
            
            if (typeof value === 'string' && value.trim() !== '') {
                if (!this.activeFilters.has(key)) {
                    this.activeFilters.set(key, []);
                }
                this.activeFilters.get(key).push(value);
            }
        }

        this.updateUI();
    }

    updateUI() {
        this.updateFilterButton();
        this.updateFilterPills();
        this.updateSidebarCounter();
    }

    updateFilterButton() {
        const filterCount = document.getElementById('filterCount');
        const filterText = document.getElementById('filterText');
        const totalFilters = Array.from(this.activeFilters.values()).reduce((sum, arr) => sum + arr.length, 0);

        if (totalFilters > 0) {
            filterCount.textContent = `${totalFilters} Actieve filter${totalFilters > 1 ? 's' : ''}`;
            filterCount.classList.remove('al-hidden');
            filterText.classList.add('al-hidden');
        } else {
            filterCount.classList.add('al-hidden');
            filterText.classList.remove('al-hidden');
        }
    }

    updateSidebarCounter() {
        const counter = document.getElementById('activeFilterCount');
        const totalFilters = Array.from(this.activeFilters.values()).reduce((sum, arr) => sum + arr.length, 0);

        if (totalFilters > 0) {
            counter.textContent = totalFilters;
            counter.classList.remove('al-hidden');
        } else {
            counter.classList.add('al-hidden');
        }
    }

    updateFilterPills() {
        const container = document.getElementById('filterPillsContainer');
        const pillsSection = document.getElementById('activeFiltersPills');
        
        container.innerHTML = '';

        if (this.activeFilters.size === 0) {
            pillsSection.classList.add('al-hidden');
            return;
        }

        pillsSection.classList.remove('al-hidden');

        for (const [key, values] of this.activeFilters.entries()) {
            values.forEach(value => {
                const pill = this.createFilterPill(key, value);
                container.appendChild(pill);
            });
        }
    }

    createFilterPill(key, value) {
        const pill = document.createElement('div');
        pill.className = 'al-flex al-items-center al-gap-2 al-py-1';
        
        const label = this.getFilterLabel(key);
        const displayValue = this.getDisplayValue(key, value);
        
        pill.innerHTML = `
            <button type="button" class="al-text-gray-400 hover:al-text-gray-600 al-flex al-items-center al-justify-center al-w-4 al-h-4" data-filter-key="${key}" data-filter-value="${value}">
                <i class="fas fa-times al-text-xs"></i>
            </button>
            <span class="al-text-sm al-text-gray-600 al-italic">${label}: ${displayValue}</span>
        `;
        
        const removeButton = pill.querySelector('button');
        removeButton.addEventListener('click', () => {
            this.removeFilter(key, value);
        });
        
        return pill;
    }

    getFilterLabel(key) {
        const labels = {
            'date_from': 'Van',
            'date_to': 'Tot',
            'actions[]': 'Actie',
            'actor_search': 'Actor',
            'object_search': 'Object ID',
            'object_type_search': 'Object Type',
            'outcomes[]': 'Uitkomst',
            'source_search': 'Source ID',
            'source_type_search': 'Source Type',
            'user_agent_search': 'User Agent',
            'sort': 'Sortering',
            'direction': 'Richting'
        };
        return labels[key] || key.replace('[]', '').replace('_', ' ');
    }

    getDisplayValue(key, value) {
        if (key === 'sort') {
            const sortLabels = {
                'datetime': 'Datum/Tijd',
                'action': 'Actie', 
                'actor': 'Actor',
                'object': 'Object',
                'outcome': 'Uitkomst'
            };
            return sortLabels[value] || value;
        }
        
        if (key === 'direction') {
            return value === 'asc' ? '↑ Oplopend' : '↓ Aflopend';
        }
        

        if (key === 'actions[]') {
            const actionLabels = {
                'c': 'CREATE',
                'r': 'READ',
                'u': 'UPDATE', 
                'd': 'DELETE',
                'e': 'EXECUTE'
            };
            return actionLabels[value.toLowerCase()] || value.toUpperCase();
        }
        
        if (value.length > 20) {
            return value.substring(0, 17) + '...';
        }
        return value;
    }

    removeFilter(key, value) {
        if (key === 'sort' || key === 'direction') {
            const sortField = document.querySelector('input[name="sort"]');
            const sortDirection = document.querySelector('input[name="direction"]');
            if (sortField) sortField.value = '';
            if (sortDirection) sortDirection.value = '';
            
            if (typeof currentSort !== 'undefined') {
                currentSort.field = null;
                currentSort.direction = null;
            }
            
            document.querySelectorAll('.sort-icon').forEach(icon => {
                icon.className = 'sort-icon al-transition-all al-duration-200 al-w-3 al-inline-block al-text-center al-invisible';
            });
        } else if (key.endsWith('[]')) {
            const checkbox = document.querySelector(`input[name="${key}"][value="${value}"]`);
            if (checkbox) checkbox.checked = false;
        } else {
            const input = document.querySelector(`input[name="${key}"], select[name="${key}"]`);
            if (input) input.value = '';
        }

        this.updateActiveFilters();
        
        this.applyFiltersAjax();
    }

    applyFiltersAjax() {
        const formData = new FormData(this.form);
        const params = new URLSearchParams();
        
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && searchInput.value.trim()) {
            params.append('search', searchInput.value.trim());
        }
        
        for (const [key, value] of formData.entries()) {
            if (typeof value === 'string' && value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        this.showLoadingState();
        const url = `${window.location.pathname}?${params.toString()}`;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            window.history.pushState({}, '', url);
            updateTableWithData(data);
            this.hideLoadingState();
        })
        .catch(() => {
            this.hideLoadingState();
        });
    }

    showLoadingState() {
        const tableContainer = document.querySelector('.audit-table');
        if (tableContainer) {
            tableContainer.style.opacity = '0.6';
            tableContainer.style.pointerEvents = 'none';
        }
        
        let loadingIndicator = document.getElementById('loadingIndicator');
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'loadingIndicator';
            loadingIndicator.className = 'al-fixed al-bottom-4 al-left-4 al-bg-blue-600 al-text-white al-px-4 al-py-2 al-rounded-md al-shadow-lg al-z-50';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin al-mr-2"></i>Filters toepassen...';
            document.body.appendChild(loadingIndicator);
        }
        loadingIndicator.classList.remove('al-hidden');
    }
    
    hideLoadingState() {
        const tableContainer = document.querySelector('.audit-table');
        if (tableContainer) {
            tableContainer.style.opacity = '';
            tableContainer.style.pointerEvents = '';
        }
        
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.classList.add('al-hidden');
        }
    }

    clearAllFilters() {
        this.resetForm();
        this.applyFiltersAjax();
    }

    resetForm() {
        this.form.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
            input.value = '';
        });
        
        this.form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        this.form.querySelectorAll('input[type="hidden"]').forEach(hidden => {
            hidden.value = '';
        });
        
        this.updateActiveFilters();
    }
}
