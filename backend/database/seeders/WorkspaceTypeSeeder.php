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
                'key' => 'student',
                'name' => 'student',
                'display_name' => 'Student AI',
                'description' => 'دستیار هوشمند دانشجویان',
                'icon' => '🎓',
                'features' => [

                    'خلاصه سازی جزوه',
                    'حل تمرین',
                    'تحقیق',
                    'ارائه'

                ]

            ],



            [
                'key' => 'developer',
                'name' => 'developer',
                'display_name' => 'Developer AI',
                'description' => 'محیط برنامه نویسی و امنیت نرم افزار',
                'icon' => '💻',
                'features' => [

                    'Code Review',
                    'Debug',
                    'Security Analysis',
                    'Repository'

                ]

            ],



            [
                'key' => 'research',
                'name' => 'research',
                'display_name' => 'Research AI',
                'description' => 'دستیار مقاله و تحقیقات علمی',
                'icon' => '📚',
                'features' => [

                    'تحلیل PDF',
                    'خلاصه سازی مقاله',
                    'Citation',
                    'ترجمه علمی'

                ]

            ],



            [
                'key' => 'creator',
                'name' => 'creator',
                'display_name' => 'Creator AI',
                'description' => 'تولید محتوا و مارکتینگ',
                'icon' => '🎨',
                'features' => [

                    'Content Writing',
                    'SEO',
                    'Image AI',
                    'Planner'

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
