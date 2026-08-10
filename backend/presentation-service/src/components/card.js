const theme = require("../theme");


module.exports = function Card(
    slide,
    data,
    context = {}
) {


    const content =
        data?.content ??
        data ??
        {};



    const title =
        content.title ??
        "";



    const text =
        content.text ??
        content.description ??
        "";



    const style =
        data?.style ??
        context.style ??
        {};



    const x =
        context.x ?? 0.7;



    const y =
        context.y ?? 1;



    const width =
        context.width ?? 11.8;



    const titleHeight =
        title
        ? 0.35
        : 0;



    const charsPerLine =
        Math.floor(width * 12);



    const lines =
        Math.max(
            1,
            Math.ceil(
                text.length /
                charsPerLine
            )
        );



    const textHeight =
        lines * 0.32;



    const height =
        context.height ??
        Math.min(
            2.5,
            Math.max(
                1,
                0.55 +
                titleHeight +
                textHeight
            )
        );




    slide.addShape(
        "roundRect",
        {

            x,

            y,

            w:width,

            h:height,


            fill:{

                color:
                    style.background ??
                    "F8FAFC"

            },


            line:{

                color:
                    style.border ??
                    "E2E8F0",


                width:1

            }

        }
    );



    let currentY =
        y + 0.18;



    if(title){


        slide.addText(
            title,
            {

                x:x + 0.25,


                y:currentY,


                w:width - 0.5,


                h:0.35,


                fontFace:
                    theme.font,


                fontSize:
                    style.titleSize ??
                    17,


                bold:true,


                color:
                    style.titleColor ??
                    theme.colors.primary,



                align:"right",



                valign:"mid",



                rtlMode:true,



                margin:0,


                fit:"shrink"

            }
        );


        currentY += 0.45;

    }





    slide.addText(
        text,
        {

            x:x + 0.25,


            y:currentY,


            w:width - 0.5,


            h:
                height -
                (currentY - y) -
                0.15,


            fontFace:
                theme.font,


            fontSize:
                style.fontSize ??
                (
                    text.length > 300
                    ? 11
                    : text.length > 150
                    ? 12
                    : 14
                ),



            color:
                style.color ??
                theme.colors.text,



            align:"right",



            valign:"top",



            rtlMode:true,



            margin:0,



            breakLine:true,



            wrap:true,



            fit:"shrink"

        }
    );



    return height;

};
