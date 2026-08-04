const theme = require("../theme");

module.exports = function Text(
    slide,
    text,
    y,
    x = 1,
    width = 10,
    options = {},
) {
    const length = text?.length || 0;

    const fontSize =
        options.fontSize ?? (length > 150 ? 12 : length > 80 ? 14 : 16);

    const height =
        options.height ?? (length > 150 ? 0.9 : length > 80 ? 0.65 : 0.4);

    slide.addText(text ?? "", {
        x,

        y,

        w: width,

        h: height,

        fontFace: theme.font,

        fontSize,

        color: options.color ?? theme.colors.text,

        bold: options.bold ?? false,

        align: options.align ?? "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        breakLine: false,

        fit: "shrink",
    });

    return height;
};
