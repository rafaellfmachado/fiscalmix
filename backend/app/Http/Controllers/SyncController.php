<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SyncRun;
use App\Services\Connectors\SefazDFeConnector;
use App\Models\FiscalDocument;
use App\Models\FiscalEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Trigger sync for a company
     */
    public function trigger(Request $request, string $companyId)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->with('activeCertificate')
            ->findOrFail($companyId);

        if (!$company->activeCertificate()) {
            return response()->json([
                'error' => 'Empresa não possui certificado ativo',
            ], 422);
        }

        // Get last NSU
        $lastSync = SyncRun::where('company_id', $company->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();

        $nsuStart = $lastSync ? $lastSync->ult_nsu : 0;

        // Create sync run
        $syncRun = SyncRun::create([
            'company_id' => $company->id,
            'connector_type' => 'SefazDFe',
            'nsu_start' => $nsuStart,
            'ult_nsu' => $nsuStart,
            'status' => 'running',
            'metadata' => [
                'triggered_by' => $request->user()->id,
                'doc_types' => ['NFE', 'CTE', 'MDFE'],
            ],
        ]);

        try {
            // Connect to SEFAZ
            $connector = new SefazDFeConnector();
            $connector->connect($company->activeCertificate());

            // Sync documents
            $result = $connector->sync($company, $nsuStart, ['NFE', 'CTE', 'MDFE']);

            // Save documents
            $docsSaved = 0;
            foreach ($result->documents as $docData) {
                // Check if document already exists (deduplication)
                $existing = FiscalDocument::where('access_key', $docData['access_key'])->first();

                if ($existing) {
                    continue;
                }

                // Determine direction
                $direction = $docData['issuer_cnpj'] === $company->cnpj ? 'outbound' : 'inbound';

                FiscalDocument::create([
                    'account_id' => $company->account_id,
                    'company_id' => $company->id,
                    'doc_type' => $docData['doc_type'],
                    'access_key' => $docData['access_key'],
                    'number' => $docData['number'],
                    'series' => $docData['series'],
                    'issue_date' => $docData['issue_date'],
                    'direction' => $direction,
                    'issuer_cnpj' => $docData['issuer_cnpj'],
                    'issuer_name' => $docData['issuer_name'],
                    'recipient_cnpj' => $docData['recipient_cnpj'],
                    'recipient_name' => $docData['recipient_name'],
                    'total_value' => $docData['total_value'],
                    'status' => $docData['status'],
                    'xml_content' => $docData['xml'],
                    'metadata' => [
                        'nsu' => $docData['nsu'],
                    ],
                ]);

                $docsSaved++;
            }

            // Update sync run
            $syncRun->update([
                'ult_nsu' => $result->ultNsu,
                'docs_found' => $result->docsFound,
                'docs_saved' => $docsSaved,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return response()->json([
                'id' => $syncRun->id,
                'status' => 'completed',
                'nsu_start' => $nsuStart,
                'ult_nsu' => $result->ultNsu,
                'docs_found' => $result->docsFound,
                'docs_saved' => $docsSaved,
                'message' => "Sincronização concluída! {$docsSaved} documentos salvos.",
            ]);

        } catch (\Exception $e) {
            $syncRun->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return response()->json([
                'error' => 'Erro na sincronização: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync run details
     */
    public function show(Request $request, string $syncRunId)
    {
        $syncRun = SyncRun::whereHas('company', function ($query) use ($request) {
            $query->where('account_id', $request->user()->account_id);
        })->with('company')->findOrFail($syncRunId);

        return response()->json([
            'id' => $syncRun->id,
            'company' => [
                'id' => $syncRun->company->id,
                'razao_social' => $syncRun->company->razao_social,
            ],
            'connector_type' => $syncRun->connector_type,
            'nsu_start' => $syncRun->nsu_start,
            'ult_nsu' => $syncRun->ult_nsu,
            'docs_found' => $syncRun->docs_found,
            'docs_saved' => $syncRun->docs_saved,
            'status' => $syncRun->status,
            'error_message' => $syncRun->error_message,
            'metadata' => $syncRun->metadata,
            'created_at' => $syncRun->created_at,
            'completed_at' => $syncRun->completed_at,
        ]);
    }

    /**
     * List sync runs for a company
     */
    public function index(Request $request, string $companyId)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->findOrFail($companyId);

        $syncRuns = SyncRun::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $syncRuns->items(),
            'meta' => [
                'current_page' => $syncRuns->currentPage(),
                'last_page' => $syncRuns->lastPage(),
                'total' => $syncRuns->total(),
            ],
        ]);
    }
}
