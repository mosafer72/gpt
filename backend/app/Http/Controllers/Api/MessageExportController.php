<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageExportController extends Controller
{
    public function export(Request $request, Message $message)
    {
        $format = $request->get('format', 'txt');

        switch ($format) {
            case 'json':
                return response()->json($message);

            case 'md':
                return response($message->content)
                    ->header('Content-Type', 'text/markdown')
                    ->header('Content-Disposition', 'attachment; filename="message.md"');

            case 'pdf':
                return response($message->content)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="message.pdf"');

            default:
                return response($message->content)
                    ->header('Content-Type', 'text/plain')
                    ->header('Content-Disposition', 'attachment; filename="message.txt"');
        }
    }
}
