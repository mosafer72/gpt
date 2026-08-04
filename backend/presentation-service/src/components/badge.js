const theme = require("../theme");

module.exports = function (slide, value, y = 5.2) {
    const length = value?.length || 0;

    const fontSize =
        length > 120 ? 11 : length > 80 ? 13 : length > 45 ? 15 : 16;

    const height = length > 140 ? 1.4 : length > 90 ? 1.1 : 0.9;

    slide.addShape("roundRect", {
        x: 0.7,

        y,

        w: 11.8,

        h: height,

        fill: {
            color: theme.colors.highlight,
        },

        line: {
            color: theme.colors.primary,

            width: 1,
        },
    });

    slide.addText(value, {
        x: 1,

        y: y + (height - 0.35) / 2,

        w: 11.2,

        h: height - 0.2,

        fontFace: theme.font,

        fontSize,

        bold: true,

        color: theme.colors.primary,

        align: "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        breakLine: false,

        fit: "shrink",
    });
};
