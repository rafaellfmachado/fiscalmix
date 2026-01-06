<?php

namespace App\Http\Controllers;

use App\Models\ExportJob;
use App\Models\FiscalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportController extends Controller
{
    /**
     * Create a new export job
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|uuid|exists:companies,id',
            'doc_type' => 'nullable|in:NFE,CTE,MDFE,NFSE',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Build query to count documents
        $query = FiscalDocument::where('account_id', $request->user()->account_id);

        $filters = [];

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
            $filters['company_id'] = $request->company_id;
        }

        if ($request->doc_type) {
            $query->where('doc_type', $request->doc_type);
            $filters['doc_type'] = $request->doc_type;
        }

        if ($request->start_date) {
            $query->where('issue_date', '>=', $request->start_date);
            $filters['start_date'] = $request->start_date;
        }

        if ($request->end_date) {
            $query->where('issue_date', '<=', $request->end_date);
            $filters['end_date'] = $request->end_date;
        }

        $totalDocuments = $query->count();

        if ($totalDocuments === 0) {
            return response()->json([
                'error' => 'Nenhum documento encontrado com os filtros especificados',
            ], 422);
        }

        // Create export job
        $exportJob = ExportJob::create([
            'account_id' => $request->user()->account_id,
            'user_id' => $request->user()->id,
            'company_id' => $request->company_id,
            'filters' => $filters,
            'total_documents' => $totalDocuments,
            'processed_documents' => 0,
            'status' => 'pending',
        ]);

        // Process synchronously for MVP (in production, use Queue)
        $this->processExport($exportJob);

        return response()->json([
            'id' => $exportJob->id,
            'status' => $exportJob->status,
            'total_documents' => $exportJob->total_documents,
            'message' => 'Exportação iniciada',
        ], 201);
    }

    /**
     * Get export job status
     */
    public function show(Request $request, string $id)
    {
        $exportJob = ExportJob::where('account_id', $request->user()->account_id)
            ->with('company')
            ->findOrFail($id);

        return response()->json([
            'id' => $exportJob->id,
            'company' => $exportJob->company ? [
                'id' => $exportJob->company->id,
                'razao_social' => $exportJob->company->razao_social,
            ] : null,
            'filters' => $exportJob->filters,
            'total_documents' => $exportJob->total_documents,
            'processed_documents' => $exportJob->processed_documents,
            'progress' => $exportJob->getProgress(),
            'file_size' => $exportJob->getFileSizeFormatted(),
            'status' => $exportJob->status,
            'error_message' => $exportJob->error_message,
            'download_url' => $exportJob->getDownloadUrl(),
            'is_downloadable' => $exportJob->isDownloadable(),
            'created_at' => $exportJob->created_at,
            'started_at' => $exportJob->started_at,
            'completed_at' => $exportJob->completed_at,
        ]);
    }

    /**
     * Download export file
     */
    public function download(Request $request, string $id)
    {
        $exportJob = ExportJob::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        if (!$exportJob->isDownloadable()) {
            return response()->json([
                'error' => 'Exportação não disponível para download',
            ], 404);
        }

        $filePath = storage_path('app/' . $exportJob->file_path);
        $filename = 'fiscalmix_export_' . $exportJob->id . '.zip';

        return response()->download($filePath, $filename);
    }

    /**
     * List export jobs
     */
    public function index(Request $request)
    {
        $exports = ExportJob::where('account_id', $request->user()->account_id)
            ->with('company')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $exports->items(),
            'meta' => [
                'current_page' => $exports->currentPage(),
                'last_page' => $exports->lastPage(),
                'total' => $exports->total(),
            ],
        ]);
    }

    /**
     * Process export (synchronous for MVP)
     */
    private function processExport(ExportJob $exportJob)
    {
        try {
            $exportJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Build query
            $query = FiscalDocument::where('account_id', $exportJob->account_id);

            foreach ($exportJob->filters as $key => $value) {
                if ($key === 'start_date') {
                    $query->where('issue_date', '>=', $value);
                } elseif ($key === 'end_date') {
                    $query->where('issue_date', '<=', $value);
                } else {
                    $query->where($key, $value);
                }
            }

            $documents = $query->get();

            // Create ZIP
            $zipPath = 'exports/' . $exportJob->id . '.zip';
            $fullPath = storage_path('app/' . $zipPath);

            // Ensure directory exists
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Não foi possível criar arquivo ZIP');
            }

            $processed = 0;
            foreach ($documents as $document) {
                if ($document->xml_content) {
                    $filename = "{$document->doc_type}_{$document->access_key}.xml";
                    $zip->addFromString($filename, $document->xml_content);
                    $processed++;
                }

                $exportJob->update(['processed_documents' => $processed]);
            }

            $zip->close();

            $fileSize = filesize($fullPath);

            $exportJob->update([
                'file_path' => $zipPath,
                'file_size' => $fileSize,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
