<?
Route::get('/test-chat', function(){


    $context =
        new \App\Application\Context\ChatContext(

            userId:"1",

            conversationId:"100",

            currentMessage:
            "من برنامه نویس لاراول هستم"

        );



    $pipeline =
        app(\App\Application\Pipelines\ChatPipeline::class);



    $result =
        $pipeline->handle($context);



    return $result->toArray();


});
