<?php

namespace App\Livewire\Configuration;

use App\Services\AuditService;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Documents & backups')]
class Documents extends Component
{
    public array $form = [];

    public ?string $backupOutput = null;

    public function mount(SettingsService $settings): void
    {
        Gate::authorize('settings');
        $this->form = $settings->group('documents');
    }

    public function save(SettingsService $settings, AuditService $audit): void
    {
        Gate::authorize('settings');

        $validated = $this->validate([
            'form.max_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'form.retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ])['form'];

        $old = $settings->group('documents');
        $settings->setMany(['documents.max_size_mb' => (int) $validated['max_size_mb'], 'documents.retention_days' => (int) $validated['retention_days']]);
        $audit->log('settings.documents.updated', null, $old, $validated);

        Flux::toast(variant: 'success', text: __('Document settings saved.'));
    }

    public function runBackup(AuditService $audit): void
    {
        Gate::authorize('settings');

        try {
            $code = Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);
            $this->backupOutput = trim(Artisan::output());
            $audit->log($code === 0 ? 'backup.run' : 'backup.failed');
            Flux::toast(variant: $code === 0 ? 'success' : 'danger', text: $code === 0 ? __('Database backup created.') : __('Backup failed. See output below.'));
        } catch (Throwable $e) {
            $this->backupOutput = $e->getMessage();
            $audit->log('backup.failed', null, [], ['error' => $e->getMessage()]);
            Flux::toast(variant: 'danger', text: __('Backup failed: :msg', ['msg' => $e->getMessage()]));
        }
    }

    public function render(): View
    {
        $disk = config('backup.backup.destination.disks.0', 'local');
        $folder = config('backup.backup.name', config('app.name'));
        $backups = [];
        try {
            $files = Storage::disk($disk)->files($folder);
            rsort($files);
            foreach (array_slice($files, 0, 10) as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => Storage::disk($disk)->size($file),
                    'at' => CarbonImmutable::createFromTimestamp(Storage::disk($disk)->lastModified($file)),
                ];
            }
        } catch (Throwable) {
            // disk not configured yet
        }

        return view('livewire.configuration.documents', [
            'backups' => $backups,
            'backupDisk' => $disk,
            'backupPath' => $disk === 'local' ? storage_path('app/private/'.$folder) : $disk,
        ]);
    }
}
