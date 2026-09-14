<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadDocument;
use App\Security\CrmPermission;
use App\Support\CrmDatabaseGuard;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadDocumentController extends Controller
{
    public function download(Lead $lead, LeadDocument|int|string $document): BinaryFileResponse
    {
        $doc = $document instanceof LeadDocument ? $document : LeadDocument::query()->findOrFail((int) $document);
        $this->authorizeDocumentAccess($lead, $doc);

        $disk = Storage::disk($doc->disk ?: 'local');
        $this->assertSafePath($disk, $doc->path);

        $absolutePath = $disk->path($doc->path);
        $downloadName = $doc->original_name ?: basename($doc->path);

        return response()->download($absolutePath, $downloadName);
    }

    /**
     * Preview a Lead document (inline) for PDFs and images.
     */
    public function preview(Lead $lead, LeadDocument|int|string $document): BinaryFileResponse
    {
        $doc = $document instanceof LeadDocument ? $document : LeadDocument::query()->findOrFail((int) $document);
        $this->authorizeDocumentAccess($lead, $doc);

        $disk = Storage::disk($doc->disk ?: 'local');
        $this->assertSafePath($disk, $doc->path);

        $absolutePath = $disk->path($doc->path);
        $mime = $doc->mime_type ?: ($disk->mimeType($doc->path) ?: 'application/octet-stream');
        $filename = $doc->original_name ?: basename($doc->path);

        // For safe inline preview of PDF and images
        $isInlinePreviewable = $doc->isPdf() || $doc->isImage();
        $disposition = $isInlinePreviewable ? 'inline' : 'attachment';

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => "{$disposition}; filename=\"" . addslashes($filename) . "\"",
        ]);
    }
    /**
     * Backward-compatible route for downloading the latest or legacy quotation file of a lead.
     */
    public function downloadQuotation(Lead $lead): BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);
        if ($lead->trashed()) {
            abort_unless($user->hasPermission(CrmPermission::LEADS_TRASH_VIEW), 403, 'غير مصرح لك بالوصول لمستندات العملاء في سلة المهملات.');
        } else {
            abort_unless($user->hasPermission(CrmPermission::LEADS_VIEW), 403, 'غير مصرح لك بعرض بيانات العملاء.');
        }
        abort_unless($lead->isAccessibleTo($user), 403, 'غير مصرح لك بالوصول لهذا العميل.');
        abort_unless($user->hasPermission(CrmPermission::QUOTATIONS_VIEW), 403, 'غير مصرح لك بعرض عروض الأسعار.');
        // Find latest quotation document or legacy path
        $latestQuotation = $lead->quotationDocuments()->first();

        if ($latestQuotation instanceof LeadDocument) {
            return $this->download($lead, $latestQuotation);
        }

        $legacyPath = trim((string) $lead->quotation_file_path);
        abort_unless($legacyPath !== '', 404, 'لا يوجد ملف عرض سعر مرتبط بهذا العميل.');

        $disk = Storage::disk('local');
        $this->assertSafePath($disk, $legacyPath);

        $absolutePath = $disk->path($legacyPath);
        return response()->download($absolutePath, basename($legacyPath));
    }

    /**
     * Ensure the user has permission to access the document and lead.
     */
    private function authorizeDocumentAccess(Lead $lead, LeadDocument $document): void
    {
        CrmDatabaseGuard::ensureConnected();

        // 1. Verify document belongs to this lead
        abort_unless((int) $document->lead_id === (int) $lead->id, 404, 'المستند غير مرتبط بهذا العميل.');

        $user = auth()->user();
        abort_unless($user !== null, 401);

        // 2. Require leads.view permission (or leads.trash.view if lead is trashed)
        if ($lead->trashed()) {
            abort_unless($user->hasPermission(CrmPermission::LEADS_TRASH_VIEW), 403, 'غير مصرح لك بالوصول لمستندات العملاء في سلة المهملات.');
        } else {
            abort_unless($user->hasPermission(CrmPermission::LEADS_VIEW), 403, 'غير مصرح لك بعرض بيانات العملاء.');
        }

        // 3. Check lead accessibility (branch scope, assigned user, creator)
        abort_unless($lead->isAccessibleTo($user), 403, 'غير مصرح لك بالوصول لبيانات هذا العميل.');
        // 4. For quotation category, require quotations.view permission
        if ($document->isQuotation()) {
            abort_unless($user->hasPermission(CrmPermission::QUOTATIONS_VIEW), 403, 'غير مصرح لك بعرض عروض الأسعار.');
        }
    }

    /**
     * Prevent directory traversal and verify file existence on disk.
     */
    private function assertSafePath(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $relativePath): void
    {
        $relativePath = trim($relativePath);

        $isSafe = $relativePath !== ''
            && ! str_contains($relativePath, '..')
            && ! str_starts_with($relativePath, '/')
            && ! str_starts_with($relativePath, '\\');

        abort_unless($isSafe, 404, 'مسار الملف غير صالح.');

        abort_unless($disk->exists($relativePath), 404, 'الملف غير موجود في الخادم.');
    }
}
