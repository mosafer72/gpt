const fs = require("fs");
const path = require("path");
const { XMLParser } = require("fast-xml-parser");



function normalizeElement(type, value){


    if(typeof value === "string"){

        return {

            type,

            content:{
                title:"",
                text:value.trim(),
                items:[]
            },

            layout:{},
            style:{}

        };

    }



    let title =
        value?.h3 ??
        "";


    let text =
        value?.["#text"] ??
        "";



    if(typeof text !== "string"){

        text =
            JSON.stringify(value);

    }



    return {

        type,


        content:{

            title:
                title
                .replace(/<[^>]+>/g,"")
                .trim(),


            text:
                text
                .replace(/<[^>]+>/g,"")
                .trim(),


            items:
                []

        },


        layout:{
            x:null,
            y:null,
            width:null,
            height:null
        },


        style:{}

    };

}





function parsePage(page,index){


    let elements=[];


    Object.keys(page).forEach(key=>{


        if(
            key==="page-header"
        ){
            return;
        }


        let value =
            page[key];



        if(Array.isArray(value)){


            value.forEach(v=>{


                elements.push(
                    normalizeElement(
                        key,
                        v
                    )
                );


            });


        }
        else{


            elements.push(
                normalizeElement(
                    key,
                    value
                )
            );

        }


    });



    let title =
        page["page-header"]
        ?.h1
        ??
        `Page ${index+1}`;



    return {

        id:index+1,

        template:"content",


        title:
            title
            .replace(/<[^>]+>/g,"")
            .trim(),


        subtitle:"",


        elements

    };


}







function xmlToPresentation(xml){


    const parser =
        new XMLParser({

            ignoreAttributes:false,

            preserveOrder:false,

            trimValues:true

        });



    const result =
        parser.parse(xml);



    const pages =
        result["scientific-document"]
        ?.page
        ??
        [];



    const presentation = {


        meta:{


            generatedAt:
                new Date()
                .toISOString(),


            pages:
                pages.length


        },


        pages:
            pages.map(
                parsePage
            ),


        ending:{


            summary:
            "Generated successfully"


        }


    };



    return presentation;


}








function loadPresentation(input){



    let presentation;



    /*
      اگر XML بود
    */

    if(
        typeof input==="string" &&
        input.includes("<scientific-document>")
    ){

        presentation =
            xmlToPresentation(
                input
            );


    }
    else{


        presentation =
            input;


    }



    const output =
        path.join(
            __dirname,
            "presentation.json"
        );



    fs.writeFileSync(
        output,
        JSON.stringify(
            presentation,
            null,
            2
        ),
        "utf8"
    );



    console.log(
        "JSON SAVED:",
        output
    );



    return presentation;


}



module.exports={
    loadPresentation
};
