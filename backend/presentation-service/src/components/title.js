const theme = require("../theme");


module.exports = function Title(
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
        context.y ?? 0.25;



    const width =
        context.width ?? 12;



    const length =
        text.length;



    let fontSize =
        style.fontSize ??
        theme.sizes.title;



    if(length > 60){
        fontSize = 22;
    }


    if(length > 100){
        fontSize = 18;
    }




    const height =
        context.height ??
        (
            length > 60
            ? 0.75
            : 0.5
        );





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


            bold:
                style.bold ??
                true,


            color:
                style.color ??
                theme.colors.secondary,


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
