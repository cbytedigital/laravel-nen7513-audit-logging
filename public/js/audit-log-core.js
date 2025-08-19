/**
 * Audit Log Core Functionality
 */
function copyToClipboard(text, label) {
    // Check if clipboard API is available (HTTPS or localhost)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            showToast(`${label} gekopieerd!`, 'success');
        }).catch(function() {
            fallbackCopyToClipboard(text, label);
        });
    } else {
        // Fallback for non-secure contexts
        fallbackCopyToClipboard(text, label);
    }
}

function fallbackCopyToClipboard(text, label) {
    // Create a temporary textarea element
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    
    try {
        textArea.focus();
        textArea.select();
        const successful = document.execCommand('copy');
        if (successful) {
            showToast(`${label} gekopieerd!`, 'success');
        } else {
            showToast('Kopiëren mislukt', 'error');
        }
    } catch (err) {
        showToast('Kopiëren mislukt', 'error');
    } finally {
        document.body.removeChild(textArea);
    }
}

function showToast(message, type = 'info') {
    const existingToasts = document.querySelectorAll('.audit-toast');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    let bgColor = 'al-bg-emerald-500'; // success (default)
    if (type === 'error') {
        bgColor = 'al-bg-red-500';
    } else if (type === 'info') {
        bgColor = 'al-bg-blue-500';
    }
    
    toast.className = `audit-toast al-fixed al-bottom-4 al-right-4 al-z-50 al-flex al-items-center al-gap-3 al-px-4 al-py-3 al-rounded-lg al-shadow-lg al-transition-all al-duration-300 ${bgColor} al-text-white al-text-sm al-font-medium`;

    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="al-ml-2 al-bg-transparent al-border-0 al-text-white al-cursor-pointer hover:al-opacity-75">
            <i class="fas fa-times al-text-xs"></i>
        </button>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }
    }, 3000);
}

function toggleDetails(logId) {
    const detailsRow = document.getElementById(`details-${logId}`);
    const parentRow = document.querySelector(`[data-log-id="${logId}"]`);
    
    if (detailsRow && parentRow) {
        if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
            detailsRow.style.display = 'table-row';
            parentRow.classList.add('audit-row-expanded');
            
            const expandIcon = parentRow.querySelector('.expand-btn i');
            if (expandIcon) {
                expandIcon.className = 'fas fa-chevron-up al-text-sm';
            }
        } else {
            detailsRow.style.display = 'none';
            parentRow.classList.remove('audit-row-expanded');
            
            const expandIcon = parentRow.querySelector('.expand-btn i');
            if (expandIcon) {
                expandIcon.className = 'fas fa-chevron-down al-text-sm';
            }
        }
    }
}

function applyMiddleTruncation(text, maxLength = 20) {
    if (text.length <= maxLength) {
        return text;
    }
    
    const startLength = Math.ceil((maxLength - 3) / 2);
    const endLength = Math.floor((maxLength - 3) / 2);
    
    return text.substring(0, startLength) + '...' + text.substring(text.length - endLength);
}

function initializeMiddleTruncation() {
    document.querySelectorAll('.middle-truncate').forEach(element => {
        const fullText = element.getAttribute('data-full-text');
        const textSpan = element.querySelector('span');
        if (fullText && textSpan) {
            textSpan.textContent = applyMiddleTruncation(fullText, 20);
        }
    });
}

function exportAuditLogs() {
    const currentParams = new URLSearchParams(window.location.search);
    
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput && searchInput.value.trim()) {
        currentParams.set('search', searchInput.value.trim());
    }
    
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const formData = new FormData(filterForm);
        for (const [key, value] of formData.entries()) {
            if (typeof value === 'string' && value.trim() !== '') {
                currentParams.append(key, value);
            }
        }
    }
    
    const baseUrl = window.location.pathname.replace(/\/$/, '');
    const exportUrl = `${baseUrl}/export?${currentParams.toString()}`;
    
    showExportLoading();
    
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        hideExportLoading();
        showToast('Export gestart! Download begint automatisch.', 'success');
    }, 1000);
}

function showExportLoading() {
    const exportButton = document.querySelector('.btn-export');
    if (exportButton) {
        exportButton.disabled = true;
        exportButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporteren...';
    }
}

function hideExportLoading() {
    const exportButton = document.querySelector('.btn-export');
    if (exportButton) {
        exportButton.disabled = false;
        exportButton.innerHTML = '<i class="fas fa-download"></i> Export';
    }
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
