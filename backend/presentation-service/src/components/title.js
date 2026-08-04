const theme = require("../theme");

module.exports = function (slide, title) {
    const length = title?.length || 0;

    let fontSize = theme.sizes.title;

    if (length > 40) {
        fontSize = 24;
    }

    if (length > 70) {
        fontSize = 20;
    }

    slide.addText(title, {
        x: 0.7,
        y: 0.25,

        w: 11,

        h: length > 50 ? 0.8 : 0.5,

        fontFace: theme.font,

        fontSize,

        bold: true,

        color: theme.colors.secondary,

        align: "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        breakLine: false,
    });
};
