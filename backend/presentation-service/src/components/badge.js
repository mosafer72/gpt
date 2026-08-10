const theme = require("../theme");


module.exports = function Badge(
    slide,
    data,
    context = {}
){

    const text =
        typeof data === "string"
        ? data
        :
        data?.text ??
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
        context.width ?? 10.5;



    const height =
        context.height ??
        (
            text.length > 120
            ? 1.2
            : 0.8
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
                    theme.colors.primary

            }

        }
    );



    slide.addText(
        text,
        {

            x:x+0.25,


            y:y+0.15,


            w:width-0.5,


            h:height-0.3,


            fontFace:
                theme.font,


            fontSize:
                style.fontSize ??
                14,


            bold:true,


            color:
                style.color ??
                theme.colors.primary,


            align:"right",


            valign:"mid",


            rtlMode:true,


            margin:0,


            fit:"shrink",


            breakLine:true

        }
    );



    return height;

};
