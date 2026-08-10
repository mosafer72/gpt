const { Title, Subtitle, Footer } = require("../../components");

const theme = require("./theme");


function calculateTextBox(text, width, baseFont = 22) {

    const chars = text?.length || 0;

    const charsPerLine = width * 10;

    const lines = Math.max(
        1,
        Math.ceil(chars / charsPerLine)
    );


    let fontSize = baseFont;


    if (chars > 120) {
        fontSize = 16;
    }
    else if (chars > 80) {
        fontSize = 18;
    }
    else if (chars > 40) {
        fontSize = 20;
    }


    return {
        fontSize,
        height: lines * (fontSize / 28),
    };

}



class Layout {

    constructor(start = 1.6) {

        this.y = start;

        this.gap = 0.25;

    }


    add(height) {

        const y = this.y;

        this.y += height + this.gap;

        return y;

    }

}



module.exports = function cover(slide, page) {


    slide.background = {

        color:
            theme.colors.background ||
            theme.colors.bg ||
            "F8FAFC"

    };



    slide.addShape("rect", {

        x: 0,

        y: 0,

        w: 13.33,

        h: 1.15,


        fill: {

            color: theme.colors.primary,

        },


        line: {

            color: theme.colors.primary,

        },

    });



    const layout = new Layout();



    /*
     * Page title
     */

    if (page.title) {


        Title(

            slide,

            page.title,

            layout.add(0.5)

        );


    }



    /*
     * Page subtitle
     */

    if (page.subtitle) {


        const box =
            calculateTextBox(
                page.subtitle,
                11
            );



        slide.addText(
            page.subtitle,
            {

                x: 1,

                y: layout.add(
                    box.height
                ),

                w: 11,

                h: box.height,


                fontFace: theme.font,

                fontSize: box.fontSize,


                color:
                    theme.colors.muted ||
                    theme.colors.text,


                align: "right",

                rtlMode: true,

                margin: 0,

                fit: "shrink"

            }
        );


    }




    /*
     * Elements
     *
     * New schema:
     *
     * elements:{
     *   element1:{
     *      type:"image",
     *      content:{
     *          path:"..."
     *      }
     *   }
     * }
     */


    if (page.elements) {


        const elements =
            Array.isArray(page.elements)
            ?
            page.elements
            :
            Object.values(
                page.elements
            );



        elements.forEach(
            (element)=>{


                if (
                    element.type === "image" ||
                    element.type === "scientific-image"
                ) {


                    const Image =
                        require("../../components/image");



                    Image(

                        slide,

                        element.content || {},

                        1,

                        layout.add(2),

                        4,

                        3

                    );


                }


            }
        );


    }




    Footer(
        slide,
        1
    );

};
