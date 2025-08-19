<?php

namespace CbyteDigital\Nen7513AuditLogging\Http\Controllers;

use CbyteDigital\Nen7513AuditLogging\Services\AuditLogQuery;
use CbyteDigital\Nen7513AuditLogging\Services\MaskingService;
use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class AuditLogOverviewController extends Controller
{
    private MaskingService $maskingService;

    public function __construct(MaskingService $maskingService)
    {
        $this->maskingService = $maskingService;
    }

    /**
     * Display a listing of audit logs
     */
    public function index(Request $request)
    {
        $query = new AuditLogQuery();
        $this->applyFilters($query, $request);

        $perPage = $request->get('per_page', 25);
        $perPage = min($perPage, 100);

        $logs = $query->paginate($perPage);

        // Apply masking to logs for display
        $maskedLogs = $this->maskingService->maskAuditLogsForDisplay($logs);

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $tableRowsHtml = '';
            foreach ($maskedLogs->items() as $log) {
                $tableRowsHtml .= view('vendor.audit-log.partials.table-row', compact('log'))->render();
            }
            
            return response()->json([
                'html' => $tableRowsHtml,
                'current_page' => $maskedLogs->currentPage(),
                'last_page' => $maskedLogs->lastPage(),
                'per_page' => $maskedLogs->perPage(),
                'total' => $maskedLogs->total(),
                'from' => $maskedLogs->firstItem(),
                'to' => $maskedLogs->lastItem(),
            ]);
        }

        return view('vendor.audit-log.index', compact('maskedLogs', 'request'));
    }

    private function applyFilters(AuditLogQuery $query, Request $request): void
    {
        $this->applySearchFilters($query, $request);
        $this->applyDateFilters($query, $request);
        $this->applyEntityFilters($query, $request);
        $this->applyActionFilters($query, $request);
        $this->applySorting($query, $request);
    }

    private function applySearchFilters(AuditLogQuery $query, Request $request): void
    {
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->getQuery()->where(function ($q) use ($searchTerm) {
                $q->where('actor_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('object_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('object_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('actor_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('ip_address', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('source_id', 'LIKE', "%{$searchTerm}%");
            });
        }

        $searchFields = [
            'actor_search' => ['actor_name', 'actor_id'],
            'object_search' => ['object_name', 'object_id'],
            'object_type_search' => ['object_type'],
            'source_search' => ['source_id'],
            'source_type_search' => ['source_type'],
            'user_agent_search' => ['user_agent']
        ];

        foreach ($searchFields as $requestField => $dbFields) {
            if ($request->filled($requestField)) {
                $searchTerm = $request->get($requestField);
                if (count($dbFields) === 1) {
                    $query->getQuery()->where($dbFields[0], 'LIKE', "%{$searchTerm}%");
                } else {
                    $query->getQuery()->where(function ($q) use ($dbFields, $searchTerm) {
                        foreach ($dbFields as $field) {
                            $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                        }
                    });
                }
            }
        }
    }

    private function applyDateFilters(AuditLogQuery $query, Request $request): void
    {
        if ($request->filled('date_from')) {
            try {
                $from = \Carbon\Carbon::parse($request->get('date_from'))->startOfDay();
                $query->since($from);
            } catch (\Exception $e) {
            }
        }

        if ($request->filled('date_to')) {
            try {
                $to = \Carbon\Carbon::parse($request->get('date_to'))->endOfDay();
                $query->until($to);
            } catch (\Exception $e) {
            }
        }
    }

    private function applyEntityFilters(AuditLogQuery $query, Request $request): void
    {
        if ($request->filled('object_type')) {
            $query->forObjectType($request->get('object_type'));
        }

        if ($request->filled('object_id')) {
            $objectType = $request->get('object_type', 'unknown');
            $query->forObject($objectType, $request->get('object_id'));
        }

        if ($request->filled('actor_type')) {
            $query->forActorType($request->get('actor_type'));
        }

        if ($request->filled('actor_id')) {
            $query->getQuery()->where('actor_id', $request->get('actor_id'));
        }

        if ($request->filled('actor_role')) {
            $query->forActorRole($request->get('actor_role'));
        }
    }

    private function applyActionFilters(AuditLogQuery $query, Request $request): void
    {
        if ($request->filled('actions')) {
            $actions = $request->get('actions');
            if (is_array($actions) && !empty($actions)) {
                $mappedActions = array_map('strtoupper', $actions);
                if (!empty($mappedActions)) {
                    $query->withActions($mappedActions);
                }
            }
        }

        if ($request->filled('outcomes')) {
            $outcomes = $request->get('outcomes');
            if (is_array($outcomes) && !empty($outcomes)) {
                $mappedOutcomes = array_map('strtolower', $outcomes);
                $query->getQuery()->whereIn('outcome', $mappedOutcomes);
            }
        }
    }

    private function applySorting(AuditLogQuery $query, Request $request): void
    {
        $sortField = $request->get('sort', 'datetime');
        $direction = $request->get('direction', 'desc');
        
        $query->getQuery()->orderByRaw("CAST(event_time AS DATETIME) {$direction}");
        
        if ($sortField && $sortField !== 'datetime') {
            $sortFieldMap = [
                'actor' => 'actor_name',
                'action' => 'action',
                'object' => 'object_name',
                'outcome' => 'outcome'
            ];
            
            if (isset($sortFieldMap[$sortField])) {
                $dbField = $sortFieldMap[$sortField];
                $query->getQuery()->orderBy($dbField, $direction);
            }
        }
    }

    /**
     * Export audit logs as CSV with current filters
     */
    public function export(Request $request)
    {
        $query = new AuditLogQuery();
        $this->applyFilters($query, $request);
        
        $logs = $query->getQuery()->get();

        $maskedLogs = $this->maskingService->maskAuditLogsForDisplay($logs);
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "audit_logs_export_{$timestamp}.csv";
        $csvContent = $this->generateCsvContent($maskedLogs, $request);
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Generate CSV content with headers and data
     */
    private function generateCsvContent($logs, Request $request)
    {
        $csv = [];
        
        $csv[] = ['# Audit Log Export'];
        $csv[] = ['# Generated on:', now()->format('Y-m-d H:i:s')];
        $csv[] = ['# Total records:', $logs->count()];
        $csv[] = [''];
        $activeFilters = $this->getActiveFiltersInfo($request);
        if (!empty($activeFilters)) {
            $csv[] = ['# Active Filters:'];
            foreach ($activeFilters as $filter) {
                $csv[] = ["# - {$filter}"];
            }
            $csv[] = [''];
        }

        $csv[] = [
            'ID',
            'Datum/Tijd',
            'Actie',
            'Uitkomst',
            'Actor Type',
            'Actor ID', 
            'Actor Naam',
            'Actor Rol',
            'Object Type',
            'Object ID',
            'Object Naam',
            'Source Type',
            'Source ID',
            'IP Adres',
            'User Agent',
            'Autorisatie Check',
            'Autorisatie Protocol',
            'Behandelrelatie Check',
            'Toestemming Check',
            'Object Detail',
            'Integrity Hash'
        ];

        foreach ($logs as $log) {
            $csv[] = [
                $log->id,
                $log->event_time ? \Carbon\Carbon::parse($log->event_time)->format('Y-m-d H:i:s') : '',
                strtoupper($log->action),
                ucfirst(strtolower($log->outcome ?? 'unknown')),
                $log->actor_type ?? '',
                $log->actor_id ?? '',
                $log->actor_name ?? '',
                $log->actor_role ?? '',
                $log->object_type ?? '',
                $log->object_id ?? '',
                $log->object_name ?? '',
                $log->source_type ?? '',
                $log->source_id ?? '',
                $log->ip_address ?? '',
                $log->user_agent ?? '',
                $log->authorization_check ? 'Ja' : 'Nee',
                $log->authorization_protocol ?? '',
                $log->treatment_relation_check ? 'Ja' : 'Nee',
                $log->consent_check ? 'Ja' : 'Nee',
                $log->object_detail ?? '',
                $log->integrity_hash ?? ''
            ];
        }

        return $this->arrayToCsv($csv);
    }
    
    /**
     * Get active filters information for CSV header
     */
    private function getActiveFiltersInfo(Request $request)
    {
        $filters = [];
        
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from'))->format('Y-m-d') : 'Begin';
            $to = $request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to'))->format('Y-m-d') : 'Eind';
            $filters[] = "Periode: {$from} tot {$to}";
        }
        
        if ($request->filled('search')) {
            $filters[] = "Zoekterm: " . $request->get('search');
        }
        
        if ($request->filled('actions')) {
            $actions = collect($request->get('actions'))
                ->unique()
                ->map(function($action) {
                    return strtoupper($action);
                })
                ->join(', ');
            $filters[] = "Acties: {$actions}";
        }
        
        if ($request->filled('outcomes')) {
            $outcomes = collect($request->get('outcomes'))->map(function($outcome) {
                return ucfirst(strtolower($outcome));
            })->join(', ');
            $filters[] = "Uitkomsten: {$outcomes}";
        }
        
        if ($request->filled('actor_search')) {
            $filters[] = "Actor zoekterm: " . $request->get('actor_search');
        }
        
        if ($request->filled('object_search')) {
            $filters[] = "Object ID zoekterm: " . $request->get('object_search');
        }
        
        if ($request->filled('object_type_search')) {
            $filters[] = "Object Type zoekterm: " . $request->get('object_type_search');
        }
        
        if ($request->filled('source_search')) {
            $filters[] = "Source ID zoekterm: " . $request->get('source_search');
        }
        
        if ($request->filled('source_type_search')) {
            $filters[] = "Source Type zoekterm: " . $request->get('source_type_search');
        }
        
        if ($request->filled('user_agent_search')) {
            $filters[] = "User Agent zoekterm: " . $request->get('user_agent_search');
        }
        
        if ($request->filled('sort') && $request->filled('direction')) {
            $sortLabels = [
                'datetime' => 'Datum/Tijd',
                'action' => 'Actie',
                'actor' => 'Actor',
                'object' => 'Object',
                'outcome' => 'Uitkomst'
            ];
            $sortField = $sortLabels[$request->get('sort')] ?? $request->get('sort');
            $direction = $request->get('direction') === 'asc' ? 'Oplopend' : 'Aflopend';
            $filters[] = "Sortering: {$sortField} ({$direction})";
        }
        
        return $filters;
    }
    
    /**
     * Convert array to CSV string
     */
    private function arrayToCsv($array)
    {
        $output = '';
        
        foreach ($array as $row) {
            $escapedRow = [];
            foreach ($row as $field) {
                if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                $escapedRow[] = $field;
            }
            $output .= implode(',', $escapedRow) . "\n";
        }
        
        return $output;
    }
}
