module.exports = function Icon(
    slide,
    data,
    context = {}
) {


    const icon =
        typeof data === "string"
        ? data
        :
        data?.icon ??
        data?.text ??
        "";



    const layout =
        data?.layout ??
        context.layout ??
        {};



    const x =
        context.x ??
        layout.x ??
        1;



    const y =
        context.y ??
        layout.y ??
        1;



    const size =
        layout.size ??
        24;



    slide.addText(
        icon,
        {

            x,


            y,


            w:
                context.width ??
                layout.width ??
                0.5,


            h:
                context.height ??
                layout.height ??
                0.5,



            fontSize:size,



            align:"center",



            valign:"mid",



            margin:0

        }
    );



    return (
        context.height ??
        layout.height ??
        0.5
    );

};
