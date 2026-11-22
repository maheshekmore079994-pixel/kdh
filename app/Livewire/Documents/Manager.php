<?php

namespace App\Livewire\Documents;

use App\Exceptions\DomainException;
use App\Models\Document;
use App\Services\DocumentService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

/**
 * Attachment list + upload for any model with a documents() morph relation.
 * The parent page is responsible for module authorisation; this component re-checks via DocumentService.
 */
class Manager extends Component
{
    use WithFileUploads;

    public Model $model;

    public $upload = null;

    public function updatedUpload(DocumentService $documents): void
    {
        $this->validate(['upload' => ['required', 'file', 'max:'.((int) setting('documents.max_size_mb', 10) * 1024)]]);

        try {
            $documents->attach($this->model, $this->upload);
            $this->upload = null;
            Flux::toast(variant: 'success', text: __('File attached.'));
        } catch (DomainException $e) {
            $this->upload = null;
            $this->addError('upload', $e->getMessage());
        }
    }

    public function delete(int $id, DocumentService $documents): void
    {
        $doc = Document::query()->where('documentable_type', $this->model->getMorphClass())->where('documentable_id', $this->model->getKey())->findOrFail($id);
        $documents->remove($doc);
        Flux::toast(variant: 'success', text: __('File removed.'));
    }

    public function render(): View
    {
        return view('livewire.documents.manager', [
            'documents' => Document::query()->where('documentable_type', $this->model->getMorphClass())->where('documentable_id', $this->model->getKey())->with('uploader')->latest()->get(),
            'maxMb' => (int) setting('documents.max_size_mb', 10),
        ]);
    }
}
