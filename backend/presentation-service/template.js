export const theme = {
    bg:"#0F172A",
    primary:"#2563EB",
    secondary:"#38BDF8",
    text:"#F8FAFC",
    muted:"#CBD5E1",
    font:"Vazirmatn"
};

export function applyBase(slide){
    slide.background={color:theme.bg};

    slide.addText(
        "AI Presentation Generator",
        {
            x:0.4,
            y:7,
            w:12,
            h:0.2,
            fontFace:theme.font,
            fontSize:8,
            color:theme.muted,
            align:"right",
            rtlMode:true
        }
    );
}
