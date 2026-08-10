const theme = require("../theme");


module.exports = function Footer(
    slide,
    data
) {


    const page =
        typeof data === "number"
        ? data
        : data?.page ??
          "";



    slide.addText(
        String(page),
        {

            x:12,

            y:7,


            w:0.4,


            h:0.2,


            fontFace:
                theme.font,


            fontSize:10,


            color:
                theme.colors.muted,


            align:"center"

        }
    );


};
