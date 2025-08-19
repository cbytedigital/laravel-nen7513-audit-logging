<tr class="audit-row al-hover:bg-gray-50 al-cursor-pointer" data-log-id="{{ $log->id }}" onclick="toggleDetails({{ $log->id }})">
    <td class="al-ps-8 al-py-4 al-text-center">
        <button
                class="expand-btn al-text-gray-400 hover:al-text-gray-600 al-transition-colors"
                onclick="(function(e){ e.stopPropagation(); toggleDetails({{ $log->id }}) })(event)">
            <i class="fas fa-chevron-down al-text-sm"></i>
        </button>

    </td>

    <td class="al-px-6 al-py-4 al-whitespace-nowrap">
        <span class="datetime al-text-sm al-text-gray-700">{{ \Carbon\Carbon::parse($log->event_time)->format('Y-m-d H:i:s') }}</span>
    </td>
    
    <td class="al-px-6 al-py-4 al-whitespace-nowrap">
        <span class="action-badge al-text-sm al-font-medium 
             {{ strtolower($log->action) === 'c'
                 ? 'al-text-green-600'
                 : (strtolower($log->action) === 'r'
                     ? 'al-text-blue-600'
                     : (strtolower($log->action) === 'u'
                         ? 'al-text-yellow-600'
                         : (strtolower($log->action) === 'd'
                             ? 'al-text-red-600'
                             : (strtolower($log->action) === 'e'
                                 ? 'al-text-purple-600'
                                 : 'al-text-gray-600')))) }}">
            {{ strtolower($log->action) === 'c'
                ? 'CREATE'
                : (strtolower($log->action) === 'r'
                    ? 'READ'
                    : (strtolower($log->action) === 'u'
                        ? 'UPDATE'
                        : (strtolower($log->action) === 'd'
                            ? 'DELETE'
                            : (strtolower($log->action) === 'e'
                                ? 'EXECUTE'
                                : strtoupper($log->action))))) }}
        </span>
    </td>
    
    <td class="al-px-6 al-py-4">
        <div class="actor-name al-text-sm al-text-gray-700">{{ $log->actor_name }}</div>
    </td>
    
    <td class="al-px-6 al-py-4">
        <div class="al-text-sm al-text-gray-700">
            <span class="object-type">{{ ucfirst($log->object_type) }}:</span>
            <span class="object-name al-text-gray-600">{{ $log->object_name }}</span>
        </div>
    </td>
    
    <td class="al-px-6 al-py-4 al-whitespace-nowrap al-text-center">
        <div class="al-flex al-items-center al-justify-center">
            <i class="outcome-indicator fas fa-{{ strtolower($log->outcome) === 'success' ? 'check-circle al-text-green-500' : (strtolower($log->outcome) === 'failure' ? 'times-circle al-text-red-500' : 'exclamation-triangle al-text-yellow-500') }} al-text-lg"></i>
        </div>
    </td>
    
    <td class="al-px-6 al-py-4 al-whitespace-nowrap">
        <button class="al-px-4 al-text-gray-700 al-py-2 al-rounded-md al-flex al-items-center al-gap-2 hover:al-bg-gray-50 al-text-sm"
                onclick="event.stopPropagation(); toggleDetails({{ $log->id }})">
            <i class="fas fa-search al-text-sm"></i>
            Bekijk details
        </button>
    </td>
</tr>

<!-- Expandable Details Row -->
<tr class="details-row al-bg-[#FCFCFC]" id="details-{{ $log->id }}" style="display: none;">
    <td class="al-w-12"></td>
    <td colspan="6" class="al-px-6 al-py-4">
        <div class="al-text-lg al-text-gray-400 al-mb-4">Details</div>
        <div class="al-w-full al-overflow-x-auto">
            <div class="al-grid al-grid-cols-3 al-gap-6 al-divide-x al-divide-gray-200 al-min-w-0 al-h-full al-max-h-24">
                @include('vendor.audit-log.partials.detail-sections', ['log' => $log])
            </div>
        </div>
    </td>
</tr>