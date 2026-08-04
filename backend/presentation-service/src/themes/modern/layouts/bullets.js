const { Title, Subtitle, Footer, Bullet } = require("../../../components");
const theme = require("../theme");

module.exports = function (slide, page) {
    let y = 1.4;

    if (page.title) {
        Title(slide, page.title, y);

        y += 0.6;
    }

    if (page.subtitle) {
        Subtitle(slide, page.subtitle, y);

        y += 0.5;
    }

    const elements = page.elements ?? [];

    elements.forEach((element) => {
        if (element.type !== "bullet") return;

        Bullet(slide, element.text, y);

        // فاصله 20px
        y += 0.65;
    });

    Footer(slide, 1);
};
