const modern = require("./themes/modern");

module.exports = function (name = "modern") {
    const themes = {
        modern,

    };

    return themes[name] || themes.modern;
};
