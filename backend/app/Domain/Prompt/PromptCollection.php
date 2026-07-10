<?php

namespace App\Domain\Prompt;


class PromptCollection
{

    /**
     * @var array<int,array{
     * role:string,
     * content:string
     * }>
     */
    protected array $messages = [];



    public function add(
        string $role,
        string $content
    ): self {

        $this->messages[] = [
            'role'=>$role,
            'content'=>$content
        ];


        return $this;
    }



    public function all(): array
    {
        return $this->messages;
    }



    public function toArray(): array
    {
        return $this->messages;
    }

}
