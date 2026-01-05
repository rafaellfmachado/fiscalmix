<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    /**
     * Upload A1 certificate (.pfx file)
     */
    public function uploadA1(Request $request, string $companyId)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->findOrFail($companyId);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pfx,p12|max:10240', // 10MB max
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $password = $request->password;

            // Read certificate content
            $pfxContent = file_get_contents($file->getRealPath());

            // Validate certificate with OpenSSL
            $certs = [];
            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                return response()->json([
                    'errors' => ['file' => ['Certificado inválido ou senha incorreta']],
                ], 422);
            }

            // Extract certificate info
            $certInfo = openssl_x509_parse($certs['cert']);

            // Check if certificate is expired
            $validFrom = \Carbon\Carbon::createFromTimestamp($certInfo['validFrom_time_t']);
            $validTo = \Carbon\Carbon::createFromTimestamp($certInfo['validTo_time_t']);

            if ($validTo->isPast()) {
                return response()->json([
                    'errors' => ['file' => ['Certificado expirado']],
                ], 422);
            }

            // Extract subject info
            $subject = $certInfo['subject']['CN'] ?? 'Unknown';
            $issuer = $certInfo['issuer']['CN'] ?? 'Unknown';

            // Generate fingerprint
            openssl_x509_export($certs['cert'], $certPem);
            $fingerprint = openssl_x509_fingerprint($certPem, 'sha256');

            // Encrypt certificate (simplified for MVP - in production use KMS)
            $encryptedBlob = base64_encode($pfxContent);
            $encryptedDek = base64_encode(random_bytes(32)); // Mock DEK

            // Deactivate old certificates
            Certificate::where('company_id', $company->id)
                ->where('type', 'A1')
                ->update(['status' => 'expired']);

            // Create new certificate
            $certificate = Certificate::create([
                'company_id' => $company->id,
                'type' => 'A1',
                'encrypted_blob' => $encryptedBlob,
                'encrypted_dek' => $encryptedDek,
                'fingerprint' => $fingerprint,
                'metadata' => [
                    'subject' => $subject,
                    'issuer' => $issuer,
                ],
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'status' => 'active',
            ]);

            return response()->json([
                'id' => $certificate->id,
                'type' => $certificate->type,
                'fingerprint' => $certificate->fingerprint,
                'subject' => $subject,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'days_until_expiry' => $certificate->daysUntilExpiry(),
                'status' => $certificate->status,
                'message' => 'Certificado A1 configurado com sucesso!',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['file' => ['Erro ao processar certificado: ' . $e->getMessage()]],
            ], 500);
        }
    }

    /**
     * List certificates for a company
     */
    public function index(Request $request, string $companyId)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->findOrFail($companyId);

        $certificates = Certificate::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $certificates->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'type' => $cert->type,
                    'fingerprint' => $cert->fingerprint,
                    'metadata' => $cert->metadata,
                    'valid_from' => $cert->valid_from,
                    'valid_to' => $cert->valid_to,
                    'days_until_expiry' => $cert->daysUntilExpiry(),
                    'status' => $cert->status,
                    'is_active' => $cert->isActive(),
                    'is_expiring_soon' => $cert->isExpiringSoon(),
                    'created_at' => $cert->created_at,
                ];
            }),
        ]);
    }

    /**
     * Delete a certificate
     */
    public function destroy(Request $request, string $companyId, string $certificateId)
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->findOrFail($companyId);

        $certificate = Certificate::where('company_id', $company->id)
            ->findOrFail($certificateId);

        $certificate->delete();

        return response()->json([
            'message' => 'Certificado removido com sucesso',
        ]);
    }
}
