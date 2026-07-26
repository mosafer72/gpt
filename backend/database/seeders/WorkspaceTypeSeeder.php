<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkspaceType;


class WorkspaceTypeSeeder extends Seeder
{

    public function run(): void
    {


        $types = [

            [

                'key'=>'personal',

                'name'=>'personal',

                'display_name'=>'Personal AI',

                'description'=>'دستیار هوشمند عمومی و روزمره',

                'icon'=>'💬',

                'features'=>[

                    'Chat',
                    'Memory',
                    'General Assistant'

                ]

            ],



            [

                'key'=>'student',

                'name'=>'student',

                'display_name'=>'Student AI',

                'description'=>'دستیار هوشمند دانشجویان',

                'icon'=>'🎓',

                'features'=>[

                    'PDF Analysis',
                    'Research',
                    'Summarize',
                    'Study Assistant'

                ]

            ]

        ];





        foreach($types as $type)
        {


            WorkspaceType::updateOrCreate(

                [

                    'key'=>$type['key']

                ],

                [

                    'name'=>$type['name'],

                    'display_name'=>$type['display_name'],

                    'description'=>$type['description'],

                    'icon'=>$type['icon'],

                    'features'=>$type['features'],

                    'is_active'=>true

                ]

            );


        }


    }

}
