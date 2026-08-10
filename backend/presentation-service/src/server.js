// مسیر فایل: server.js

const express = require("express");
const bodyParser = require("body-parser");
const pptxgen = require("pptxgenjs");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = 3001;

app.use(bodyParser.json({ limit: "50mb" }));

// توابع کمکی برای استخراج و تمیزسازی تگ‌های HTML/XML
function extractRawTag(html, tag) {
    const result = [];
    if (!html || !tag) {
        return result;
    }

    const regex = new RegExp(
        `<${tag}(?:\\s[^>]*)?>([\\s\\S]*?)<\\/${tag}>`,
        "gi",
    );

    let match;
    while ((match = regex.exec(html)) !== null) {
        result.push(match[1].trim());
    }

    return result;
}

function extractTag(html, tag) {
    return extractRawTag(html, tag).map((item) => cleanText(item));
}

function cleanText(text) {
    if (!text) {
        return "";
    }
    return text
        .replace(/<[^>]+>/g, "")
        .replace(/&nbsp;/gi, " ")
        .replace(/&amp;/gi, "&")
        .replace(/&lt;/gi, "<")
        .replace(/&gt;/gi, ">")
        .replace(/&quot;/gi, '"')
        .replace(/\s+/g, " ")
        .trim();
}

// تابع اصلی پارس و استخراج ساختاریافته صفحات و المان‌ها
function extractPagesFromXML(xml) {
    const pages = [];
    if (!xml) {
        return pages;
    }

    const pageRegex = /<page>([\s\S]*?)<\/page>/gi;
    let match;

    while ((match = pageRegex.exec(xml)) !== null) {
        const pageHtml = match[1];

        const header = extractRawTag(pageHtml, "page-header")[0] ?? "";
        const h1 = extractRawTag(header, "h1")[0] ?? "";
        const h2 = extractRawTag(header, "h2")[0] ?? "";

        const elements = [];

        // ۱. استخراج متن‌های علمی
        extractTag(pageHtml, "scientific-text").forEach((text) => {
            elements.push({
                type: "scientific-text",
                content: { text },
            });
        });

        // ۲. استخراج تگ‌های متنی ساده
        [
            "definition-box",
            "insight-box",
            "formula",
            "formula-explanation",
        ].forEach((tag) => {
            extractTag(pageHtml, tag).forEach((value) => {
                elements.push({
                    type: tag,
                    content: { text: value },
                });
            });
        });

        // ۳. استخراج Concept Card (تفکیک تیتر h3 و متن p با خط جدید)
        extractRawTag(pageHtml, "concept-card").forEach((cardHtml) => {
            const h3 = extractTag(cardHtml, "h3")[0] || "";
            const p = extractTag(cardHtml, "p")[0] || "";
            const combinedText = h3 ? `${h3}\n${p}` : cleanText(cardHtml);
            elements.push({
                type: "concept-card",
                content: { text: combinedText },
            });
        });

        // ۴. استخراج Scientific List (استخراج آیتم‌های <li> با علامت بولت)
        extractRawTag(pageHtml, "scientific-list").forEach((listHtml) => {
            const listItems = extractRawTag(listHtml, "li").map(cleanText);
            if (listItems.length > 0) {
                elements.push({
                    type: "scientific-list",
                    content: { text: "• " + listItems.join("\n• ") },
                });
            }
        });

        // ۵. استخراج Process (مراحل گام به گام با فلش عمودی)
        extractRawTag(pageHtml, "process").forEach((processHtml) => {
            const steps = extractRawTag(processHtml, "process-step").map(
                cleanText,
            );
            if (steps.length > 0) {
                elements.push({
                    type: "process",
                    content: { text: steps.join("\n⬇\n") },
                });
            }
        });

        // ۶. استخراج Pipeline (نودهای فرآیندی با فلش افقی)
        extractRawTag(pageHtml, "pipeline").forEach((pipeHtml) => {
            const nodes = extractRawTag(pipeHtml, "pipeline-node").map(
                cleanText,
            );
            if (nodes.length > 0) {
                elements.push({
                    type: "pipeline",
                    content: { text: nodes.join(" ➔ ") },
                });
            }
        });

        // ۷. استخراج تصاویر علمی
        extractRawTag(pageHtml, "scientific-image").forEach((value) => {
            elements.push({
                type: "scientific-image",
                content: {
                    src: "",
                    description: cleanText(value),
                },
            });
        });

        // ۸. استخراج مقایسه‌ها
        extractRawTag(pageHtml, "comparison").forEach((value) => {
            const left = extractRawTag(value, "comparison-left")[0] ?? "";
            const right = extractRawTag(value, "comparison-right")[0] ?? "";

            elements.push({
                type: "comparison",
                content: {
                    left: cleanText(left),
                    right: cleanText(right),
                },
            });
        });

        pages.push({
            title: cleanText(h1 || "Presentation"),
            subtitle: cleanText(h2),
            elements,
        });
    }

    return pages;
}

// Endpoint اصلی برای ساخت پاورپوینت
app.post("/generate", async (req, res) => {
    console.log("INPUT RECEIVED");

    try {
        const { html, theme: themeName, layout: layoutName } = req.body;

        const source = req.body.preview ?? html ?? "";
        const count = (source.match(/<page>/g) || []).length;
        console.log("HTML PARSED. PAGES COUNT:", count);

        const ppt = new pptxgen();
        try {
            ppt.layout = "LAYOUT_16x9";
        } catch (e) {}

        const requestedLayout = layoutName || themeName || "modern";
        let builder;
        try {
            builder = require(`./themes/${requestedLayout}/index`);
        } catch (err) {
            console.warn(
                `[Theme Warning] Layout "${requestedLayout}" not found. Falling back to "modern".`,
            );
            builder = require("./themes/modern/index");
        }

        const pages = extractPagesFromXML(source);
        const presentationData = {
            pages,
            ending: null,
        };

        const timestamp = Date.now();
        const outputDir = path.join(__dirname, "output");

        // اطمینان از وجود پوشه output
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        // ۱. ذخیره فایل JSON داده‌ها
        const jsonFileName = `data_${timestamp}.json`;
        const jsonOutputPath = path.join(outputDir, jsonFileName);
        fs.writeFileSync(
            jsonOutputPath,
            JSON.stringify(presentationData, null, 4),
            "utf8",
        );
        console.log("JSON SAVED:", jsonOutputPath);

        // ۲. لود مجدد JSON جهت ارسال به builder
        const loadedJsonData = JSON.parse(
            fs.readFileSync(jsonOutputPath, "utf8"),
        );
        console.log(
            "JSON LOADED FOR BUILDER. PAGES:",
            loadedJsonData.pages.length,
        );

        // ۳. رندر اسلایدها با قالب مشخص‌شده
        builder(ppt, loadedJsonData);
        console.log("SLIDES CREATED");

        // ۴. ذخیره فایل PPTX نهایی
        const pptFileName = `presentation_${timestamp}.pptx`;
        const pptOutputPath = path.join(outputDir, pptFileName);

        await ppt.writeFile({
            fileName: pptOutputPath,
        });

        console.log("PPT SAVED:", pptOutputPath);

        // return res.json({
        //     status: "success",
        //     file: pptFileName,
        //     jsonFile: jsonFileName,
        //     path: pptOutputPath,
        //     jsonPath: jsonOutputPath,
        // });
        return res.download(pptOutputPath, pptFileName, (err) => {
            if (err) {
                console.error("DOWNLOAD ERROR:", err);
            }
        });
    } catch (error) {
        console.error("ERROR:", error);
        return res.status(500).json({
            status: "error",
            message: error.message,
        });
    }
});

app.listen(PORT, () => {
    console.log(`PPT SERVER RUNNING :${PORT}`);
});
