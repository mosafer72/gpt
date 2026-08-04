const fs = require("fs");
const PptxGenJS = require("pptxgenjs");

const selectTheme = require("./themeSelector");
const loadTheme = require("./themeLoader");


const jsonPath = process.argv[2];
const outputPath = process.argv[3];


const data = JSON.parse(
    fs.readFileSync(jsonPath, "utf8")
);


const ppt = new PptxGenJS();

ppt.layout = "LAYOUT_WIDE";


const themeName = selectTheme(data);


console.log(
    "SELECTED THEME:",
    themeName
);


const theme = loadTheme(themeName);


theme.cover(
    ppt,
    data
);


data.slides.forEach(slide=>{

    const layout =
        theme.layouts[slide.layout];


    if(layout){

        layout(
            ppt,
            slide
        );

    }

});


theme.ending(
    ppt,
    data
);


ppt.writeFile({
    fileName: outputPath
});
