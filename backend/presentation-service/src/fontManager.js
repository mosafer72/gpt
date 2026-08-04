const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

const FONT_DIR = path.join(__dirname,"../fonts");


function installFonts(){

    if(!fs.existsSync(FONT_DIR))
        return;


    const files = fs.readdirSync(FONT_DIR)
        .filter(x=>x.endsWith(".ttf"));


    for(const file of files){

        const src = path.join(FONT_DIR,file);

        try{

            execSync(
                `fc-match "${file}"`,
                {stdio:"ignore"}
            );

        }catch{

            try{

                execSync(
                    `mkdir -p ~/.fonts && cp "${src}" ~/.fonts/ && fc-cache -fv`,
                    {stdio:"ignore"}
                );

            }catch(e){

                console.log(
                    "Font install failed:",
                    file
                );

            }
        }
    }
}


module.exports={
    installFonts,
    font:"Vazirmatn"
};
