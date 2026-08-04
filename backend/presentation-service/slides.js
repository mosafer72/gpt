import { theme, applyBase } from "./template.js";

const style = {
    fontFace: theme.font,
    align: "right",
    rtlMode: true,
};

export function cover(ppt, data) {
    let s = ppt.addSlide();

    s.background = { color: theme.bg };

    s.addText(data.title, {
        x: 0.8,
        y: 2,
        w: 11,
        h: 1,
        ...style,
        fontSize: 38,
        bold: true,
        color: theme.text,
    });

    s.addText(data.subtitle || "", {
        x: 0.8,
        y: 3.2,
        w: 10,
        h: 0.5,
        ...style,
        fontSize: 20,
        color: theme.secondary,
    });
}

export function textSlide(ppt, data) {
    let s = ppt.addSlide();

    applyBase(s);

    s.addText(data.title, {
        x: 0.5,
        y: 0.3,
        w: 12,
        h: 0.5,
        ...style,
        fontSize: 28,
        bold: true,
        color: theme.text,
    });

    let y = 1.3;

    data.content.forEach((i) => {
        s.addText("• " + i, {
            x: 1,
            y,
            w: 10,
            h: 0.4,
            ...style,
            fontSize: 18,
            color: theme.text,
            breakLine: false,
        });

        y += 0.6;
    });
}

export function cards(ppt, data) {
    let s = ppt.addSlide();

    applyBase(s);

    s.addText(data.title, {
        x: 0.5,
        y: 0.3,
        w: 12,
        h: 0.5,
        ...style,
        fontSize: 28,
        bold: true,
        color: theme.text,
    });

    let x = 0.5;

    data.items.forEach((c) => {
        s.addShape(ppt.ShapeType.roundRect, {
            x,
            y: 1.5,
            w: 3.8,
            h: 2,
            fill: { color: "1E293B" },
            line: { color: theme.primary },
        });

        s.addText(c.title, {
            x: x + 0.2,
            y: 1.7,
            w: 3,
            h: 0.4,
            ...style,
            fontSize: 20,
            bold: true,
            color: theme.secondary,
        });

        s.addText(c.text, {
            x: x + 0.2,
            y: 2.3,
            w: 3,
            h: 0.8,
            ...style,
            fontSize: 14,
            color: theme.text,
        });

        x += 4.1;
    });
}

export function architecture(ppt, data) {
    let s = ppt.addSlide();

    applyBase(s);

    s.addText(data.title, {
        x: 0.5,
        y: 0.3,
        w: 12,
        h: 0.5,
        ...style,
        fontSize: 28,
        bold: true,
        color: theme.text,
    });

    let y = 1;

    data.items.forEach((i) => {
        s.addShape(ppt.ShapeType.roundRect, {
            x: 4,
            y,
            w: 5,
            h: 0.6,
            fill: { color: theme.primary },
            line: { color: theme.secondary },
        });

        s.addText(i, {
            x: 4,
            y: y + 0.12,
            w: 5,
            h: 0.3,
            fontFace: theme.font,
            fontSize: 18,
            align: "center",
            rtlMode: true,
            color: theme.text,
        });

        y++;
    });
}
