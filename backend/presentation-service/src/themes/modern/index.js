const cover = require("./cover");
const ending = require("./ending");

const Title = require("../../components/title");
const Subtitle = require("../../components/subtitle");
const Card = require("../../components/card");
const Bullet = require("../../components/bullet");
const Image = require("../../components/image");
const Highlight = require("../../components/highlight");
const Badge = require("../../components/badge");
const Icon = require("../../components/icon");
const Line = require("../../components/line");
const CompareBox = require("../../components/compare_box");
const text = require("../../components/text");

const ELEMENT_GAP = 0.25;

function renderElement(slide, element, y) {
    switch (element.type) {
        case "title":
            Title(slide, element.text ?? "", y);
            return 0.45;

        case "subtitle":
            Subtitle(slide, element.text ?? "", y);
            return 0.35;

        case "card":
            Card(slide, element, y);
            return 1;

        case "bullet":
            Bullet(slide, element.text ?? "", y);

            let childY = y + 0.35;

            (element.children ?? []).forEach((child) => {
                text(slide, "         " + child, childY);

                childY += 0.35;
            });

            return 0.45 + (element.children?.length ?? 0) * 0.35;

        case "image":
            Image(slide, element, y);
            return 2;

        case "highlight":
            Highlight(slide, element.text ?? "", y);
            return 0.8;

        case "badge":
            Badge(slide, element.text ?? "", y);
            return 0.3;

        case "icon":
            Icon(slide, element, y);
            return 0.5;

        case "line":
            Line(slide, 1, y, 11);
            return 0.1;

        /*
          صفحه مقایسه
          کل کامپوننت یکجا ساخته می‌شود
        */
        case "compare_box":
            CompareBox(
                slide,
                element.left ?? {
                    title: "",
                    items: [],
                },
                element.right ?? {
                    title: "",
                    items: [],
                },
            );

            return 4;

        case "timeline_item":
            Bullet(slide, element.text ?? "", y);

            return 0.5;

        case "text":
            Subtitle(slide, element.text ?? "", y);

            return 0.4;

        default:
            console.log("UNKNOWN ELEMENT", element);

            return 0;
    }
}

function renderPage(ppt, page) {
    /*
       پایان
    */

    if (page.template === "ending") {
        ending(ppt, page);

        return;
    }

    /*
       کاور
    */

    if (page.template === "cover") {
        cover(ppt, page);

        return;
    }

    const slide = ppt.addSlide();

    let y = 0.7;

    if (page.title) {
        Title(slide, page.title, y);

        y += 0.55;
    }

    if (page.subtitle) {
        Subtitle(slide, page.subtitle, y);

        y += 0.45;
    }

    /*
       خط زیر عنوان
    */

    Line(slide, 1, y, 11);

    y += 0.3;

    const elements = page.elements ?? [];

    elements.forEach((element) => {
        y += ELEMENT_GAP;

        const height = renderElement(slide, element, y);

        y += height;
    });
}

module.exports = function modern(ppt, data) {
    let pages = data.pages ?? [];

    if (!Array.isArray(pages)) {
        pages = Object.values(pages);
    }

    pages.forEach((page) => {
        renderPage(ppt, page);
    });

    if (data.ending) {
        ending(ppt, data);
    }
};
