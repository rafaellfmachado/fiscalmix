<?php

namespace App\Services\Connectors;

use App\Contracts\FiscalConnector;
use App\Contracts\SyncResult;
use App\Models\Certificate;
use App\Models\Company;

/**
 * Mock SEFAZ DF-e Connector for development
 * In production, this would connect to real SEFAZ WebService
 */
class SefazDFeConnector implements FiscalConnector
{
    private ?Certificate $certificate = null;

    public function connect(Certificate $certificate): bool
    {
        // Mock connection - in production, validate certificate with SEFAZ
        if (!$certificate->isActive()) {
            throw new \Exception('Certificado inválido ou expirado');
        }

        $this->certificate = $certificate;
        return true;
    }

    public function healthcheck(): array
    {
        return [
            'status' => 'ok',
            'connector' => 'SefazDFeConnector',
            'environment' => config('app.env'),
            'mock' => true,
        ];
    }

    public function sync(Company $company, int $nsuStart, array $docTypes): SyncResult
    {
        if (!$this->certificate) {
            throw new \Exception('Conector não conectado. Execute connect() primeiro.');
        }

        // Mock sync - generate fake documents
        $documents = $this->generateMockDocuments($company, $nsuStart, $docTypes);
        $events = [];

        $ultNsu = $nsuStart + count($documents);

        return new SyncResult(
            ultNsu: $ultNsu,
            documents: $documents,
            events: $events,
            docsFound: count($documents),
            docsSaved: count($documents),
            eventsSaved: 0,
            errors: null
        );
    }

    public function fetchDocument(string $accessKey): ?array
    {
        // Mock fetch - in production, call SEFAZ WebService
        return [
            'access_key' => $accessKey,
            'xml' => $this->generateMockXml($accessKey),
            'status' => 'authorized',
        ];
    }

    public function fetchEvents(string $accessKey): array
    {
        // Mock events - in production, fetch from SEFAZ
        return [];
    }

    public function renderPDF(string $xml, string $docType): string
    {
        // Mock PDF generation - in production, use nfephp-org/sped-da
        return base64_encode("Mock PDF for {$docType}");
    }

    /**
     * Generate mock documents for testing
     */
    private function generateMockDocuments(Company $company, int $nsuStart, array $docTypes): array
    {
        $documents = [];
        $count = rand(5, 15); // Random number of documents

        for ($i = 0; $i < $count; $i++) {
            $nsu = $nsuStart + $i + 1;
            $docType = $docTypes[array_rand($docTypes)];
            $number = rand(1000, 9999);

            $accessKey = $this->generateMockAccessKey($company->uf, $company->cnpj, $number);

            $documents[] = [
                'nsu' => $nsu,
                'doc_type' => $docType,
                'access_key' => $accessKey,
                'number' => $number,
                'series' => 1,
                'issue_date' => now()->subDays(rand(0, 30)),
                'issuer_cnpj' => rand(0, 1) ? $company->cnpj : $this->generateRandomCnpj(),
                'issuer_name' => 'Fornecedor ' . chr(65 + rand(0, 25)) . chr(65 + rand(0, 25)) . chr(65 + rand(0, 25)),
                'recipient_cnpj' => rand(0, 1) ? $company->cnpj : $this->generateRandomCnpj(),
                'recipient_name' => $company->razao_social,
                'total_value' => rand(100, 10000) / 100,
                'status' => 'authorized',
                'xml' => $this->generateMockXml($accessKey),
            ];
        }

        return $documents;
    }

    private function generateMockAccessKey(string $uf, string $cnpj, int $number): string
    {
        $ufCode = $this->getUfCode($uf);
        $year = date('y');
        $month = date('m');

        // Format: UF + AAMM + CNPJ + Modelo + Serie + Numero + Codigo + DV
        $key = $ufCode . $year . $month . $cnpj . '55' . '001' . str_pad($number, 9, '0', STR_PAD_LEFT);
        $key .= rand(10000000, 99999999); // Random code
        $key .= $this->calculateDV($key); // Check digit

        return $key;
    }

    private function getUfCode(string $uf): string
    {
        $codes = [
            'SP' => '35',
            'RJ' => '33',
            'MG' => '31',
            'RS' => '43',
            'PR' => '41',
            'SC' => '42',
            'BA' => '29',
            'PE' => '26',
        ];
        return $codes[$uf] ?? '35';
    }

    private function calculateDV(string $key): int
    {
        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($key) - 1; $i >= 0; $i--) {
            $sum += $key[$i] * $multiplier;
            $multiplier = $multiplier == 9 ? 2 : $multiplier + 1;
        }

        $remainder = $sum % 11;
        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private function generateRandomCnpj(): string
    {
        return str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT) . '0001' . rand(10, 99);
    }

    private function generateMockXml(string $accessKey): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc versao="4.00">
    <NFe>
        <infNFe Id="NFe{$accessKey}">
            <ide>
                <cUF>35</cUF>
                <cNF>12345678</cNF>
                <natOp>VENDA</natOp>
                <mod>55</mod>
                <serie>1</serie>
                <nNF>1234</nNF>
                <dhEmi>2024-01-15T10:30:00-03:00</dhEmi>
            </ide>
        </infNFe>
    </NFe>
</nfeProc>
XML;
    }
}
