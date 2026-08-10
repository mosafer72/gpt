const theme = require("../theme");


module.exports = function Highlight(
    slide,
    data,
    context = {}
) {


    let text = "";



    if(typeof data === "string"){

        text = data;

    }
    else if(Array.isArray(data)){


        text =
            data
            .map(item => {

                if(typeof item === "string")
                    return item;


                return (
                    item.text ??
                    item.title ??
                    item.description ??
                    ""
                );

            })
            .filter(Boolean)
            .join("\n");

    }
    else {

        text =
            data?.text ??
            data?.description ??
            data?.title ??
            "";

    }




    const style =
        data?.style ??
        context.style ??
        {};




    const x =
        context.x ?? 0.7;



    const y =
        context.y ?? 5;



    const width =
        context.width ?? 11.8;




    const lines =
        Math.max(
            1,
            Math.ceil(
                text.length /
                Math.floor(width * 10)
            )
        );



    const height =
        context.height ??
        Math.min(
            1.8,
            Math.max(
                0.9,
                lines * 0.35 + 0.4
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
                    theme.colors.highlight

            },


            line:{

                color:
                    style.border ??
                    theme.colors.accent,


                width:1

            }

        }
    );




    slide.addText(
        text,
        {

            x:x + 0.35,


            y:y + 0.2,


            w:width - 0.7,


            h:height - 0.4,


            fontFace:
                theme.font,


            fontSize:
                style.fontSize ??
                (
                    text.length > 250
                    ? 11
                    : text.length > 120
                    ? 13
                    : 15
                ),



            bold:
                style.bold ??
                true,



            color:
                style.color ??
                theme.colors.accent,



            align:"right",


            valign:"mid",



            rtlMode:true,



            margin:0,


            breakLine:true,


            wrap:true,


            fit:"shrink"

        }
    );



    return height;

};
