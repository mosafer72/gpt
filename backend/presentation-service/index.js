const PptxGenJS = require("pptxgenjs");
const { JSDOM } = require("jsdom");

const { font } = require("../../fontManager");


module.exports = function modern(ppt, data) {


    if (!data.html) {
        throw new Error("HTML content missing");
    }


    const dom = new JSDOM(data.html);

    const document = dom.window.document;


    const pages =
        document.querySelectorAll(
            "section.page"
        );


    pages.forEach((page, index) => {


        const slide =
            ppt.addSlide();


        slide.background = {
            color: "FFFFFF"
        };


        slide.addText(
            data.title ?? "",
            {
                x:0.4,
                y:0.15,
                w:12,
                h:0.3,
                fontFace:font,
                fontSize:8,
                color:"666666",
                align:"right"
            }
        );



        /*
        =========================
        PAGE TITLE
        =========================
        */


        const title =
            page.querySelector(
                ".page-header h1"
            );


        if(title){

            slide.addText(
                title.textContent.trim(),
                {
                    x:0.5,
                    y:0.45,
                    w:12,
                    h:0.6,

                    fontFace:font,

                    fontSize:24,

                    bold:true,

                    rtlMode:true,

                    align:"right"
                }
            );

        }



        let y = 1.25;



        /*
        =========================
        COMPONENT MAPPER
        =========================
        */


        const components =
            page.querySelector(
                ".section"
            )?.children ?? [];



        Array.from(
            components
        ).forEach(
            component=>{


                y =
                renderNode(
                    slide,
                    component,
                    y
                );


            }
        );



        /*
        PAGE NUMBER
        */


        slide.addText(
            `${index+1}`,
            {
                x:12.5,
                y:7,
                w:0.3,
                h:0.2,

                fontFace:font,
                fontSize:10
            }
        );


    });



};



function renderNode(
    slide,
    node,
    y
){


    const cls =
        node.className;



    switch(cls){


        /*
        =========================
        TEXT
        =========================
        */


        case "scientific-text":

            addText(
                slide,
                node.innerText,
                y
            );

            return y+0.8;



        /*
        =========================
        DEFINITION
        =========================
        */


        case "definition-box":

            addCard(
                slide,
                node,
                y,
                "Definition"
            );

            return y+1;



        /*
        =========================
        CARD
        =========================
        */


        case "concept-card":

            addCard(
                slide,
                node,
                y,
                "Concept"
            );

            return y+1;



        /*
        =========================
        KEY IDEA
        =========================
        */


        case "key-idea":

            addCard(
                slide,
                node,
                y,
                "Key Idea"
            );

            return y+1;



        /*
        =========================
        INSIGHT
        =========================
        */


        case "insight":

            addCard(
                slide,
                node,
                y,
                "Insight"
            );

            return y+0.9;



        /*
        =========================
        SUMMARY
        =========================
        */


        case "summary":

            addCard(
                slide,
                node,
                y,
                "Summary"
            );

            return y+0.9;



        /*
        =========================
        CONCLUSION
        =========================
        */


        case "conclusion":

            addCard(
                slide,
                node,
                y,
                "Conclusion"
            );

            return y+1;



        /*
        =========================
        LISTS
        =========================
        */


        case "bullet-list":

            addList(
                slide,
                node,
                y
            );

            return y+1.2;




        /*
        =========================
        COMPARISON
        =========================
        */


        case "comparison":

            addComparison(
                slide,
                node,
                y
            );

            return y+1.5;




        /*
        =========================
        PIPELINE
        =========================
        */


        case "pipeline":

            addPipeline(
                slide,
                node,
                y
            );

            return y+1.5;




        /*
        =========================
        PROCESS FLOW
        =========================
        */


        case "process-flow":

            addProcess(
                slide,
                node,
                y
            );

            return y+1.5;



        /*
        =========================
        IMAGE
        =========================
        */


        case "scientific-image":

            slide.addText(
                node.dataset.description ?? "",
                {
                    x:0.7,
                    y,
                    w:5,
                    h:1,

                    fontFace:font,

                    fontSize:12
                }
            );


            return y+1;



        default:

            return y;


    }


}





function addText(
    slide,
    text,
    y
){

    slide.addText(
        text,
        {

            x:0.7,
            y,

            w:11,

            h:0.7,

            fontFace:font,

            fontSize:14,

            rtlMode:true,

            align:"right",

            breakLine:false

        }
    );

}




function addCard(
    slide,
    node,
    y,
    label
){


    const title =
        node.querySelector("h3")
        ?.textContent ?? label;


    const text =
        node.querySelector("p")
        ?.textContent ?? "";



    slide.addText(
        title,
        {

            x:0.7,
            y,

            w:11,

            h:0.3,

            bold:true,

            fontFace:font,

            fontSize:16,

            align:"right"

        }
    );



    slide.addText(
        text,
        {

            x:0.7,

            y:y+0.35,

            w:11,

            h:0.5,

            fontFace:font,

            fontSize:12,

            rtlMode:true,

            align:"right"

        }
    );

}





function addList(
    slide,
    node,
    y
){


    const items =
        Array.from(
            node.querySelectorAll("li")
        )
        .map(
            x=>`• ${x.textContent}`
        )
        .join("\n");



    slide.addText(
        items,
        {

            x:0.8,

            y,

            w:11,

            h:1,

            fontFace:font,

            fontSize:13,

            align:"right"

        }
    );


}





function addComparison(
    slide,
    node,
    y
){


    const items =
        node.querySelectorAll(
            ".comparison-item"
        );


    let x=0.5;


    items.forEach(
        item=>{


            slide.addText(
                item.innerText,
                {

                    x,

                    y,

                    w:3.8,

                    h:1.3,

                    fontFace:font,

                    fontSize:12,

                    align:"right"

                }
            );


            x+=4;

        }
    );

}





function addPipeline(
    slide,
    node,
    y
){


    const nodes =
        node.querySelectorAll(
            ".pipeline-node"
        );


    let x=0.5;


    nodes.forEach(
        n=>{


            slide.addText(
                n.innerText,
                {

                    x,

                    y,

                    w:2.5,

                    h:1,

                    fontFace:font,

                    fontSize:11,

                    align:"center"

                }
            );


            x+=3;

        }
    );


}





function addProcess(
    slide,
    node,
    y
){


    const steps =
        node.querySelectorAll(
            ".step"
        );


    let x=0.5;


    steps.forEach(
        s=>{


            slide.addText(
                s.innerText,
                {

                    x,

                    y,

                    w:3,

                    h:1,

                    fontFace:font,

                    fontSize:11,

                    align:"right"

                }
            );


            x+=3.5;

        }
    );


}
