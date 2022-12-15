//vars
let sistemaAtivo = true;
let turno = null;
let tipoDisciplina = null
let selectDiscTipo = null;
let selectDisciplinas = null;
let selectTurno = null;
let loader = null;
let json = null;
const apiURL = 'https://api.traue.com.br/disciplinas/';
const gitURL = 'https://github.com/traue/';
const pagesURL = 'https://traue.github.io/';
let version = '2.1.2';

window.addEventListener( "pageshow", function ( event ) {
    var historyTraversal = event.persisted || 
                           ( typeof window.performance != "undefined" && 
                                window.performance.navigation.type === 2 );
    if ( historyTraversal ) {
      // Handle page restore.
      window.location.reload();
    }
  });

/**
 * Prepara os selects no carregamento da página
 */ 
 $(window).on('pageshow', function(){
    loader = document.getElementById('loader');
    loadingPainel(true);
    document.getElementById("year").innerHTML = new Date().getFullYear();
    document.getElementById("version").innerHTML = version;
    selectDiscTipo = document.getElementById('discTipo');
    selectDisciplinas = document.getElementById('disciplinas');
    selectTurno = document.getElementById('turno');
    selectDiscTipo.selectedIndex = selectDisciplinas.selectedIndex = selectTurno.selectedIndex = 0;
    selectDiscTipo.disabled = selectDisciplinas.disabled = true;
    $.getJSON(apiURL, function(data){
        json = data;
        sistemaAtivo = json['ativo'];
        if(!sistemaAtivo) {
            bootbox.alert({
                message: 'Aguarde instruções do professor!<br><br>Esta aplicação não está ativa! 😄',
                size: 'large',
                closeButton: false,
                title: 'Aguarde...',
                centerVertical: true,
                callback: function(result){ 
                    window.location.href = 'https://github.com/traue/';
                }
            });
        }
    })
    .fail(function() {
        bootbox.alert({
            message: 'Ops... Houve algum erro no carregamento da API.😓<br><br>Contate o profesor! ',
            size: 'large',
            closeButton: false,
            title: 'Ops... Erro na API!',
            centerVertical: true,
            callback: function(result){ 
                window.location.href = 'https://github.com/traue/';
            }
        });
    });

    loadingPainel(false);
    // var perfEntries = performance.getEntriesByType('navigation');
    // if (perfEntries[0].type === 'back_forward') {
    //     location.reload();
    //     //loadDiscs();
    // }
});

/**
 * Limpa um select
 */
function removeOptions(selectElement) {
    var i, L = selectElement.options.length - 1;
    for(i = L; i >= 0; i--) {
        selectElement.remove(i);
    }
}

/**
 * Mostra ou oculta a progress bar
 * @param {boolean} loading 
 */
function loadingPainel(loading) {
    if(loading) {
        loader.style.display = 'block';
    } else {
        setTimeout(function(){
            loader.style.display = 'none';
        },1000);
    }
}

/**
 * Controla o select de turno 
 */ 
function turnoSelect() {
    turno = selectTurno.value;
    if (turno == '') {
        turno = null;
        tipoDisciplina = null;
        selectDiscTipo.selectedIndex = 0;
    }
    selectDiscTipo.disabled = (turno == 'diurno' || turno == 'noturno') ? false : true;
    loadDiscs();
}

/**
 * Controla o select de tipo de disciplina
 */
function discTipoSelect() {
    tipoDisciplina = document.getElementById('discTipo').value;
    if (tipoDisciplina == '') {
        tipoDisciplina = null;
    }
    loadDiscs();
}

/**
 * Carrega a lista de disciplinas no select
 */
function loadDiscs() {
    if(turno == null || tipoDisciplina == null) {
        selectDisciplinas.disabled = true;
        return;
    } else {
        removeOptions(selectDisciplinas);
        var optAux = document.createElement('option');
        optAux.text = 'Selecione a disciplina';
        optAux.value = '';
        selectDisciplinas.appendChild(optAux);
        
        for(var disc in json[tipoDisciplina][turno]) {
            var option = document.createElement('option');
            option.text = json[tipoDisciplina][turno][disc]['title'];
            option.value = json[tipoDisciplina][turno][disc]['link'];
            selectDisciplinas.appendChild(option);
        }
        selectDisciplinas.disabled = false;
    }
}

/**
 * Redireciona para o repo da disciplina
 */
function discSelect() {
    loadingPainel(true);

    var link = document.getElementById('disciplinas').value;
    (link != '')
        ? window.location.href = (tipoDisciplina == 'projetos')
            ? pagesURL + link
            : gitURL + link
        : bootbox.alert({
            message: 'É preciso selecionar uma disciplina!',
            size: 'large',
            closeButton: false,
            title: '🟡 Ops...',
            centerVertical: true
        });

    loadingPainel(false);
}