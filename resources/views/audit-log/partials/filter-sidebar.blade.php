<!-- Filter Sidebar -->
<div id="filterSidebar" class="al-fixed al-top-0 al-right-0 al-h-full al-w-80 al-bg-white al-shadow-xl al-z-50 al-transform al-translate-x-full al-transition-transform al-duration-300 al-ease-in-out al-flex al-flex-col">
    <!-- Sidebar Header -->
    <div class="al-flex al-items-center al-justify-between al-p-4 al-border-b">
        <div class="al-flex al-items-center al-gap-2">
            <i class="fas fa-filter al-text-gray-600"></i>
            <h3 class="al-text-lg al-font-semibold al-text-gray-900">Filters</h3>
            <span id="activeFilterCount" class="al-hidden al-bg-blue-100 al-text-blue-800 al-text-xs al-px-2 al-py-1 al-rounded-full"></span>
        </div>
        <button id="closeSidebar" class="al-text-gray-400 hover:al-text-gray-600 al-p-1">
            <i class="fas fa-times al-text-lg"></i>
        </button>
    </div>

    <!-- Active Filters Pills -->
    <div id="activeFiltersPills" class="al-p-4 al-border-b al-hidden">
        <div class="al-flex al-flex-col al-gap-1" id="filterPillsContainer"></div>
        <button id="clearAllFilters" class="al-text-xs al-text-blue-600 hover:al-text-blue-800 al-mt-2">
            Alle filters wissen
        </button>
    </div>

    <!-- Filter Form -->
    <div class="al-flex-1 al-overflow-y-auto al-px-4 al-py-3">
        <form id="filterForm" class="al-space-y-4">
            <!-- Hidden fields for sort parameters -->
            <input type="hidden" name="sort" value="{{ request('sort', 'datetime') }}">
            <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
            
            <!-- Datum/Tijd Range -->
            <div class="al-space-y-3 al-pb-6 al-border-b al-border-gray-200">
                <label class="al-block al-text-sm al-font-normal al-text-gray-600">Datum / tijd range</label>
                <div class="al-flex al-items-center al-gap-2">
                    <i class="fas fa-calendar al-text-gray-400 al-text-sm al-flex-shrink-0"></i>
                    <input type="date" name="date_from" id="dateFrom" value="{{ request('date_from') }}"
                           class="al-flex-1 al-px-2 al-py-2 al-border al-border-gray-300 al-rounded-md al-text-xs focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500 al-min-w-0">
                    <span class="al-text-xs al-text-gray-500 al-flex-shrink-0">tot</span>
                    <input type="date" name="date_to" id="dateTo" value="{{ request('date_to') }}"
                           class="al-flex-1 al-px-2 al-py-2 al-border al-border-gray-300 al-rounded-md al-text-xs focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500 al-min-w-0">
                </div>
            </div>

            <!-- Actie Multi-Select -->
            <div class="al-space-y-3 al-pb-6 al-border-b al-border-gray-200">
                <label class="al-block al-text-sm al-font-normal al-text-gray-600">Actie</label>
                <div class="al-space-y-1">
                    @php
                        $actionMap = [
                            'c' => 'CREATE',
                            'r' => 'READ', 
                            'u' => 'UPDATE',
                            'd' => 'DELETE',
                            'e' => 'EXECUTE'
                        ];
                    @endphp
                    @foreach($actionMap as $code => $name)
                        <label class="al-flex al-items-center al-cursor-pointer al-py-1 al-px-2 al-rounded hover:al-bg-gray-50">
                            <input type="checkbox" name="actions[]" value="{{ $code }}" 
                                   {{ in_array($code, request('actions', [])) ? 'checked' : '' }}
                                   class="checkbox-custom al-appearance-none al-mr-3 al-w-5 al-h-5 al-text-blue-400 al-bg-white al-border al-border-gray-300 al-rounded focus:al-ring-2 checked:al-bg-blue-500 checked:al-border-blue-500">
                            <span class="al-text-sm al-text-gray-600">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Actor -->
            <div class="al-space-y-3 al-pb-6 al-border-b al-border-gray-200">
                <label class="al-block al-text-sm al-font-normal al-text-gray-600">Actor</label>
                <div class="al-relative">
                    <input type="text" name="actor_search" id="actorSearch" placeholder="Actor zoeken" 
                           value="{{ request('actor_search') }}"
                           class="al-w-full al-px-3 al-py-2 al-pl-8 al-border al-border-gray-300 al-rounded-md al-text-sm focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500">
                    <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-xs al-text-gray-400"></i>
                </div>
            </div>

            <!-- Object -->
            <div class="al-space-y-3 al-pb-6 al-border-b al-border-gray-200">
                <label class="al-block al-text-sm al-font-normal al-text-gray-600">Object</label>
                
                <!-- Object Type Search -->
                <div class="al-relative">
                    <input type="text" name="object_type_search" id="objectTypeSearch" placeholder="Object type zoeken" 
                           value="{{ request('object_type_search') }}"
                           class="al-w-full al-px-3 al-py-2 al-pl-8 al-border al-border-gray-300 al-rounded-md al-text-sm focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500">
                    <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-xs al-text-gray-400"></i>
                </div>

                <!-- Object ID Search -->
                <div class="al-relative">
                    <input type="text" name="object_search" id="objectIdSearch" placeholder="Object ID zoeken"
                           value="{{ request('object_search') }}"
                           class="al-w-full al-px-3 al-py-2 al-pl-8 al-border al-border-gray-300 al-rounded-md al-text-sm focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500">
                    <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-xs al-text-gray-400"></i>
                </div>
            </div>

            <!-- Uitkomst -->
            <div class="al-space-y-3 al-pb-6 al-border-b al-border-gray-200">
                <label class="al-block al-text-sm al-font-normal al-text-gray-600">Uitkomst</label>
                <div class="al-space-y-1">
                    @foreach(['success', 'failure', 'unknown'] as $outcome)
                        <label class="al-flex al-items-center al-cursor-pointer al-py-1 al-px-2 al-rounded hover:al-bg-gray-50">
                            <input type="checkbox" name="outcomes[]" value="{{ $outcome }}" 
                                   {{ in_array($outcome, request('outcomes', [])) ? 'checked' : '' }}
                                   class="checkbox-custom al-appearance-none al-mr-3 al-w-5 al-h-5 al-text-blue-400 al-bg-white al-border al-border-gray-300 al-rounded focus:al-ring-2 checked:al-bg-blue-500 checked:al-border-blue-500">
                            <span class="al-text-sm al-text-gray-600">{{ ucfirst(strtolower($outcome)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Source -->
            <div class="al-space-y-3">
                <label class="al-block al-text-sm al-font-normal al-text-gray-600">Source</label>
                
                <!-- Source ID Search -->
                <div class="al-relative">
                    <input type="text" name="source_search" id="sourceSearch" placeholder="Source ID zoeken"
                           value="{{ request('source_search') }}"
                           class="al-w-full al-px-3 al-py-2 al-pl-8 al-border al-border-gray-300 al-rounded-md al-text-sm focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500">
                    <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-xs al-text-gray-400"></i>
                </div>
                
                <!-- Source Type Search -->
                <div class="al-relative">
                    <input type="text" name="source_type_search" id="sourceTypeSearch" placeholder="Source type zoeken"
                           value="{{ request('source_type_search') }}"
                           class="al-w-full al-px-3 al-py-2 al-pl-8 al-border al-border-gray-300 al-rounded-md al-text-sm focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500">
                    <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-xs al-text-gray-400"></i>
                </div>

                <!-- User Agent Search -->
                <div class="al-relative">
                    <input type="text" name="user_agent_search" id="userAgentSearch" placeholder="User Agent zoeken"
                           value="{{ request('user_agent_search') }}"
                           class="al-w-full al-px-3 al-py-2 al-pl-8 al-border al-border-gray-300 al-rounded-md al-text-sm focus:al-outline-none focus:al-ring-2 focus:al-ring-blue-500">
                    <i class="fas fa-search al-absolute al-left-3 al-top-1/2 al-transform -al-translate-y-1/2 al-text-xs al-text-gray-400"></i>
                </div>
            </div>
        </form>
    </div>
</div>
