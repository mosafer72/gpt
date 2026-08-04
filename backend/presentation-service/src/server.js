const express = require("express");
const cors = require("cors");
const path = require("path");
const fs = require("fs");
const PptxGenJS = require("pptxgenjs");

const modern = require("./themes/modern");

const { installFonts, font } = require("./fontManager");

installFonts();

const app = express();

app.use(cors());

app.use(
    express.json({
        limit: "20mb",
    }),
);

app.post("/generate", async (req, res) => {
    try {
        const data = req.body;

        const ppt = new PptxGenJS();

        ppt.layout = "LAYOUT_WIDE";

        ppt.author = "AI Generator";

        ppt.lang = "fa-IR";

        ppt.theme = {
            headFontFace: font,

            bodyFontFace: font,

            lang: "fa-IR",
        };

        console.log("MODERN BUILD START");

        /*
          ساخت تمام صفحات
        */

        modern(ppt, data);

        console.log("SLIDES CREATED");

        const output =
            data.output ??
            path.join(
                __dirname,
                "../output",
                `presentation_${Date.now()}.pptx`,
            );

        if (!fs.existsSync(path.dirname(output))) {
            fs.mkdirSync(path.dirname(output), {
                recursive: true,
            });
        }

        /*
          ذخیره نهایی
        */

        await ppt.writeFile({
            fileName: output,
        });

        console.log("PPT SAVED:", output);

        res.json({
            status: "success",

            file: output,
        });
    } catch (e) {
        console.error(e);

        res.status(500).json({
            status: "error",

            message: e.message,
        });
    }
});

app.listen(3001, () => console.log("PPT SERVER RUNNING :3001"));
