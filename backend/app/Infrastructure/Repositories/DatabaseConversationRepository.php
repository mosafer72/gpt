<?php

namespace App\Infrastructure\Repositories;


use App\Domain\Contracts\ConversationRepository;
use App\Domain\Conversation\DTOs\ConversationDTO;
use Illuminate\Support\Facades\DB;



class DatabaseConversationRepository implements ConversationRepository
{


    public function find(
        string $id
    ): ?ConversationDTO {


        $chat =
            DB::table('chats')
                ->where('id', $id)
                ->first();


        if (!$chat) {

            return null;

        }



        return new ConversationDTO(

            id: (string) $chat->id,

            userId: (string) $chat->user_id,

            title: $chat->title ?? 'New Conversation',

            model: $chat->model ?? 'gpt-4o',

            status: $chat->status ?? 'active',

            createdAt: (string) $chat->created_at,

            updatedAt: (string) $chat->updated_at,

        );

    }


    public function create(
        string $userId,
        ?string $title = null,
        ?string $model = null
    ): ConversationDTO {


        $id = DB::table('chats')
            ->insertGetId([

                'user_id' => $userId,

                'created_at' => now(),

                'updated_at' => now(),

            ]);


        return $this->find(
            (string) $id
        );

    }


    public function update(
        string $id,
        array $data
    ): void {

        DB::table('chats')
            ->where('id', $id)
            ->update([
                ...$data,
                'updated_at' => now(),
            ]);

    }



    public function delete(
        string $id
    ): void {

        DB::table('chats')
            ->where('id', $id)
            ->delete();

    }


}
