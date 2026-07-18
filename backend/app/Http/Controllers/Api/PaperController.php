<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Application\Capabilities\CapabilityResolver;
use App\Application\Research\PdfTextExtractor;

class PaperController extends Controller
{
    public function upload(
        Request $request,
        CapabilityResolver $capability,
        PdfTextExtractor $extractor
    ) {
        $user = $request->user();

        // فقط پلن Student
        if (! $capability->enabled($user, 'pdf_reader')) {
            return response()->json([
                'message' => 'پلن شما امکان خواندن PDF را ندارد'
            ], 403);
        }

        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480',
        ]);

        // ذخیره فایل
        $path = $request->file('pdf')->store('papers');

        // استخراج متن
        $text = $extractor->extract( storage_path('app/' . $path) );

        return response()->json([
            'path' => $path,
            'text' => mb_substr($text, 0, 5000),
        ]);
    }
}
