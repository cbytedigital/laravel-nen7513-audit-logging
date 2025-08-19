<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('audit-log.ui.title', 'Audit Logs') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('vendor/audit-log/css/audit-logs.css') }}" rel="stylesheet">
    
    <style>
        :root {
            --expanded-row-color: {{ config('audit-log.ui.expanded_row_color', '#fef3c7') }};
        }
    </style>
    
</head>

<body class="al-scope">
    <div class="al-bg-custom-bg al-min-h-screen">
        <!-- Header -->
        <div class="al-max-w-7xl al-mx-auto al-px-4 al-pt-6 al-flex al-justify-between al-items-center">
            <div class="al-flex al-items-center">
                @php
                    $logoType = config('audit-log.ui.logo.type', 'file');
                    $logoPath = config('audit-log.ui.logo.path', '/images/logo.png');
                    $logoAlt = config('audit-log.ui.logo.alt', 'Logo');
                    
                    if ($logoType === 'url') {
                        $logoSrc = $logoPath;
                    } else {
                        $logoSrc = asset($logoPath);
                    }
                @endphp
                <img src="{{ $logoSrc }}" alt="{{ $logoAlt }}" class="al-h-10">
            </div>
            <button class="btn-export al-bg-white al-text-gray-700 al-border al-rounded-md al-px-4 al-py-2 al-flex al-items-center al-gap-2 hover:al-bg-gray-50"
                    onclick="exportAuditLogs()">
                <i class="fas fa-download"></i>
                Export
            </button>
        </div>

        <div class="al-max-w-7xl al-mx-auto al-px-4 al-py-6">
            <!-- Combined Card: Header + Table -->
            <div class="al-bg-white al-rounded-md al-shadow-sm al-border al-overflow-hidden">
                <!-- Header -->
                <header class="al-p-6 al-border-b">
                    <div class="al-flex al-flex-col sm:al-flex-row al-justify-between al-items-start sm:al-items-center al-gap-4">
                        <div class="al-flex al-flex-col sm:al-flex-row al-items-start sm:al-items-center al-gap-4 al-flex-1">
                            <h1 class="al-text-xl al-text-gray-700">{{ config('audit-log.ui.title', 'Audit Logs') }}</h1>
                            <div class="al-relative al-flex-1 al-max-w-md">
                                <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-gray-400"></i>
                                <input type="text" name="search" value="{{ request('search') }}"
                                       class="al-w-full al-pl-10 al-pr-4 al-py-2 al-border al-border-gray-300 al-rounded-md focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500 focus:al-border-transparent"
                                       placeholder="Zoeken" id="searchInput">
                            </div>
                        </div>
                        <div class="al-flex al-items-center al-gap-3">
                            <!-- Per Page Selector -->
                            <div class="al-flex al-items-center al-gap-2">
                                <label for="perPageSelect" class="al-text-sm hover:al-border hover:al-border-color-[#9ca3af] al-min-w-[70px] al-cursor-pointer al-text-gray-600 al-whitespace-nowrap">Items per pagina:</label>
                                <select id="perPageSelect" 
                                        class="al-px-3 al-py-1.5 al-text-sm al-border al-border-gray-300 al-rounded-md al-bg-white al-text-gray-700 focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500 focus:al-border-blue-500 al-transition-colors"
                                        onchange="changePerPage(this.value)">
                                    <option value="10" {{ request('per_page', 25) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page', 25) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page', 25) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            
                            <button id="filterToggle"
                                    class="btn-filters al-bg-white al-text-gray-700 al-px-4 al-py-2 al-rounded-md al-flex al-items-center al-gap-2 hover:al-bg-gray-50">
                                <i class="fas fa-filter"></i>
                                <span id="filterCount" class="al-hidden">3 Actieve filters</span>
                                <span id="filterText">Filters</span>
                            </button>
                        </div>
                    </div>
                </header>

                @include('vendor.audit-log.partials.filter-sidebar')

                <!-- Audit Logs Table -->
                <div class="al-overflow-x-auto">
                    <table class="audit-table al-w-full al-table-auto al-min-w-max">
                        @include('vendor.audit-log.partials.table-header')
                        <tbody class="al-divide-y al-divide-gray-200">
                            @foreach ($maskedLogs as $log)
                                @include('vendor.audit-log.partials.table-row', ['log' => $log])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="paginationContainer" class="al-p-6 al-border-t al-flex al-justify-between al-items-center">
                    <div id="paginationInfo" class="pagination-info al-text-sm al-text-gray-700">
                        @if (method_exists($maskedLogs, 'total'))
                            {{ $maskedLogs->firstItem() }} - {{ $maskedLogs->lastItem() }} van {{ $maskedLogs->total() }} items
                        @else
                            {{ $maskedLogs->count() }} items
                        @endif
                    </div>

                    @if (method_exists($maskedLogs, 'total'))
                        <div class="al-flex al-gap-2">
                            <button class="pagination-prev al-px-4 al-py-2 al-text-sm al-border al-rounded al-transition-colors {{ $maskedLogs->onFirstPage() ? 'al-bg-gray-100 al-text-gray-400 al-cursor-not-allowed' : 'al-bg-white al-text-gray-700 hover:al-bg-gray-50' }}"
                                    {{ $maskedLogs->onFirstPage() ? 'disabled' : '' }}
                                    onclick="window.location='{{ $maskedLogs->previousPageUrl() }}'">
                                Terug
                            </button>
                            <button class="pagination-next al-px-4 al-py-2 al-text-sm al-border al-rounded al-transition-colors {{ $maskedLogs->hasMorePages() ? 'al-bg-white al-text-gray-700 hover:al-bg-gray-50' : 'al-bg-gray-100 al-text-gray-400 al-cursor-not-allowed' }}"
                                    {{ $maskedLogs->hasMorePages() ? '' : 'disabled' }}
                                    onclick="window.location='{{ $maskedLogs->nextPageUrl() }}'">
                                Volgende
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="{{ asset('vendor/audit-log/js/audit-log-core.js') }}"></script>
    <script src="{{ asset('vendor/audit-log/js/audit-log-sorting.js') }}"></script>
    <script src="{{ asset('vendor/audit-log/js/audit-log-table.js') }}"></script>
    <script src="{{ asset('vendor/audit-log/js/audit-log-filters.js') }}"></script>
    
    <script>
        // Change per page function
        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page'); // Reset to first page when changing per_page
            window.location = url.toString();
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeSearch();
            initializeSorting();
            initializeMiddleTruncation();
            
            // Make filters globally available
            window.auditLogFilters = new AuditLogFilters();
        });
    </script>
</body>

</html>
