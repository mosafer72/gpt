const theme = require("./theme");

const { Title, Footer } = require("../../components");

module.exports = function (ppt, data) {
    const slide = ppt.addSlide();

    slide.background = {
        color: theme.colors.bg,
    };

    // Header

    slide.addShape("rect", {
        x: 0,

        y: 0,

        w: 13.33,

        h: 1.4,

        fill: {
            color: theme.colors.primary,
        },

        line: {
            color: theme.colors.primary,
        },
    });

    Title(slide, data.title || "نتیجه‌گیری");

    const summary = data.summary || "خلاصه‌ای برای این ارائه ثبت نشده است.";

    // Summary card

    slide.addShape("roundRect", {
        x: 1,

        y: 1.8,

        w: 11,

        h: 2.3,

        fill: {
            color: theme.colors.card,
        },

        line: {
            color: theme.colors.border,

            width: 1,
        },
    });

    slide.addText(summary, {
        x: 1.4,

        y: 2.2,

        w: 10.2,

        h: 1.2,

        fontFace: theme.font,

        fontSize: summary.length > 220 ? 18 : 22,

        color: theme.colors.text,

        align: "right",

        valign: "mid",

        rtlMode: true,

        margin: 0,

        breakLine: false,
    });

    // Closing badge

    slide.addShape("roundRect", {
        x: 4.3,

        y: 4.8,

        w: 4.7,

        h: 0.65,

        fill: {
            color: theme.colors.highlight,
        },

        line: {
            color: theme.colors.primary,

            width: 1,
        },
    });

    slide.addText("پایان ارائه", {
        x: 4.3,

        y: 4.98,

        w: 4.7,

        h: 0.3,

        fontFace: theme.font,

        fontSize: 17,

        bold: true,

        color: theme.colors.primary,

        align: "center",

        rtlMode: true,

        margin: 0,
    });

    Footer(slide, 1);
};
