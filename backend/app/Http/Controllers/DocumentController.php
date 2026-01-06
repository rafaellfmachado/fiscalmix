<?php

namespace App\Http\Controllers;

use App\Models\FiscalDocument;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * List fiscal documents with filters and pagination
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|uuid|exists:companies,id',
            'doc_type' => 'nullable|in:NFE,CTE,MDFE,NFSE',
            'direction' => 'nullable|in:inbound,outbound',
            'status' => 'nullable|in:authorized,canceled,denied,pending',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = FiscalDocument::query()
            ->where('account_id', $request->user()->account_id);

        // Filters
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->doc_type) {
            $query->where('doc_type', $request->doc_type);
        }

        if ($request->direction) {
            $query->where('direction', $request->direction);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date) {
            $query->where('issue_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->where('issue_date', '<=', $request->end_date);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('access_key', 'like', "%{$request->search}%")
                    ->orWhere('number', 'like', "%{$request->search}%")
                    ->orWhere('issuer_name', 'like', "%{$request->search}%")
                    ->orWhere('recipient_name', 'like', "%{$request->search}%");
            });
        }

        $perPage = $request->per_page ?? 20;
        $documents = $query->with('company')
            ->orderBy('issue_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Get a specific document
     */
    public function show(Request $request, string $id)
    {
        $document = FiscalDocument::where('account_id', $request->user()->account_id)
            ->with(['company', 'events'])
            ->findOrFail($id);

        return response()->json([
            'id' => $document->id,
            'company' => [
                'id' => $document->company->id,
                'razao_social' => $document->company->razao_social,
                'cnpj' => $document->company->cnpj,
            ],
            'doc_type' => $document->doc_type,
            'access_key' => $document->access_key,
            'number' => $document->number,
            'series' => $document->series,
            'issue_date' => $document->issue_date,
            'direction' => $document->direction,
            'issuer_cnpj' => $document->issuer_cnpj,
            'issuer_name' => $document->issuer_name,
            'recipient_cnpj' => $document->recipient_cnpj,
            'recipient_name' => $document->recipient_name,
            'total_value' => $document->total_value,
            'status' => $document->status,
            'metadata' => $document->metadata,
            'events' => $document->events,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ]);
    }

    /**
     * Download XML
     */
    public function downloadXml(Request $request, string $id)
    {
        $document = FiscalDocument::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        if (!$document->xml_content) {
            return response()->json(['error' => 'XML não disponível'], 404);
        }

        $filename = "{$document->doc_type}_{$document->access_key}.xml";

        return response($document->xml_content, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download PDF (DANFE)
     */
    public function downloadPdf(Request $request, string $id)
    {
        $document = FiscalDocument::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        // TODO: Implementar geração de PDF com nfephp-org/sped-da
        // Por enquanto, retornar mock

        return response()->json([
            'message' => 'Geração de PDF em desenvolvimento',
            'document_id' => $document->id,
            'access_key' => $document->access_key,
        ], 501);
    }

    /**
     * Get document statistics
     */
    public function stats(Request $request)
    {
        $query = FiscalDocument::where('account_id', $request->user()->account_id);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $stats = [
            'total' => $query->count(),
            'by_type' => $query->selectRaw('doc_type, count(*) as count')
                ->groupBy('doc_type')
                ->pluck('count', 'doc_type'),
            'by_status' => $query->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_direction' => $query->selectRaw('direction, count(*) as count')
                ->groupBy('direction')
                ->pluck('count', 'direction'),
            'total_value' => $query->sum('total_value'),
        ];

        return response()->json($stats);
    }
}
