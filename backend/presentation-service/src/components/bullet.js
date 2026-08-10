const theme = require("../theme");


module.exports = function Bullet(
    slide,
    data,
    context = {}
) {


    const text =
        typeof data === "string"
        ? data
        : data?.text ??
          data?.title ??
          "";



    const style =
        data?.style ??
        context.style ??
        {};



    const x =
        context.x ?? 1;



    const y =
        context.y ?? 1;



    const width =
        context.width ?? 10.5;



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



    const height =
        context.height ??
        Math.min(
            1.2,
            Math.max(
                0.35,
                lines * 0.28
            )
        );





    /*
        Bullet Point
    */

    slide.addShape(
        "ellipse",
        {

            x:
                x + width - 0.12,


            y:
                y + height / 2 - 0.04,


            w:0.08,


            h:0.08,


            fill:{

                color:
                    theme.colors.primary

            },


            line:{

                color:
                    theme.colors.primary

            }

        }
    );





    slide.addText(
        text,
        {

            x,


            y,


            w:
                width - 0.25,


            h:
                height,


            fontFace:
                theme.font,


            fontSize:
                style.fontSize ??
                (
                    text.length > 200
                    ? 11
                    : text.length > 100
                    ? 13
                    : 15
                ),



            color:
                style.color ??
                theme.colors.text,



            bold:
                style.bold ??
                false,



            align:"right",



            valign:"mid",



            rtlMode:true,



            margin:0,



            breakLine:true,



            wrap:true,



            fit:"shrink",



            paraSpaceAfterPt:3

        }
    );



    return height;

};
