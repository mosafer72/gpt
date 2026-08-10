const theme = require("../theme");


module.exports = function Line(
    slide,
    data,
    context = {}
){

    const layout =
        data?.layout ??
        context.layout ??
        {};



    slide.addShape(
        "line",
        {

            x:
                context.x ??
                layout.x ??
                0.7,


            y:
                context.y ??
                layout.y ??
                1,



            w:
                context.width ??
                layout.width ??
                11,



            h:0,



            line:{

                color:
                    theme.colors.border,


                width:
                    layout.stroke ??
                    1

            }

        }
    );



    return 0.1;

};
