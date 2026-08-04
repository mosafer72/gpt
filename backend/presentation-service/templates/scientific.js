const { cardsLayout, textLayout } = require("../src/layouts");

module.exports = (ppt, data) => {
    data.slides.forEach((slide) => {
        if (slide.layout === "cards") cardsLayout(ppt, slide);

        if (slide.layout === "text") textLayout(ppt, slide);
    });
};
