<?php

namespace App\Application\Services;


use App\Models\Usage;
use App\Models\Workspace;


class UsageService
{


    public function all(
        Workspace $workspace
    ) {

        return Usage::where(
            'workspace_id',
            $workspace->id
        )
        ->latest()
        ->get();

    }




    public function today(
        Workspace $workspace
    ): array {

        $usage = Usage::where(
            'workspace_id',
            $workspace->id
        )
        ->whereDate(
            'created_at',
            today()
        )
        ->get();


        return $this->calculate($usage);

    }




    public function create(
        Workspace $workspace,
        array $data
    ): Usage {


        return Usage::create([

            'workspace_id'=>$workspace->id,

            ...$data

        ]);

    }




    public function summary(
        Workspace $workspace,
        array $filters=[]
    ): array {


        $query = Usage::where(
            'workspace_id',
            $workspace->id
        );



        if(isset($filters['from'])) {

            $query->whereDate(
                'created_at',
                '>=',
                $filters['from']
            );

        }



        if(isset($filters['to'])) {

            $query->whereDate(
                'created_at',
                '<=',
                $filters['to']
            );

        }



        return $this->calculate(
            $query->get()
        );

    }




    private function calculate(
        $usage
    ): array {


        return [

            'requests'=>$usage->count(),


            'input_tokens'=>
                $usage->sum(
                    'input_tokens'
                ),


            'output_tokens'=>
                $usage->sum(
                    'output_tokens'
                ),


            'total_tokens'=>
                $usage->sum(
                    'total_tokens'
                ),


            'total_cost'=>
                $usage->sum(
                    'total_cost'
                )

        ];

    }


}
