const theme = require("../theme");

module.exports = function (slide, y) {
    slide.addShape("line", {
        x: 0.7,
        y,
        w: 11.5,
        h: 0,

        line: {
            color: theme.colors.border,
            width: 1,
        },
    });
};
