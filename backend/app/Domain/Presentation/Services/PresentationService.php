<?php

namespace App\Domain\Presentation\Services;

use App\Jobs\GeneratePresentationJob;
use App\Models\Document;
use App\Domain\Presentation\DTO\CreatePresentationDTO;
use Illuminate\Support\Facades\Storage;

class PresentationService
{
    public function create(
        int $workspaceId,
        int $userId,
        CreatePresentationDTO $dto
    ): Document {

        $document = Document::create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'name' => 'presentation_' . time() . '.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size' => 0,
            'status' => 'processing',
            'path' => '',
            'error' => null,
            'metadata' => [
                'message' => $dto->message,
                'retry_count' => 0,
                'generation_state' => 'queued',
                'next_retry_at' => null
            ]
        ]);
        \Log::info("lksdjgskdghskdjghk",$document->toArray());
        GeneratePresentationJob::dispatch($document);

        return $document;
    }

    public function list(int $workspaceId): \Illuminate\Database\Eloquent\Collection
    {
        return Document::query()
            ->where('workspace_id', $workspaceId)
            ->where('mime_type', 'application/vnd.openxmlformats-officedocument.presentationml.presentation')
            ->latest()
            ->get();
    }

    public function find(int $id, int $workspaceId): Document
    {
        return Document::query()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);
    }

    public function download(Document $document)
    {
        if (!$document->path || !Storage::exists($document->path)) {
            throw new \RuntimeException('Presentation file not found');
        }

        return Storage::path($document->path);
    }

    public function delete(Document $document): void
    {
        if ($document->path) {
            Storage::delete($document->path);
        }

        $document->delete();
    }
}
