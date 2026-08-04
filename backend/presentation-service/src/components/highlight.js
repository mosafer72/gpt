const theme = require("../theme");

module.exports = function (slide, value, y = 5) {
    let text = "";

    if (Array.isArray(value)) {
        text = value
            .map((item) => {
                if (typeof item === "string") {
                    return item;
                }

                if (typeof item === "object") {
                    return item.text ?? item.title ?? item.description ?? "";
                }

                return "";
            })
            .filter(Boolean)
            .join("\n");
    } else if (typeof value === "object") {
        text = value.text ?? value.title ?? value.description ?? "";
    } else {
        text = String(value ?? "");
    }

    const length = text.length;

    const fontSize =
        length > 120 ? 12 : length > 80 ? 14 : length > 40 ? 16 : 18;

    const height = length > 120 ? 1.5 : length > 80 ? 1.3 : 1.1;

    slide.addShape("roundRect", {
        x: 0.5,

        y,

        w: 12.3,

        h: height,

        fill: {
            color: theme.colors.highlight,
        },

        line: {
            color: theme.colors.accent,
            width: 1,
        },
    });

    slide.addText(String(text), {
        x: 0.9,

        y: y + 0.25,

        w: 11.5,

        h: height - 0.4,

        fontFace: theme.font,

        fontSize,

        bold: true,

        color: theme.colors.accent,

        align: "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        fit: "shrink",
    });
};
