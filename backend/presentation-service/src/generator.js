const PptxGenJS = require("pptxgenjs");

async function createPresentation(data) {
    const pptx = new PptxGenJS();

    pptx.layout = "LAYOUT_WIDE";
    pptx.author = "AI Workspace";
    pptx.title = data.title;
    pptx.lang = "fa-IR";

    pptx.theme = {
        headFontFace: "B Nazanin",
        bodyFontFace: "B Nazanin",
        lang: "fa-IR",
    };

    pptx.rtlMode = true;

    data.slides.forEach((d) => {
        let s = pptx.addSlide();

        s.addText(d.title, {
            x: 0.5,
            y: 0.3,
            w: 12,
            h: 0.5,
            fontFace: "B Nazanin",
            fontSize: 28,
            bold: true,
            align: "right",
            rtlMode: true,
        });

        let y = 1.2;

        if (d.content) {
            s.addText(d.content, {
                x: 0.7,
                y,
                w: 11.5,
                h: 0.7,
                fontFace: "B Nazanin",
                fontSize: 20,
                align: "right",
                rtlMode: true,
            });

            y += 0.9;
        }

        if (d.items) {
            d.items.forEach((i) => {
                s.addText("• " + i.heading, {
                    x: 0.7,
                    y,
                    w: 11.5,
                    h: 0.35,
                    fontFace: "B Nazanin",
                    fontSize: 20,
                    bold: true,
                    align: "right",
                    rtlMode: true,
                });

                y += 0.35;

                s.addText("     " + i.description, {
                    x: 0.7,
                    y,
                    w: 11,
                    h: 0.7,
                    fontFace: "B Nazanin",
                    fontSize: 17,
                    align: "right",
                    rtlMode: true,
                });

                y += 0.8;
            });
        }

        if (d.highlight) {
            s.addText(d.highlight, {
                x: 0.7,
                y,
                w: 11,
                h: 0.5,
                fontFace: "B Nazanin",
                fontSize: 16,
                bold: true,
                align: "right",
                rtlMode: true,
            });
        }
    });

    await pptx.writeFile({
        fileName: `output/${Date.now()}.pptx`,
    });
}

module.exports = createPresentation;
