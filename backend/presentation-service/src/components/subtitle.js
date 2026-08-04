const theme = require("../theme");

module.exports = function (slide, value, y = 0.95) {
    const length = value?.length || 0;

    let fontSize = theme.sizes.subtitle;

    let height = 0.6;

    if (length > 80) {
        fontSize = 16;
        height = 1;
    }

    if (length > 150) {
        fontSize = 14;
        height = 1.4;
    }

    slide.addText(value, {
        x: 0.7,

        y,

        w: 11,

        h: height,

        fontFace: theme.font,

        fontSize,

        color: theme.colors.muted,

        align: "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        breakLine: false,

        fit: "shrink",
    });
};
