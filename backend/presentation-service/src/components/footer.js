const theme = require("../theme");

module.exports = function (slide, page) {
    slide.addText(`${page}`, {
        x: 12,
        y: 7,
        w: 0.4,
        h: 0.2,

        fontFace: theme.font,
        fontSize: 10,

        color: theme.colors.muted,

        align: "center",
    });
};
