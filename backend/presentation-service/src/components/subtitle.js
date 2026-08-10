const theme = require("../theme");


module.exports = function Subtitle(
    slide,
    data,
    context = {}
) {


    const text =
        typeof data === "string"
        ? data
        : data?.text ?? "";



    const style =
        data?.style ??
        context.style ??
        {};



    const x =
        context.x ?? 0.7;



    const y =
        context.y ?? 0.9;



    const width =
        context.width ?? 11.8;



    const length =
        text.length;



    let fontSize =
        style.fontSize ??
        theme.sizes.subtitle;



    let height =
        context.height ??
        0.5;



    if(length > 100){

        fontSize = 14;

        height = 0.8;

    }



    if(length > 200){

        fontSize = 12;

        height = 1.1;

    }





    slide.addText(
        text,
        {

            x,

            y,

            w:width,

            h:height,


            fontFace:
                theme.font,


            fontSize,


            color:
                style.color ??
                theme.colors.muted,


            bold:
                style.bold ??
                false,


            align:
                "right",


            valign:
                "mid",


            rtlMode:true,


            margin:0,


            breakLine:true,


            wrap:true,


            fit:"shrink"

        }
    );



    return height;

};
