const theme = require("../theme");


module.exports = function Text(
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
        context.y ?? 1;



    const width =
        context.width ?? 12;



    const maxChars =
        Math.floor(width * 11);



    const lines =
        Math.max(
            1,
            Math.ceil(
                text.length / maxChars
            )
        );



    const height =
        context.height ??
        Math.min(
            2,
            Math.max(
                0.45,
                lines * 0.32
            )
        );



    const fontSize =
        style.fontSize ??
        (
            text.length > 350
            ? 11
            : text.length > 200
            ? 12
            : text.length > 100
            ? 14
            : 16
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


            color:
                style.color ??
                theme.colors.text,


            bold:
                style.bold ??
                false,


            align:
                "right",


            valign:
                "top",


            rtlMode:true,


            margin:0,


            breakLine:true,


            wrap:true,


            fit:"shrink"

        }
    );


    return height;

};
