<?php

namespace App\Domain\Presentation\DTO;


class CreatePresentationDTO
{

    public function __construct(
        public string $message,
    ) {
    }



    public static function from(array $data): self
    {

        return new self(

            message: $data['message'] ?? ''

        );

    }



    public function toArray(): array
    {

        return [

            'message' => $this->message

        ];

    }

}
