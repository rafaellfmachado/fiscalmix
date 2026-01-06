use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
// Auth
Route::prefix('auth')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/me', [AuthController::class, 'me']);
});

// Companies
Route::apiResource('companies', CompanyController::class);

// Certificates
Route::prefix('companies/{company}')->group(function () {
Route::post('/certificates/a1', [CertificateController::class, 'uploadA1']);
Route::get('/certificates', [CertificateController::class, 'index']);
Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy']);

// Sync
Route::post('/sync', [SyncController::class, 'trigger']);
Route::get('/sync-runs', [SyncController::class, 'index']);
});

// Sync runs
Route::get('/sync-runs/{syncRun}', [SyncController::class, 'show']);

// Documents
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents/stats', [DocumentController::class, 'stats']);
Route::get('/documents/{document}', [DocumentController::class, 'show']);
Route::get('/documents/{document}/xml', [DocumentController::class, 'downloadXml']);
Route::get('/documents/{document}/pdf', [DocumentController::class, 'downloadPdf']);

// Exports (coming soon)
// Route::post('/exports', [ExportController::class, 'create']);
// Route::get('/exports/{export}', [ExportController::class, 'show']);
// Route::get('/exports/{export}/download', [ExportController::class, 'download']);
});