const theme = require("../theme");
module.exports = function Card(slide, item, y, options = {}) {
    const x = options.x ?? 0.6;

    const w = options.w ?? 11;

    const isCompare = options.compare ?? false;

    const lines = item.description ? item.description.split("\n") : [];

    const h = isCompare ? Math.max(2, lines.length * 0.35 + 0.9) : 0.9;

    slide.addShape("roundRect", {
        x,

        y,

        w,

        h,

        fill: {
            color: "F8FAFC",
        },

        line: {
            color: "E2E8F0",
        },
    });

    slide.addText(item.title ?? "", {
        x: x + 0.25,

        y: y + 0.15,

        w: w - 0.5,

        h: 0.3,

        fontFace: theme.font,

        fontSize: 18,

        bold: true,

        color: theme.colors.primary,

        align: "right",

        rtlMode: true,

        margin: 0,
    });

    slide.addText(item.description ?? "", {
        x: x + 0.3,

        y: y + 0.55,

        w: w - 0.6,

        h: h - 0.7,

        fontFace: theme.font,

        fontSize: 13,

        color: theme.colors.text,

        align: "right",

        valign: "top",

        rtlMode: true,

        margin: 0,

        breakLine: false,

        fit: "shrink",
    });

    return h;
};
