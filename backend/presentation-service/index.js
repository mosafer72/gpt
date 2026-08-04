import pptxgen from "pptxgenjs";

import { cover, textSlide, cards, architecture } from "./slides.js";

const ppt = new pptxgen();

ppt.layout = "LAYOUT_WIDE";

ppt.author = "AI Generator";

ppt.subject = "Scientific Presentation";

ppt.lang = "fa-IR";

const data = {
    title: "مدل‌های زبانی بزرگ در هوش مصنوعی",

    subtitle: "Scientific Overview",

    slides: [
        {
            type: "text",
            title: "مقدمه",
            content: [
                "مدل‌های زبانی بزرگ نسل جدید هوش مصنوعی هستند.",
                "این مدل‌ها بر پایه Transformer ساخته شده‌اند.",
                "توانایی پردازش زبان طبیعی را دارند.",
            ],
        },

        {
            type: "cards",
            title: "اجزای اصلی",
            items: [
                {
                    title: "Transformer",
                    text: "Architecture",
                },
                {
                    title: "Embedding",
                    text: "Representation",
                },
                {
                    title: "Attention",
                    text: "Context Learning",
                },
            ],
        },

        {
            type: "architecture",
            title: "Pipeline مدل",
            items: ["Input", "Embedding", "Transformer", "Prediction"],
        },
    ],
};

data.slides.forEach((slide) => {
    switch (slide.type) {
        case "text":
            textSlide(ppt, slide);
            break;

        case "cards":
            cards(ppt, slide);
            break;

        case "architecture":
            architecture(ppt, slide);
            break;
    }
});

cover(ppt, {
    title: data.title,
    subtitle: data.subtitle,
});

let name = `output/presentation_${Date.now()}.pptx`;

await ppt.writeFile({
    fileName: name,
});

console.log(name);
