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
    ): JsonResponse {


        $user = auth()->user();


        if (!$user) {

            return response()->json([
                'error'=>'Unauthenticated'
            ],401);

        }




        $request->validate([

            'message'=>'required|string',

            'chat_id'=>'nullable|exists:chats,id'

        ]);





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

                'user_id'=>$user->id,

                'title'=>mb_substr(
                    $request->message,
                    0,
                    50
                )

            ]);






        /**
         * 2
         *
         * ذخیره پیام User
         */

        $this->messageStore->store(

            new MessageDTO(

                id:uniqid(),

                conversation:
                    (string)$chat->id,

                role:'user',

                content:
                    $request->message,

                token_count:0,

                metadata:[

                    'user_id'=>$user->id

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
                    (string)$user->id,


                conversationId:
                    (string)$chat->id,


                currentMessage:
                    $request->message,


                model:
                    $user->currentPlan()->model
                    ??
                    'gpt-4o-mini'

            );






        /**
         * 4
         *
         * اجرای Chat Engine
         */

        $context =
            $this->chatPipeline
                ->handle(
                    $context
                );







        /**
         * 5
         *
         * خروجی مثل قبل
         */

        return response()->json([


            'chat'=>
                $chat->fresh()
                    ->load('messages'),



            'message'=>[

                'role'=>'assistant',

                'content'=>
                    $context->assistantResponse

            ],



            'context'=>
                $context->toArray()


        ]);

    }







    /**
     * نمایش یک چت
     */
    public function show(
        Chat $chat
    ): JsonResponse {


        if(
            $chat->user_id !== auth()->id()
        ){

            return response()->json([
                'error'=>'Forbidden'
            ],403);

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


        if(
            $chat->user_id !== auth()->id()
        ){

            return response()->json([
                'error'=>'Forbidden'
            ],403);

        }


        $chat->delete();


        return response()->json([

            'message'=>'deleted'

        ]);

    }


}
