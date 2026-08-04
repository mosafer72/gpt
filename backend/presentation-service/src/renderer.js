const loadTheme = require("./themeLoader");
const selectTheme = require("./themeSelector");

module.exports = function (ppt, data) {
    const themeName = selectTheme(data);

    console.log("SELECTED THEME:", themeName);

    const theme = loadTheme(themeName);

    if (!theme) {
        throw new Error(`Theme not found: ${themeName}`);
    }

    theme.cover(ppt, data);

    data.slides.forEach((slide) => {
        const layout = theme.layouts[slide.layout];

        if (layout) {
            layout(ppt, slide);
        } else {
            console.warn(
                "Layout not found:",
                slide.layout,
                "in theme:",
                themeName,
            );
        }
    });

    theme.ending(ppt, data);
};
