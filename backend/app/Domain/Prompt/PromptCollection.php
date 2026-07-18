<?php

namespace App\Domain\Prompt;


class PromptCollection
{

    /**
     * @var array<int,array{
     * role:string,
     * content:string|array
     * }>
     */
    protected array $messages = [];



    public function add(
        string $role,
        mixed $content
    ): self {


        /**
         * جلوگیری از چند system message
         */
        if ($role === 'system') {

            foreach ($this->messages as &$message) {

                if ($message['role'] === 'system') {


                    if (
                        is_string($message['content'])
                        &&
                        is_string($content)
                    ) {

                        $message['content'] .= "\n\n" . $content;

                    }


                    return $this;

                }

            }

        }



        $this->messages[] = [
            'role' => $role,
            'content' => $content
        ];


        return $this;

    }



    /**
     * اضافه کردن مستقیم به System Prompt
     */
    public function appendToSystem(
        string $content
    ): self {


        foreach ($this->messages as &$message) {


            if ($message['role'] === 'system') {


                $message['content'] .= "\n\n" . $content;


                return $this;

            }

        }


        // اگر system وجود نداشت
        $this->messages[] = [
            'role' => 'system',
            'content' => $content
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
