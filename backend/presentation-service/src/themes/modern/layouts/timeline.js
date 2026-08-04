const { Title, Footer, Subtitle } = require("../../../components");
const theme = require("../theme");

module.exports = function (ppt, data) {
    const slide = ppt.addSlide();

    slide.addShape("rect", {
        x: 0,
        y: 0,
        w: 13.33,
        h: 1,

        fill: {
            color: theme.colors.primary,
        },

        line: {
            color: theme.colors.primary,
        },
    });

    Title(slide, data.title);

    let y = 1.4;

    if (data.subtitle) {
        Subtitle(slide, data.subtitle, y);

        y += 0.8;
    }

    const items = data.items || [];

    // timeline line

    slide.addShape("line", {
        x: 11.6,

        y: 1.8,

        w: 0,

        h: Math.min(items.length * 0.75, 3.8),

        line: {
            color: theme.colors.border,
            width: 2,
        },
    });

    items.slice(0, 5).forEach((item) => {
        const title = item.year ?? item.heading ?? item.title ?? "";

        const text = item.text ?? item.description ?? "";

        // point

        slide.addShape("ellipse", {
            x: 11.48,

            y: y + 0.08,

            w: 0.22,

            h: 0.22,

            fill: {
                color: theme.colors.primary,
            },

            line: {
                color: theme.colors.primary,
            },
        });

        // heading

        slide.addText(title, {
            x: 9.5,

            y,

            w: 1.7,

            h: 0.35,

            fontFace: theme.font,

            fontSize: 18,

            bold: true,

            color: theme.colors.primary,

            align: "right",

            rtlMode: true,

            margin: 0,
        });

        // description

        slide.addText(text, {
            x: 1.2,

            y: y - 0.02,

            w: 7.8,

            h: 0.45,

            fontFace: theme.font,

            fontSize: 16,

            color: theme.colors.text,

            align: "right",

            rtlMode: true,

            margin: 0,

            breakLine: false,
        });

        y += 0.75;
    });

    Footer(slide, 1);
};
