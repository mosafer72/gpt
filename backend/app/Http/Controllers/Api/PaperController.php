<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Application\Capabilities\CapabilityResolver;
use App\Application\Research\PdfTextExtractor;
use App\Models\Paper;
use Spatie\PdfToText\Pdf;

class PaperController extends Controller
{
    public function upload(
        Request $request,
        CapabilityResolver $capability,
        PdfTextExtractor $extractor
    ) {
        $user = $request->user();

        // فقط پلن Student
        if (!$capability->enabled($user, 'pdf_reader')) {
            return response()->json([
                'message' => 'پلن شما امکان خواندن PDF را ندارد'
            ], 403);
        }
        $file = $request->file('file');
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
            'conversation_id' => 'required|integer|min:0',
        ]);
        $path = "storage/" . $request
            ->file('file')
            ->store(
                'papers',
                'public'
            );

        $text = (new Pdf())->setPdf($path)->text();

        $paper = Paper::create([
            'user_id' => auth()->id(),
            'title' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extracted_text' => $text,
            'summary' => null,
            'metadata' => [
                'pages' => $pages ?? null,
            ],
        ]);

        $chat_id = $request->input('conversation_id');

        if ($chat_id) {
            $conversation = Chat::find($chat_id);

            if ($conversation && $conversation->user_id == auth()->id()) {
                $chat = Chat::find($request->conversation_id);
                $chat->papers()->syncWithoutDetaching([
                    $paper->id
                ]);
            }
        }
        // استخراج متن
        // $text = $extractor->extract($path);

        return response()->json([
            'success' => true,
            'paper' => [
                'id' => $paper->id,
                'title' => $paper->title,
                'url' => $paper->url,
                'size' => $paper->size,
            ]
        ]);
    }
}
