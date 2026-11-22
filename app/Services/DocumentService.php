<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\ServiceJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public const ALLOWED_MIMES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];

    public function __construct(private readonly AuditService $audit) {}

    /** The module permission that guards documents attached to a given model. */
    public static function permissionFor(Model $model): string
    {
        return match (true) {
            $model instanceof Customer => 'customers',
            $model instanceof ServiceJob => 'jobs',
            $model instanceof Expense => 'expenses',
            $model instanceof Purchase => 'purchases',
            default => 'settings',
        };
    }

    public function attach(Model $model, UploadedFile $file): Document
    {
        Gate::authorize(self::permissionFor($model));

        $mime = $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension());

        if (! isset(self::ALLOWED_MIMES[$mime]) || self::ALLOWED_MIMES[$mime] !== ($ext === 'jpeg' ? 'jpg' : $ext)) {
            throw new DomainException(__('Only PDF, JPG, PNG, WEBP, DOCX and XLSX files are allowed.'));
        }
        if (substr_count($file->getClientOriginalName(), '.') > 1 && preg_match('/\.(php|phtml|exe|bat|cmd|js|sh|html?)\./i', $file->getClientOriginalName())) {
            throw new DomainException(__('This file name is not allowed.'));
        }

        $maxBytes = (int) setting('documents.max_size_mb', 10) * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new DomainException(__('File is larger than the :mb MB limit.', ['mb' => setting('documents.max_size_mb', 10)]));
        }

        $folder = 'documents/'.Str::snake(class_basename($model)).'/'.$model->getKey();
        $name = now()->format('Ymd-His').'-'.Str::random(8).'.'.self::ALLOWED_MIMES[$mime];
        $path = $file->storeAs($folder, $name, 'private');

        $retention = (int) setting('documents.retention_days', 0);

        $doc = Document::query()->create([
            'documentable_type' => $model->getMorphClass(),
            'documentable_id' => $model->getKey(),
            'disk' => 'private',
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[^\w .\-()]/u', '_', $file->getClientOriginalName()), 200, ''),
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'expires_at' => $retention > 0 ? now()->addDays($retention) : null,
        ]);

        $this->audit->log('document.uploaded', $doc, [], ['name' => $doc->original_name, 'size' => $doc->size, 'for' => class_basename($model).'#'.$model->getKey()]);

        return $doc;
    }

    public function remove(Document $document): void
    {
        $owner = $document->documentable;
        Gate::authorize($owner ? self::permissionFor($owner) : 'settings');

        Storage::disk($document->disk)->delete($document->path);
        $this->audit->log('document.deleted', $document, ['name' => $document->original_name], []);
        $document->delete();
    }

    /** Delete files past their expiry; returns the number removed. */
    public function pruneExpired(): int
    {
        $count = 0;
        Document::query()->whereNotNull('expires_at')->where('expires_at', '<', now())->chunkById(100, function ($docs) use (&$count) {
            foreach ($docs as $doc) {
                Storage::disk($doc->disk)->delete($doc->path);
                $this->audit->log('document.expired', $doc, ['name' => $doc->original_name], []);
                $doc->delete();
                $count++;
            }
        });

        return $count;
    }
}
