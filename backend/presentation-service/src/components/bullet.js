const theme = require("../theme");

module.exports = function (slide, text, y, x = 1, width = 10) {
    text = text ?? "";

    const length = text.length;

    let fontSize =
        length > 120
            ? 11
            : length > 80
              ? 13
              : length > 45
                ? 15
                : theme.sizes.body;

    const height = length > 120 ? 0.8 : length > 80 ? 0.6 : 0.4;

    /*
        Bullet Circle
        RTL => سمت راست
    */

    slide.addShape("ellipse", {
        x: x + width - 0.15,

        y: y + height / 2 - 0.05,

        w: 0.1,

        h: 0.1,

        fill: {
            color: theme.colors.primary,
        },

        line: {
            color: theme.colors.primary,
        },
    });

    /*
        Text
    */

    slide.addText(text, {
        x,

        y,

        w: width - 0.35,

        h: height,

        fontFace: theme.font,

        fontSize,

        color: theme.colors.text,

        bold: false,

        align: "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        breakLine: true,

        fit: "shrink",

        paraSpaceAfterPt: 2,
    });

    return height;
};
