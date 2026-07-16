<?php

namespace App\Domain\Message\DTOs;

class MessageDTO
{
    public function __construct(

        /**
         * شناسه پیام
         */
        public readonly string $id,


        /**
         * شناسه مکالمه
         */
        public readonly string $conversation,


        /**
         * شناسه کاربر
         */
        public readonly ?string $user_id,


        /**
         * نقش پیام
         * user | assistant | system
         */
        public readonly string $role,


        /**
         * محتوای پیام
         */
        public readonly string $content,


        /**
         * تعداد توکن مصرفی
         */
        public readonly int $token_count,


        /**
         * اطلاعات اضافی پیام
         */
        public readonly ?array $metadata,


        /**
         * زمان ایجاد
         */
        public readonly string $created_at,

    ) {
    }


    /**
     * ساخت DTO از آرایه
     */
    public static function fromArray(array $data): self
    {

        // اگر داده به صورت [[...]] است
        if (
            isset($data[0]) &&
            is_array($data[0]) &&
            isset($data[0]['id'])
        ) {

            $data = $data[0];

        }


        // اگر داده داخل result باشد
        if (
            isset($data['result']) &&
            is_array($data['result'])
        ) {

            $data = $data['result'];


            if (
                isset($data[0]) &&
                is_array($data[0])
            ) {

                $data = $data[0];

            }

        }


        // اگر داده داخل data باشد
        if (
            isset($data['data']) &&
            is_array($data['data'])
        ) {

            $data = $data['data'];


            if (
                isset($data[0]) &&
                is_array($data[0])
            ) {

                $data = $data[0];

            }

        }


        return new self(

            id:
            (string) ($data['id'] ?? ''),


            conversation:
            (string) (
                $data['conversation']
                ??
                $data['conversation_id']
                ??
                ''
            ),


            user_id:
            isset($data['user_id'])
            ? (string) $data['user_id']
            : null,


            role:
            (string) ($data['role'] ?? ''),


            content:
            (string) ($data['content'] ?? ''),


            token_count:
            (int) ($data['token_count'] ?? 0),


            metadata:
            isset($data['metadata'])
            ? (
                is_string($data['metadata'])
                ? json_decode(
                    $data['metadata'],
                    true
                )
                :
                $data['metadata']
            )
            :
            null,


            created_at:
            (string) ($data['created_at'] ?? ''),

        );

    }



    /**
     * ساخت مجموعه‌ای از DTO ها
     */
    public static function collectionFromArray(array $data): array
    {

        $items = [];


        $flatten = function ($array) use (&$flatten, &$items) {

            foreach ($array as $value) {


                if (is_array($value)) {


                    if (
                        isset($value['id'])
                    ) {

                        $items[] = $value;

                    } else {

                        $flatten($value);

                    }

                }

            }

        };


        $flatten($data);


        return array_map(

            fn($item) =>
            self::fromArray($item),

            $items

        );

    }



    /**
     * تبدیل DTO به آرایه
     */
    public function toArray(): array
    {

        return [

            'id' =>
                $this->id,


            'conversation' =>
                $this->conversation,


            'user_id' =>
                $this->user_id,


            'role' =>
                $this->role,


            'content' =>
                $this->content,


            'token_count' =>
                $this->token_count,


            'metadata' =>
                $this->metadata,


            'created_at' =>
                $this->created_at,

        ];

    }


}
