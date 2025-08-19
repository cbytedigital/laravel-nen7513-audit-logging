<!-- Source Details -->
<div class="al-space-y-4 al-pr-6 al-min-w-0">
    <h4 class="al-text-lg al-font-medium al-text-gray-700 al-mb-4">Source</h4>
    <div class="al-space-y-2">
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Source ID</span>
            <div class="copyable-group">
                <span class="al-text-sm al-text-gray-700 al-max-w-48 al-cursor-pointer hover:al-bg-gray-100 al-px-2 al-py-1 al-rounded middle-truncate al-inline-flex al-items-center al-gap-2" 
                      onclick="copyToClipboard('{{ $log->source_id ?? 'patient_portal' }}', 'Source ID')" 
                      title="Click to copy Source ID" 
                      data-full-text="{{ $log->source_id ?? 'patient_portal' }}">
                    <span>{{ $log->source_id ?? 'patient_portal' }}</span>
                    <i class="fas fa-copy copy-btn al-text-xs al-text-gray-400"></i>
                </span>
            </div>
        </div>
        <div class="al-flex">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Source type</span>
            <span class="al-text-sm al-text-gray-700 al-truncate al-max-w-48 al-px-2 al-py-1">{{ $log->source_type ?? 'laravel_application' }}</span>
        </div>
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">IP-adres</span>
            <div class="copyable-group">
                <span class="al-text-sm al-text-gray-700 al-truncate al-max-w-48 al-cursor-pointer hover:al-bg-gray-100 al-px-2 al-py-1 al-rounded al-inline-flex al-items-center al-gap-2" 
                      onclick="copyToClipboard('{{ $log->ip_address ?? '192.168.1.100' }}', 'IP-adres')" 
                      title="Click to copy IP-adres">
                    <span>{{ $log->ip_address ?? '192.168.1.100' }}</span>
                    <i class="fas fa-copy copy-btn al-text-xs al-text-gray-400"></i>
                </span>
            </div>
        </div>
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">User Agent</span>
            <div class="copyable-group">
                <span class="al-text-sm al-text-gray-700 al-max-w-32 al-cursor-pointer hover:al-bg-gray-100 al-px-2 al-py-1 al-rounded al-inline-flex al-items-center al-gap-2" 
                      onclick="copyToClipboard('{{ addslashes($log->user_agent ?? 'Mozilla/5.0 (...)') }}', 'User Agent')" 
                      title="Click to copy User Agent">
                    <span>{{ $log->user_agent ?? 'Mozilla/5.0 (...)' }}</span>
                    <i class="fas fa-copy copy-btn al-text-xs al-text-gray-400"></i>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Actor + Security Details -->
<div class="al-space-y-4 al-px-6 al-min-w-0">
    <h4 class="al-text-lg al-font-medium al-text-gray-700 al-mb-4">Actor</h4>
    <div class="al-space-y-2">
        <div class="al-flex">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Actor type</span>
            <span class="al-text-sm al-max-w-48 al-text-gray-700 al-px-2 al-py-1" 
                  style="direction: rtl; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $log->actor_type }}</span>
        </div>
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Actor ID</span>
            <div class="copyable-group">
                <span class="al-text-sm al-text-gray-700 al-truncate al-max-w-48 al-cursor-pointer hover:al-bg-gray-100 al-px-2 al-py-1 al-rounded al-inline-flex al-items-center al-gap-2" 
                      onclick="copyToClipboard('{{ $log->actor_id }}', 'Actor ID')" 
                      title="Click to copy Actor ID">
                    <span>{{ $log->actor_id }}</span>
                    <i class="fas fa-copy copy-btn al-text-xs al-text-gray-400"></i>
                </span>
            </div>
        </div>
        <div class="al-flex">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Actor naam</span>
            <span class="al-text-sm al-text-gray-700 al-px-2 al-py-1">{{ $log->actor_name ?? 'System' }}</span>
        </div>
        <div class="al-flex">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Actor rol</span>
            <span class="al-text-sm al-text-gray-700 al-px-2 al-py-1">{{ $log->actor_role ?? 'Gebruiker' }}</span>
        </div>
    </div>

    <h4 class="al-text-lg al-font-medium al-text-gray-700 al-mb-4 al-mt-6">Beveiliging</h4>
    <div class="al-space-y-2">
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-32 al-flex-shrink-0">Autorisatie</span>
            <div class="al-flex al-items-center al-gap-2">
                <i class="fas fa-{{ $log->authorization_check ?? false ? 'check al-text-green-500' : 'times al-text-red-500' }}"></i>
                <span class="al-text-sm al-text-gray-700 al-truncate al-max-w-24 al-px-2 al-py-1">{{ $log->authorization_protocol ?? 'oauth2' }}</span>
            </div>
        </div>
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-32 al-flex-shrink-0">Behandelrelatie</span>
            <i class="fas fa-{{ $log->treatment_relation_check ?? false ? 'check al-text-green-500' : 'times al-text-red-500' }}"></i>
        </div>
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-32 al-flex-shrink-0">Toestemming</span>
            <i class="fas fa-{{ $log->consent_check ?? false ? 'check al-text-green-500' : 'times al-text-red-500' }}"></i>
        </div>
    </div>
</div>

<!-- Object Details -->
<div class="al-space-y-4 al-pl-6 al-min-w-0">
    <h4 class="al-text-lg al-font-medium al-text-gray-700 al-mb-4">Object</h4>
    <div class="al-space-y-2">
        <div class="al-flex al-items-center">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Object ID</span>
            <div class="copyable-group">
                <span class="al-text-sm al-text-gray-700 al-truncate al-max-w-32 al-cursor-pointer hover:al-bg-gray-100 al-px-2 al-py-1 al-rounded al-inline-flex al-items-center al-gap-2" 
                      onclick="copyToClipboard('{{ $log->object_id }}', 'Object ID')" 
                      title="Click to copy Object ID">
                    <span>{{ $log->object_id }}</span>
                    <i class="fas fa-copy copy-btn al-text-xs al-text-gray-400"></i>
                </span>
            </div>
        </div>
        <div class="al-flex">
            <span class="al-text-sm al-text-gray-500 al-w-24 al-flex-shrink-0">Object Type</span>
            <span class="al-text-sm al-text-gray-700 al-truncate al-max-w-32 al-px-2 al-py-1">{{ $log->object_type }}</span>
        </div>
        @if ($log->object_detail)
            <div class="al-w-full">
                <div class="al-flex al-items-center al-justify-between al-mb-2">
                    <span class="al-text-sm al-text-gray-500">Object Detail</span>
                    <button
                        onclick="copyToClipboard({{ json_encode(json_encode(json_decode($log->object_detail), JSON_PRETTY_PRINT)) }}, 'Object Detail')"
                        class="al-text-xs al-text-gray-500 hover:al-text-gray-700 al-bg-gray-100 hover:al-bg-gray-200 al-px-2 al-py-1 al-rounded al-flex al-items-center al-gap-1 al-transition-colors"
                        title="Copy Object Detail to clipboard">
                        <i class="fas fa-copy"></i>
                        <span>Copy</span>
                    </button>
                </div>
                <div class="al-bg-gray-50 al-border al-border-gray-200 al-rounded al-p-3 al-w-full al-text-xs al-text-gray-700" style="height: 192px; min-height: 100px; max-height: 400px; overflow-y: auto; overflow-x: auto; white-space: pre-wrap; font-family: monospace; resize: vertical;">{{ json_encode(json_decode($log->object_detail), JSON_PRETTY_PRINT) }}</div>
            </div>
        @endif
    </div>
</div>
