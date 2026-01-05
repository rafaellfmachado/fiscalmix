<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * List all companies for authenticated user's account
     */
    public function index(Request $request)
    {
        $companies = Company::where('account_id', $request->user()->account_id)
            ->with([
                'certificates' => function ($query) {
                    $query->where('status', 'active')
                        ->where('valid_to', '>', now())
                        ->latest();
                }
            ])
            ->withCount('fiscalDocuments')
            ->get();

        return response()->json([
            'data' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'cnpj' => $company->getFormattedCnpj(),
                    'razao_social' => $company->razao_social,
                    'nome_fantasia' => $company->nome_fantasia,
                    'uf' => $company->uf,
                    'status' => $company->status,
                    'certificate' => $company->certificates->first() ? [
                        'type' => $company->certificates->first()->type,
                        'valid_to' => $company->certificates->first()->valid_to,
                        'status' => $company->certificates->first()->status,
                        'days_until_expiry' => $company->certificates->first()->daysUntilExpiry(),
                    ] : null,
                    'documents_count' => $company->fiscal_documents_count,
                ];
            }),
        ]);
    }

    /**
     * Create a new company
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cnpj' => 'required|string|size:14|unique:companies,cnpj,NULL,id,account_id,' . $request->user()->account_id,
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'ie' => 'nullable|string|max:50',
            'uf' => 'required|string|size:2',
            'municipio' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate CNPJ format
        if (!$this->validateCnpj($request->cnpj)) {
            return response()->json([
                'errors' => ['cnpj' => ['CNPJ inválido']],
            ], 422);
        }

        $company = Company::create([
            'account_id' => $request->user()->account_id,
            'cnpj' => $request->cnpj,
            'razao_social' => $request->razao_social,
            'nome_fantasia' => $request->nome_fantasia,
            'ie' => $request->ie,
            'uf' => $request->uf,
            'municipio' => $request->municipio,
            'tags' => $request->tags ?? [],
            'status' => 'active',
        ]);

        return response()->json([
            'id' => $company->id,
            'cnpj' => $company->getFormattedCnpj(),
            'razao_social' => $company->razao_social,
            'status' => $company->status,
            'created_at' => $company->created_at,
        ], 201);
    }

    /**
     * Get a specific company
     */
    public function show(Request $request, string $id)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->with([
                'certificates',
                'connectorConfigs',
                'syncRuns' => function ($query) {
                    $query->latest()->limit(10);
                }
            ])
            ->findOrFail($id);

        return response()->json([
            'id' => $company->id,
            'cnpj' => $company->getFormattedCnpj(),
            'razao_social' => $company->razao_social,
            'nome_fantasia' => $company->nome_fantasia,
            'ie' => $company->ie,
            'uf' => $company->uf,
            'municipio' => $company->municipio,
            'tags' => $company->tags,
            'status' => $company->status,
            'certificates' => $company->certificates,
            'connectors' => $company->connectorConfigs,
            'recent_syncs' => $company->syncRuns,
            'created_at' => $company->created_at,
        ]);
    }

    /**
     * Update a company
     */
    public function update(Request $request, string $id)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'razao_social' => 'sometimes|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'ie' => 'nullable|string|max:50',
            'municipio' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company->update($request->only([
            'razao_social',
            'nome_fantasia',
            'ie',
            'municipio',
            'tags',
            'status',
        ]));

        return response()->json([
            'id' => $company->id,
            'message' => 'Empresa atualizada com sucesso',
        ]);
    }

    /**
     * Delete a company
     */
    public function destroy(Request $request, string $id)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        $company->delete();

        return response()->json([
            'message' => 'Empresa excluída com sucesso',
        ]);
    }

    /**
     * Validate CNPJ
     */
    private function validateCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }

        // Check for known invalid CNPJs
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Validate check digits
        for ($t = 12; $t < 14; $t++) {
            $d = 0;
            $c = 0;
            for ($m = $t - 7; $m >= 2; $m--, $c++) {
                $d += $cnpj[$c] * $m;
            }
            for ($m = 9; $m >= 2 && $c < $t; $m--, $c++) {
                $d += $cnpj[$c] * $m;
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cnpj[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}
