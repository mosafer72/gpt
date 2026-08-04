const Bullet = require("./bullet");
const theme = require("../themes/modern/theme");

module.exports = function CompareBox(slide, left, right) {
    const boxY = 1.5;

    const leftItems = Array.isArray(left?.items) ? left.items : [];

    const rightItems = Array.isArray(right?.items) ? right.items : [];

    const maxItems = Math.max(leftItems.length, rightItems.length);

    const boxH = Math.max(3.5, maxItems * 0.55 + 1.5);

    const boxes = [
        {
            data: left,

            items: leftItems,

            x: 0.8,

            color: theme.colors.highlight,
        },

        {
            data: right,

            items: rightItems,

            x: 7,

            color: theme.colors.success,
        },
    ];

    boxes.forEach((box) => {
        /*
            Box
        */

        slide.addShape("roundRect", {
            x: box.x,

            y: boxY,

            w: 5,

            h: boxH,

            fill: {
                color: box.color,
            },

            line: {
                color: theme.colors.border,

                width: 1,
            },
        });

        /*
            Title
        */

        slide.addText(box.data?.title ?? "", {
            x: box.x + 0.2,

            y: boxY + 0.25,

            w: 4.6,

            h: 0.35,

            fontFace: theme.font,

            fontSize: 20,

            bold: true,

            color: theme.colors.dark,

            align: "center",

            rtlMode: true,

            margin: 0,
        });

        /*
            Items
        */

        let currentY = boxY + 0.85;

        box.items.forEach((item) => {
            const text =
                typeof item === "string"
                    ? item
                    : (item.title ?? item.description ?? "");

            if (!text.trim()) return;

            Bullet(slide, text, currentY, box.x + 0.3, 4.3);

            currentY += 0.55;
        });
    });
};
