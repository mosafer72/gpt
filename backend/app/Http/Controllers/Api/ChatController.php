<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\StoreAssistantMessageJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Usage;
use App\Models\Chat;

use App\Application\Context\ChatContext;
use App\Application\Pipelines\ChatPipeline;

use App\Domain\Contracts\MessageStore;
use App\Domain\Message\DTOs\MessageDTO;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Jobs\StoreMessageJob;


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

        $userId = auth()->id();


        $cacheKey =
            "user:{$userId}:chats:last20";


        $chats = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($userId) {


                return Chat::where(
                    'user_id',
                    $userId
                )
                    ->with([
                        'messages' => function ($query) {

                            $query
                                ->latest()
                                ->limit(20);

                        }
                    ])
                    ->orderBy(
                        'updated_at',
                        'desc'
                    )
                    ->limit(20)
                    ->get();

            }
        );


        return response()->json(
            $chats
        );

    }


    public function messages(
        Chat $chat,
        Request $request
    ): JsonResponse {


        if (
            $chat->user_id !== auth()->id()
        ) {

            return response()->json([
                'error' => 'Forbidden'
            ], 403);

        }



        $perPage = (int) $request->input(
            'per_page',
            10
        );


        $page = (int) $request->input(
            'page',
            1
        );



        $cacheKey =
            "chat:{$chat->id}:messages:page:{$page}:limit:{$perPage}";



        $messages = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),

            function () use ($chat, $perPage, $page) {


                $paginator =
                    $chat->messages()
                        ->orderBy(
                            'created_at',
                            'desc'
                        )
                        ->paginate(
                            $perPage,
                            ['*'],
                            'page',
                            $page
                        );


                return [

                    'data' =>
                        array_reverse(
                            $paginator->items()
                        ),


                    'current_page' =>
                        $paginator->currentPage(),


                    'last_page' =>
                        $paginator->lastPage(),


                    'per_page' =>
                        $paginator->perPage(),


                    'total' =>
                        $paginator->total(),

                ];


            }
        );



        return response()->json(
            $messages
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

        Cache::forget(
            "user:{$user->id}:chats:last20"
        );




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

        StoreMessageJob::dispatch([

            'id' => uniqid(),

            'conversation' =>
                (string) $chat->id,

            'user_id' =>
                (string) $user->id,

            'role' =>
                'user',

            'content' =>
                $content,

            'token_count' =>
                0,

            'metadata' => [

                'attachments' =>
                    $imagePath
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

            'created_at' =>
                now()->toDateTimeString(),

        ]);
        // $this->messageStore->store(

        //     new MessageDTO(

        //         id: uniqid(),


        //         conversation:
        //         (string) $chat->id,


        //         user_id:
        //         (string) $user->id,


        //         role: 'user',


        //         content:
        //         $content,


        //         token_count: 0,


        //         metadata: [

        //             'attachments' => $imagePath
        //                 ?
        //                 [
        //                     [
        //                         'type' => 'image',
        //                         'url' => $imageUrl
        //                     ]
        //                 ]
        //                 :
        //                 []

        //         ],


        //         created_at:
        //         now()->toDateTimeString()

        //     )

        // );

        // ذخیره Usage








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

                // کاربر استریم را قطع کرده
                if (connection_aborted()) {

                    break;

                }

                if (isset($response->usage)) {

                    $context->tokenUsage = [

                        'prompt_tokens' => $response->usage->promptTokens ?? 0,

                        'completion_tokens' => $response->usage->completionTokens ?? 0,

                        'total_tokens' => $response->usage->totalTokens ?? 0,

                    ];

                }
                $text = '';

                if (
                    isset($response->choices[0]->delta->content)
                ) {

                    $text =
                        $response->choices[0]
                            ->delta
                            ->content;

                }

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
            if (!connection_aborted()) {

                StoreMessageJob::dispatch([

                    'id' => uniqid(),

                    'conversation' =>
                        (string) $context->conversationId,

                    'user_id' =>
                        (string) $context->userId,

                    'role' =>
                        'assistant',

                    'content' =>
                        $full,

                    'token_count' =>
                        $context->tokenUsage['total_tokens'] ?? 0,

                    'metadata' => [

                        'model' =>
                            $context->model,

                        'token_usage' =>
                            $context->tokenUsage,

                    ],

                    'created_at' =>
                        now()->toDateTimeString(),

                ]);

            }
            // ذخیره پاسخ کامل
            // if (!connection_aborted()) {

            //     $this->messageStore->store(

            //         new MessageDTO(

            //             id: uniqid(),

            //             conversation:
            //             (string) $context->conversationId,


            //             user_id:
            //             (string) $context->userId,


            //             role:
            //             'assistant',


            //             content:
            //             $full,


            //             token_count:
            //             $context->tokenUsage['total_tokens'] ?? 0,


            //             metadata: [

            //                 'model' => $context->model,

            //                 'token_usage' => [

            //                     'prompt_tokens' =>
            //                         $context->tokenUsage['prompt_tokens'] ?? 0,


            //                     'completion_tokens' =>
            //                         $context->tokenUsage['completion_tokens'] ?? 0,


            //                     'total_tokens' =>
            //                         $context->tokenUsage['total_tokens'] ?? 0,

            //                 ],

            //             ],


            //             created_at:
            //             now()->toDateTimeString()

            //         )

            //     );

            //     \Log::info('TOKEN DEBUG', [
            //         'context_usage' => $context->tokenUsage,
            //     ]);


            //     Usage::create([

            //         'user_id' =>
            //             (int) $context->userId,


            //         'conversation_id' =>
            //             (int) $context->conversationId,


            //         'type' =>
            //             'chat',


            //         'model' =>
            //             $context->model,


            //         'prompt_tokens' =>
            //             $context->tokenUsage['prompt_tokens'] ?? 0,


            //         'completion_tokens' =>
            //             $context->tokenUsage['completion_tokens'] ?? 0,


            //         'total_tokens' =>
            //             $context->tokenUsage['total_tokens'] ?? 0,

            //     ]);


            //     echo "data: [DONE]\n\n";
            // }


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



        $cacheKey =
            "chat:{$chat->id}:messages:page:1:limit:50";



        $messages = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),

            function () use ($chat) {


                return $chat
                    ->messages()
                    ->orderBy(
                        'created_at',
                        'asc'
                    )
                    ->limit(50)
                    ->get();


            }

        );




        return response()->json([

            'id' =>
                $chat->id,


            'title' =>
                $chat->title,


            'summary' =>
                $chat->summary,


            'created_at' =>
                $chat->created_at,


            'updated_at' =>
                $chat->updated_at,


            'messages' =>
                $messages


        ]);

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
