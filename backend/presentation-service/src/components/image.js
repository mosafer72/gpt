module.exports = function (slide, path, x, y, w, h) {
    slide.addImage({
        path,

        x,

        y,

        w,

        h,

        sizing: {
            type: "contain",

            x,

            y,

            w,

            h,
        },
    });
};
