const theme = require("./theme");

function addTitle(slide, title) {
    slide.addText(title, {
        x: 0.7,
        y: 0.3,
        w: 11,
        h: 0.5,

        fontFace: theme.font,
        fontSize: theme.sizes.title,

        bold: true,
        color: theme.colors.dark,

        align: "right",
        rtlMode: true,
    });
}

function addCard(slide, item, y) {
    slide.addShape("roundRect", {
        x: 0.8,
        y,
        w: 11.5,
        h: 1.2,

        fill: {
            color: theme.colors.card,
        },

        line: {
            color: theme.colors.border,
        },
    });

    slide.addText("● " + item.heading, {
        x: 1.1,
        y: y + 0.15,
        w: 10,
        h: 0.3,

        fontFace: theme.font,
        fontSize: 19,

        bold: true,

        color: theme.colors.primary,

        align: "right",
        rtlMode: true,
    });

    slide.addText(item.description, {
        x: 1.3,
        y: y + 0.55,
        w: 10,
        h: 0.4,

        fontFace: theme.font,
        fontSize: 16,

        color: theme.colors.text,

        align: "right",
        rtlMode: true,
    });
}

function addHighlight(slide, value, y) {
    slide.addShape("roundRect", {
        x: 1,
        y,
        w: 10.8,
        h: 0.7,

        fill: {
            color: "DCFCE7",
        },

        line: {
            color: theme.colors.accent,
        },
    });

    slide.addText("💡 " + value, {
        x: 1.3,
        y: y + 0.2,
        w: 10,
        h: 0.3,

        fontFace: theme.font,
        fontSize: 16,

        bold: true,

        color: "047857",

        align: "right",
        rtlMode: true,
    });
}

module.exports = {
    addTitle,
    addCard,
    addHighlight,
};
