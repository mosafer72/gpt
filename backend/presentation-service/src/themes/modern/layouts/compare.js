const { Title, Footer, Bullet } = require("../../../components");
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

    const left = data.left || {
        title: "",
        items: [],
    };

    const right = data.right || {
        title: "",
        items: [],
    };

    const boxY = 1.5;
    const boxH = 5.2;

    // LEFT BOX

    slide.addShape("roundRect", {
        x: 0.8,

        y: boxY,

        w: 5,

        h: boxH,

        fill: {
            color: theme.colors.highlight,
        },

        line: {
            color: theme.colors.border,
            width: 1,
        },
    });

    slide.addText(left.title || "", {
        x: 1,

        y: 1.8,

        w: 4.5,

        h: 0.35,

        fontFace: theme.font,

        fontSize: 20,

        bold: true,

        color: theme.colors.dark,

        align: "center",

        rtlMode: true,

        margin: 0,
    });

    let leftY = 2.35;

    (left.items || []).slice(0, 5).forEach((item) => {
        const text =
            typeof item === "string"
                ? item
                : `${item.title ?? ""} ${item.description ?? ""}`;

        if (text.trim()) {
            Bullet(slide, text, leftY, 1.1, 4);

            leftY += 0.65;
        }
    });

    // RIGHT BOX

    slide.addShape("roundRect", {
        x: 7,

        y: boxY,

        w: 5,

        h: boxH,

        fill: {
            color: theme.colors.success,
        },

        line: {
            color: theme.colors.border,
            width: 1,
        },
    });

    slide.addText(right.title || "", {
        x: 7.3,

        y: 1.8,

        w: 4.5,

        h: 0.35,

        fontFace: theme.font,

        fontSize: 20,

        bold: true,

        color: theme.colors.dark,

        align: "center",

        rtlMode: true,

        margin: 0,
    });

    let rightY = 2.35;

    (right.items || []).slice(0, 5).forEach((item) => {
        const text =
            typeof item === "string"
                ? item
                : `${item.title ?? ""} ${item.description ?? ""}`;

        if (text.trim()) {
            Bullet(slide, text, rightY, 7.3, 4);

            rightY += 0.65;
        }
    });

    Footer(slide, 1);
};
