<?php

namespace App\Contracts;

use App\Models\Certificate;
use App\Models\Company;

interface FiscalConnector
{
    /**
     * Connect to the fiscal service using certificate
     */
    public function connect(Certificate $certificate): bool;

    /**
     * Check connector health
     */
    public function healthcheck(): array;

    /**
     * Sync documents from fiscal service
     * 
     * @param Company $company
     * @param int $nsuStart Starting NSU (0 for first sync)
     * @param array $docTypes Document types to sync ['NFE', 'CTE', 'MDFE']
     * @return SyncResult
     */
    public function sync(Company $company, int $nsuStart, array $docTypes): SyncResult;

    /**
     * Fetch a specific document by key
     */
    public function fetchDocument(string $accessKey): ?array;

    /**
     * Fetch events for a document
     */
    public function fetchEvents(string $accessKey): array;

    /**
     * Render PDF from XML
     */
    public function renderPDF(string $xml, string $docType): string;
}

/**
 * Sync result DTO
 */
class SyncResult
{
    public function __construct(
        public int $ultNsu,
        public array $documents,
        public array $events,
        public int $docsFound,
        public int $docsSaved,
        public int $eventsSaved,
        public ?array $errors = null
    ) {
    }
}
