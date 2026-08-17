<?php

namespace App\Application\Tools;


use App\Domain\Contracts\Tool;


class ToolRegistry
{


    public function __construct(
        protected WebSearchTool $webSearch
    ){}



    public function get(
        string $name
    ): ?Tool
    {

        return match($name){

            'web_search'
                => $this->webSearch,


            default
                => null

        };

    }


}
