const { addTitle, addCard, addHighlight } = require("./components");

function cardsLayout(ppt, data) {
    let slide = ppt.addSlide();

    addTitle(slide, data.title);

    let y = 1.2;

    data.items.forEach((item) => {
        addCard(slide, item, y);

        y += 1.4;
    });

    if (data.highlight) addHighlight(slide, data.highlight, 5.5);
}

function textLayout(ppt, data) {
    let slide = ppt.addSlide();

    addTitle(slide, data.title);

    slide.addText(data.text, {
        x: 1,
        y: 1.4,
        w: 10.5,
        h: 3,

        fontFace: "Vazirmatn",
        fontSize: 18,

        align: "right",
        rtlMode: true,
    });
}

module.exports = {
    cardsLayout,
    textLayout,
};
