module.exports = function(data){

    if(data.theme){
        return data.theme;
    }


    if(data.type==="academic"){
        return "academic";
    }


    if(data.type==="startup"){
        return "startup";
    }


    return "modern";

};
