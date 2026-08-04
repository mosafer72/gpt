module.exports = function (slide, icon, x, y) {
    const size = icon?.length > 2 ? 18 : 24;

    slide.addText(icon, {
        x,

        y,

        w: 0.45,

        h: 0.45,

        fontSize: size,

        align: "center",

        valign: "mid",

        margin: 0,
    });
};
