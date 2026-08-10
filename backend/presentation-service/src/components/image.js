const pathUtil = require("path");


module.exports = function Image(
    slide,
    data,
    context = {}
) {


    const content =
        data?.content ??
        data ??
        {};



    const layout =
        data?.layout ??
        context.layout ??
        {};



    const imagePath =
        typeof content === "string"
        ? content
        :
        (
            content.src ??
            content.path ??
            ""
        );



    const x =
        context.x ??
        layout.x ??
        1;



    const y =
        context.y ??
        layout.y ??
        1;



    const width =
        context.width ??
        layout.width ??
        4.5;



    const height =
        context.height ??
        layout.height ??
        2.5;



    /*
        اگر تصویر واقعی وجود ندارد
        چیزی ارسال نکن
    */

    if(
        !imagePath ||
        !pathUtil.isAbsolute(imagePath) ||
        !require("fs").existsSync(imagePath)
    ){

        return height;

    }



    slide.addImage(
        {

            path:imagePath,


            x,


            y,


            w:width,


            h:height,



            sizing:{

                type:"contain",

                x,

                y,

                w:width,

                h:height

            }

        }
    );



    return height;

};
