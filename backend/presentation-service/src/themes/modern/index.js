module.exports = function buildPresentation(pptx, presentationData) {
    // ۱. تنظیمات اولیه پاورپوینت (راست‌چین برای زبان فارسی)
    pptx.rtlMode = true;

    // ۲. پالت رنگی آکادمیک و رسمی (Academic & Scholarly Palette)
    const colors = {
        primary: "0F172A",       // سرمه‌ای بسیار تیره و رسمی برای عناوین
        accent: "1E40AF",        // آبی کلاسیک آکادمیک برای خطوط و تأکیدات
        text: "334155",          // خاکستری تیره استاندارد برای خوانایی بالا
        subText: "64748B",       // خاکستری ملایم برای متن‌های فرعی

        // رنگ‌های تخصصی باکس‌های آکادمیک
        defBoxBg: "F8FAFC",
        defBoxLine: "CBD5E1",

        conceptBg: "F1F5F9",
        conceptLine: "94A3B8",

        insightBg: "F8FAFC",
        insightLine: "64748B",
        insightText: "0F172A",

        processBg: "F8FAFC",
        processLine: "CBD5E1",

        codeBg: "0B0F19",
        codeText: "38BDF8",

        formulaBg: "F8FAFC",
        formulaLine: "CBD5E1",
        formulaText: "1E3A8A"
    };

    const defaultFont = "Vazirmatn";
    const monoFont = "Consolas";

    // ۳. استایل‌های حرفه‌ای و بهینه‌شده عناوین و متن‌ها
    const styles = {
        title: {
            x: 0.6, y: 0.3, w: "88%", h: 0.5,
            fontSize: 20, bold: true, color: colors.primary,
            fontFace: defaultFont, align: "right", rtlMode: true
        },
        line: {
            x: 0.6, y: 0.85, w: "88%", h: 0,
            line: { color: colors.accent, width: 2 }
        },
        baseText: {
            w: "88%", x: 0.6, fontSize: 11.5,
            fontFace: defaultFont, color: colors.text,
            align: "right", rtlMode: true,
            margin: [4, 8, 4, 8]
        }
    };

    // ۴. بررسی صحت داده‌ها
    if (!presentationData || !presentationData.pages || !Array.isArray(presentationData.pages)) {
        console.warn("[Theme: Modern Academic] هیچ صفحه‌ای برای ساخت یافت نشد.");
        return;
    }

    // ۵. تابع کمکی برای محاسبه ارتفاع داینامیک متن بر اساس تعداد خطوط و کاراکترها
    const calculateDynamicHeight = (text, minH = 0.4, lineH = 0.24) => {
        if (!text) return minH;
        let linesCount = text.split('\n').length;
        let charLengthEstimate = Math.ceil(text.length / 85);
        let effectiveLines = Math.max(linesCount, charLengthEstimate);
        return Math.max(minH, effectiveLines * lineH + 0.15);
    };

    // فاصله ثابتی معادل ۱۵ پیکسل (تقریباً 0.2 اینچ)
    const fixedGap = 0.2;

    // ۶. رندر اسلایدها با کنترل دقیق ارتفاع و فواصل
    presentationData.pages.forEach((page, pageIndex) => {
        let slide = pptx.addSlide();
        slide.background = { color: "FFFFFF" };

        const isFirstPage = pageIndex === 0;

        // --- رندر عنوان صفحه و خط جداکننده ---
        slide.addText(page.title || "", styles.title);
        slide.addShape(pptx.ShapeType.line, styles.line);

        let currentY = 1.05; // شروع محتوا درست زیر خط هدر

        if (page.elements && Array.isArray(page.elements)) {
            page.elements.forEach((element) => {
                if (!element || !element.content) return;

                let textContent = element.content.text || "";
                let calculatedHeight = calculateDynamicHeight(textContent);

                switch (element.type) {
                    case "scientific-text":
                        slide.addText(textContent, {
                            ...styles.baseText,
                            y: currentY,
                            h: calculatedHeight
                        });
                        currentY += calculatedHeight + fixedGap;
                        break;

                    case "definition-box":
                        let defText = `تعریف تخصصی:\n${textContent}`;
                        let defHeight = calculateDynamicHeight(defText);
                        slide.addText(defText, {
                            ...styles.baseText,
                            y: currentY,
                            h: defHeight,
                            fill: { color: colors.defBoxBg },
                            line: { color: colors.defBoxLine, width: 1 },
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += defHeight + fixedGap;
                        break;

                    case "concept-card":
                        let conceptHeight = calculateDynamicHeight(textContent);
                        slide.addText(textContent, {
                            ...styles.baseText,
                            y: currentY,
                            h: conceptHeight,
                            fill: { color: colors.conceptBg },
                            line: { color: colors.conceptLine, width: 1 },
                            shape: pptx.ShapeType.roundRect,
                            bold: !isFirstPage
                        });
                        currentY += conceptHeight + fixedGap;
                        break;

                    case "insight-box":
                        let insightText = `یافته و نتیجه‌گیری کلیدی:\n${textContent}`;
                        let insightHeight = calculateDynamicHeight(insightText);
                        slide.addText(insightText, {
                            ...styles.baseText,
                            y: currentY,
                            h: insightHeight,
                            fill: { color: colors.insightBg },
                            line: { color: colors.insightLine, width: 1.5 },
                            color: colors.insightText,
                            bold: true,
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += insightHeight + fixedGap;
                        break;

                    case "scientific-list":
                        let listHeight = calculateDynamicHeight(textContent, 0.5, 0.28);
                        slide.addText(textContent, {
                            ...styles.baseText,
                            y: currentY,
                            h: listHeight,
                            lineSpacing: 14
                        });
                        currentY += listHeight + fixedGap;
                        break;

                    case "process":
                        let processText = `روند اجرای فرآیند:\n${textContent}`;
                        let processHeight = calculateDynamicHeight(processText);
                        slide.addText(processText, {
                            ...styles.baseText,
                            y: currentY,
                            h: processHeight,
                            fill: { color: colors.processBg },
                            line: { color: colors.processLine, width: 1 },
                            align: "center",
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += processHeight + fixedGap;
                        break;

                    case "pipeline":
                        let pipeHeight = calculateDynamicHeight(textContent, 0.4, 0.22);
                        slide.addText(`معماری پایپ‌لاین: ${textContent}`, {
                            ...styles.baseText,
                            y: currentY,
                            h: pipeHeight,
                            fill: { color: colors.primary },
                            color: "FFFFFF",
                            align: "center",
                            bold: true,
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += pipeHeight + fixedGap;
                        break;

                    case "algorithm":
                        let algoText = `شرح مراحل الگوریتم:\n${textContent}`;
                        let algoHeight = calculateDynamicHeight(algoText);
                        slide.addText(algoText, {
                            ...styles.baseText,
                            y: currentY,
                            h: algoHeight,
                            fill: { color: "F8FAFC" },
                            line: { color: "CBD5E1", width: 1 },
                            fontFace: monoFont,
                            fontSize: 10,
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += algoHeight + fixedGap;
                        break;

                    case "formula":
                        let formulaHeight = calculateDynamicHeight(textContent, 0.5, 0.25);
                        slide.addText(textContent, {
                            x: 1.5, y: currentY, w: "70%", h: formulaHeight,
                            fontFace: monoFont,
                            fontSize: 11.5,
                            bold: true,
                            color: colors.formulaText,
                            fill: { color: colors.formulaBg },
                            line: { color: colors.formulaLine, width: 1 },
                            align: "center",
                            rtlMode: false,
                            margin: [4, 6, 4, 6],
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += formulaHeight + fixedGap;
                        break;

                    case "formula-explanation":
                        let expHeight = calculateDynamicHeight(textContent);
                        slide.addText(`تحلیل پارامترها و روابط: ${textContent}`, {
                            ...styles.baseText,
                            y: currentY,
                            h: expHeight,
                            fontSize: 11,
                            color: colors.subText,
                            italic: true,
                            align: "right"
                        });
                        currentY += expHeight + fixedGap;
                        break;

                    case "code-block":
                        let codeHeight = calculateDynamicHeight(textContent, 0.6, 0.2);
                        slide.addText(textContent, {
                            x: 0.6, y: currentY, w: "88%", h: codeHeight,
                            fontFace: monoFont, fontSize: 9.5, color: colors.codeText,
                            fill: { color: colors.codeBg },
                            align: "left", rtlMode: false,
                            margin: [6, 6, 6, 6],
                            shape: pptx.ShapeType.roundRect
                        });
                        currentY += codeHeight + fixedGap;
                        break;

                    case "code-analysis":
                        let codeAnText = `تحلیل ساختار کد:\n${textContent}`;
                        let codeAnHeight = calculateDynamicHeight(codeAnText);
                        slide.addText(codeAnText, {
                            ...styles.baseText,
                            y: currentY,
                            h: codeAnHeight,
                            fontSize: 11,
                            color: colors.text,
                            fill: { color: "F8FAFC" },
                            line: { color: "E2E8F0", width: 1 }
                        });
                        currentY += codeAnHeight + fixedGap;
                        break;

                    case "comparison":
                        let tableHeight = 0.8;
                        let leftText = element.content.left || "";
                        let rightText = element.content.right || "";

                        let tableData = [
                            [
                                { text: "رویکرد پیشنهادی", options: { align: "center", bold: true, fill: "1E40AF", color: "FFFFFF", fontFace: defaultFont, fontSize: 10.5 } },
                                { text: "رویکرد پایه / موجود", options: { align: "center", bold: true, fill: "475569", color: "FFFFFF", fontFace: defaultFont, fontSize: 10.5 } }
                            ],
                            [
                                { text: rightText, options: { align: "center", fill: "F8FAFC", color: colors.text, fontFace: defaultFont, fontSize: 10 } },
                                { text: leftText, options: { align: "center", fill: "F8FAFC", color: colors.text, fontFace: defaultFont, fontSize: 10 } }
                            ]
                        ];

                        slide.addTable(tableData, {
                            x: 0.6, y: currentY, w: "88%", h: tableHeight,
                            border: { type: "solid", color: "CBD5E1", pt: 1 },
                            rtlMode: true,
                            margin: 4
                        });
                        currentY += tableHeight + fixedGap;
                        break;

                    case "scientific-image":
                        let imgHeight = 1.7;
                        if (element.content.src && element.content.src.trim() !== "") {
                            slide.addImage({
                                path: element.content.src,
                                x: 2.5, y: currentY, w: 4.8, h: imgHeight,
                                sizing: { type: "contain", w: 4.8, h: imgHeight }
                            });
                        } else {
                            slide.addText(`شماتیک علمی: ${element.content.description || "نمودار معماری و ساختاری"}`, {
                                x: 2.0, y: currentY, w: 5.8, h: imgHeight,
                                fill: { color: "F8FAFC" },
                                line: { color: "CBD5E1", width: 1, dashType: "dash" },
                                align: "center",
                                color: colors.subText,
                                fontFace: defaultFont,
                                fontSize: 11,
                                shape: pptx.ShapeType.roundRect
                            });
                        }
                        currentY += imgHeight + fixedGap;
                        break;

                    default:
                        console.log(`[Theme: Modern Academic] المان نامعتبر: ${element.type}`);
                        break;
                }
            });
        }
    });
};
