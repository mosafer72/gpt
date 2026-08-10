const Bullet = require("./bullet");
const theme = require("../theme");



module.exports = function CompareBox(
    slide,
    data,
    context = {}
) {


    const content =
        data?.content ??
        data ??
        {};



    const left =
        content.left ??
        {
            title:"",
            items:[]
        };



    const right =
        content.right ??
        {
            title:"",
            items:[]
        };



    const layout =
        data?.layout ??
        context.layout ??
        {};



    const boxY =
        context.y ??
        layout.y ??
        1.5;



    const boxHeight =
        layout.height ??
        Math.max(
            3,
            Math.max(
                left.items.length,
                right.items.length
            ) * 0.55 + 1
        );



    const boxes = [

        {
            data:left,
            x:
                layout.leftX ??
                0.8,

            color:
                theme.colors.highlight
        },


        {
            data:right,

            x:
                layout.rightX ??
                7,

            color:
                theme.colors.success
        }

    ];




    boxes.forEach(box=>{


        slide.addShape(
            "roundRect",
            {

                x:box.x,

                y:boxY,

                w:
                    layout.width ??
                    5,


                h:boxHeight,


                fill:{
                    color:box.color
                },


                line:{
                    color:
                        theme.colors.border,

                    width:1
                }

            }
        );




        slide.addText(
            box.data.title ?? "",
            {

                x:
                    box.x + 0.2,


                y:
                    boxY + 0.25,


                w:
                    4.6,


                h:
                    0.35,


                fontFace:
                    theme.font,


                fontSize:20,


                bold:true,


                color:
                    theme.colors.dark,


                align:"center",


                rtlMode:true,


                margin:0

            }
        );



        let currentY =
            boxY + 0.85;



        (box.data.items ?? [])
        .forEach(item=>{


            const text =
                typeof item === "string"
                ? item
                : item.text ??
                  item.title ??
                  "";



            Bullet(
                slide,
                text,
                {
                    y:currentY,
                    x:box.x + 0.3,
                    width:4.3
                }
            );



            currentY += 0.55;


        });



    });



    return boxHeight;

};
