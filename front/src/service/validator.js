export const validator = (nome, geners, description, year_publication, featured)=>{
    var error = [];
    if(nome.length >255){
        error.push("<li>O campo nome não pode conter mais de 255 caractéres</li><br>");
    }

    if(nome.length <1){
        error.push("<li>O campo nome não pode ser vazio</li><br>");
    }

    if(description.length <1){
        error.push("<li>O campo descrição não pode ser vazio</li><br>");
    }

    if(description.length >255){
        error.push("<li>O campo descrição não pode conter mais de 255 caractéres</li><br>");
    }

    if(year_publication.length <1){
        error.push("<li>O campo ano não pode ser vazio</li><br>");
    }

    if(isNaN(year_publication)){
        error.push("<li>O campo ano não pode conter letras</li><br>");
    }

    if(geners.length <1){
        error.push("<li>Deve ser selecionado ao menos um genero</li>");
    }
     
    return error;
}