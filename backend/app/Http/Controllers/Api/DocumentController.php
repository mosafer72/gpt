<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\Documents\DocumentOverviewService;
use App\Application\Services\Documents\DocumentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Application\Services\Documents\DocumentViewerService;
use App\Jobs\ExtractDocumentTextJob; // اضافه کردن ایوب پردازش

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $service,
        protected DocumentOverviewService $overviewService
    ) {
    }

    /**
     * List Documents
     */
    public function index(
        Request $request,
        $workspaceId
    ) {
        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        return response()->json([
            'success' => true,
            'documents' => $this->service->list(
                workspace: $workspace
            )
        ]);
    }

    /**
     * Upload Document & Dispatch Text Extraction
     */
    public function store(
        Request $request,
        $workspaceId
    ) {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,txt',
                'max:20480'
            ]
        ]);

        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        // ۱. ذخیره فایل و ثبت رکورد Document اولیه
        $document = $this->service->upload(
            file: $request->file('file'),
            workspace: $workspace,
            user: $request->user()
        );

        // ۲. ارسال جاب پردازش و استخراج متن به صف پس‌زمینه

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully. Processing started.',
            'document' => $document
        ], 201);
    }

    /**
     * Show Document
     */
    public function show(
        Request $request,
        $workspaceId,
        $documentId
    ) {
        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        $document = $workspace
            ->documents()
            ->findOrFail($documentId);

        return response()->json([
            'success' => true,
            'document' => $this->service->show($document)
        ]);
    }

    /**
     * Rename Document
     */
    public function update(
        Request $request,
        $workspaceId,
        $documentId
    ) {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ]
        ]);

        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        $document = $workspace
            ->documents()
            ->findOrFail($documentId);

        $document = $this->service->rename(
            $document,
            $request->name
        );

        return response()->json([
            'success' => true,
            'document' => $document
        ]);
    }

    /**
     * Delete Document
     */
    public function destroy(
        Request $request,
        $workspaceId,
        $documentId
    ) {
        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        $document = $workspace
            ->documents()
            ->findOrFail($documentId);

        $this->service->delete(
            $document
        );

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Document Intelligence Overview
     */
    public function overview(
        Request $request,
        $workspaceId,
        $documentId
    ) {
        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        $document = $workspace
            ->documents()
            ->findOrFail($documentId);

        return response()->json([
            'success' => true,
            'data' => $this->overviewService->overview(
                $document
            )
        ]);
    }

    /**
     * Document Sections (Fixed & Secured)
     */
    public function sections(
        Request $request,
        $workspaceId,
        $documentId,
        DocumentViewerService $viewer
    ) {
        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);

        $document = $workspace
            ->documents()
            ->findOrFail($documentId);

        $sections = $viewer->sections(
            documentId: (string) $document->id,
            workspaceId: (string) $workspace->id
        );

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }
}
