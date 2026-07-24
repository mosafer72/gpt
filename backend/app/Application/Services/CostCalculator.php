<?php

namespace App\Application\Services;


use App\Models\AiModel;


class CostCalculator
{


    public function calculate(
        AiModel $model,
        int $inputTokens,
        int $outputTokens
    ): array {


        $inputCost =
            ($inputTokens / 1000000)
            *
            $model->input_cost;



        $outputCost =
            ($outputTokens / 1000000)
            *
            $model->output_cost;



        return [

            'input_cost'=>$inputCost,

            'output_cost'=>$outputCost,

            'total_cost'=>
                $inputCost + $outputCost

        ];

    }

}
