const { Title, Subtitle, Footer, Badge } = require("../../../components");
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

    let y = 1.2;

    if (data.subtitle) {
        Subtitle(slide, data.subtitle, y);

        y += 0.8;
    }

    y += 0.3;

    (data.items || []).forEach((item) => {
        slide.addShape("roundRect", {
            x: 0.8,

            y,

            w: 11.5,

            h: 1.1,

            fill: {
                color: "FFFFFF",
            },

            line: {
                color: "E2E8F0",
            },
        });

        slide.addText("● " + item.heading, {
            x: 1.1,

            y: y + 0.15,

            w: 10,

            h: 0.3,

            fontFace: "Vazirmatn",

            fontSize: 19,

            bold: true,

            color: "4F46E5",

            align: "right",

            rtlMode: true,

            margin: 0,
        });

        slide.addText(item.description, {
            x: 1.3,

            y: y + 0.55,

            w: 10,

            h: 0.35,

            fontFace: "Vazirmatn",

            fontSize: 16,

            color: "334155",

            align: "right",

            rtlMode: true,

            margin: 0,
        });

        y += 1.35;
    });

    // if (data.highlight) {
    //     Badge(slide, data.highlight, 0.8, 5.4);
    // }

    Footer(slide, 1);
};
