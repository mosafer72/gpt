<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\Chat;

use App\Application\Context\ChatContext;
use App\Application\Pipelines\ChatPipeline;

use App\Domain\Contracts\MessageStore;
use App\Domain\Message\DTOs\MessageDTO;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ChatController extends Controller
{


    public function __construct(

        protected ChatPipeline $chatPipeline,

        protected MessageStore $messageStore

    ) {

    }




    /**
     * لیست چت ها
     */
    public function index(): JsonResponse
    {

        $chats =
            Chat::where(
                'user_id',
                auth()->id()
            )
                ->with('messages')
                ->orderBy(
                    'updated_at',
                    'desc'
                )
                ->get();


        return response()->json(
            $chats
        );

    }





    /**
     * ارسال پیام و دریافت پاسخ
     */
    public function store(
        Request $request
    ) {


        $user = auth()->user();


        if (!$user) {

            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);

        }

        $request->validate([

            'message' => 'nullable|string',

            'chat_id' => 'nullable|exists:chats,id',

            'image' => 'nullable|image|max:5120'

        ]);



        $imagePath = null;


        if ($request->hasFile('image')) {

            $imagePath = $request
                ->file('image')
                ->store(
                    'chat-images',
                    'public'
                );

        }




        $imageUrl = $imagePath
            ? 'https://accountsara.ir/images/'
            . basename($imagePath)
            : null;



        /**
         * 1
         *
         * ایجاد یا دریافت Chat
         */

        $chat =
            $request->chat_id

            ?

            Chat::where(
                'user_id',
                $user->id
            )
                ->findOrFail(
                    $request->chat_id
                )

            :

            Chat::create([

                'user_id' => $user->id,

                'title' => mb_substr(
                    $request->input('message', 'Image'),
                    0,
                    50
                )

            ]);






        /**
         * 2
         *
         * ذخیره پیام User
         */

        $content = $request->input('message') ?? '';

        $plannerMessage = $content;

        if ($plannerMessage === '' && $imageUrl) {
            $plannerMessage = '[Image attached]';
        }

        $this->messageStore->store(

            new MessageDTO(

                id: uniqid(),

                conversation:
                (string) $chat->id,

                role: 'user',

                content:
                $content,

                token_count: 0,

                metadata: [

                    'user_id' => $user->id,

                    'attachments' => $imagePath
                        ?
                        [
                            [
                                'type' => 'image',
                                'url' => $imageUrl
                            ]
                        ]
                        :
                        []

                ],

                created_at:
                now()->toDateTimeString()

            )

        );






        /**
         * 3
         *
         * ساخت Context
         */


        $context =
            new ChatContext(

                userId:
                (string) $user->id,


                conversationId:
                (string) $chat->id,


                currentMessage:
                $plannerMessage,


                model:
                $user->currentPlan()->model
                ??
                'gpt-4o-mini'

            );

        $context->setMetadata([

            'attachments' => $imagePath
                ?
                [
                    [
                        'type' => 'image',
                        'url' => $imageUrl
                    ]
                ]
                :
                []

        ]);
















        return response()->stream(function () use ($context) {

            $stream = $this->chatPipeline->stream($context);

            $full = '';

            foreach ($stream as $response) {

                $text = $response->choices[0]->delta->content ?? '';

                if ($text === '') {

                    continue;

                }

                $full .= $text;

                echo "data: " . json_encode(

                    ['content' => $text],

                    JSON_UNESCAPED_UNICODE

                ) . "\n\n";

                if (ob_get_level() > 0) {

                    ob_flush();

                }

                flush();

            }

            // ذخیره پاسخ کامل

            $this->messageStore->store(

                new MessageDTO(

                    id: uniqid(),

                    conversation: $context->conversationId,

                    role: 'assistant',

                    content: $full,

                    token_count: 0,

                    metadata: [],

                    created_at: now()->toDateTimeString()

                )

            );

            echo "data: [DONE]\n\n";

        }, 200, [

            'Content-Type' => 'text/event-stream',

            'Cache-Control' => 'no-cache',

            'Connection' => 'keep-alive',

            'X-Accel-Buffering' => 'no',

        ]);

    }







    /**
     * نمایش یک چت
     */
    public function show(
        Chat $chat
    ): JsonResponse {


        if (
            $chat->user_id !== auth()->id()
        ) {

            return response()->json([
                'error' => 'Forbidden'
            ], 403);

        }



        return response()->json(

            $chat->load('messages')

        );

    }





    /**
     * حذف چت
     */
    public function destroy(
        Chat $chat
    ): JsonResponse {


        if (
            $chat->user_id !== auth()->id()
        ) {

            return response()->json([
                'error' => 'Forbidden'
            ], 403);

        }


        $chat->delete();


        return response()->json([

            'message' => 'deleted'

        ]);

    }


}
