//vars
let systemActive = true;
let loader = null;
let painel = null;
let json = null;
const apiURL = "https://api.traue.com.br/disciplinas/";
const gitURL = "https://github.com/traue/";
const pagesURL = "https://traue.github.io/";
let version = "3.0.0";

$(window).on("pageshow", function () {
  $.getJSON(apiURL, function (data) {
    json = data;
    systemActive = json["active"];
    if (!systemActive) {
      bootbox.alert({
        message:
          "Aguarde instruções do professor!<br><br>Esta aplicação não está ativa! 😄",
        size: "large",
        closeButton: false,
        title: "Aguarde...",
        centerVertical: true,
        callback: function (result) {
          window.location.href = "https://github.com/traue/";
        },
      });
      return;
    }

    startModalChoose();
  }).fail(function () {
    bootbox.alert({
      message:
        "Ops... Houve algum erro no carregamento da API.😓<br><br>Contate o profesor! ",
      size: "large",
      closeButton: false,
      title: "Ops... Erro na API!",
      centerVertical: true,
      callback: function (result) {
        window.location.href = "https://github.com/traue/";
      },
    });
  });
});

function startModalChoose() {
  bootbox.dialog({
    title: "1. Turno",
    centerVertical: true,
    message: "Em qual turno você estuda?",
    closeButton: false,
    buttons: {
      diurno: {
        label: "☀️ Diurno",
        className: "btn-info",
        callback: function () {
          modalDisciplineChoose("diurno");
        },
      },
      noturno: {
        label: "🌒 Noturno",
        className: "btn-primary",
        callback: function () {
          modalDisciplineChoose("noturno");
        },
      },
    },
  }).find('.modal-content').css({'background-color': '#303030', 'color': 'white'});;
}

function modalDisciplineChoose(shift) {
  let options = getDisciplines(json["regulares"][shift]);

  bootbox.prompt({
    title: "2. Selecione a disciplina",
    centerVertical: true,
    closeButton: false,
    inputType: "select",
    inputOptions: options,
    callback: function (result) {
      if (result != null) {
        redirectToGit(result);
      } else {
        startModalChoose();
      }
    },
  }).find('.modal-content').css({'background-color': '#303030', 'color': 'white'});;
}

function getDisciplines(shift) {
  let options = [];
  options.push({ text: "Selecione...", value: "" });
  for (let value in shift) {
    options.push({
      text: shift[value]["description"],
      value: shift[value]["link"],
    });
  }
  return options;
}

function redirectToGit(link) {
  link != "" && link != null
    ? window.location.href = gitURL + link
    : bootbox.alert({
        message: "É preciso selecionar uma disciplina!",
        size: "large",
        closeButton: false,
        title: "🟡 Ops...",
        centerVertical: true,
      });
}

/**
 * Mostra ou oculta a progress bar
 * @param {boolean} loading
 */
function loadingPainel(loading) {
  if (loading) {
    //loader.style.display = 'block';
  } else {
    setTimeout(function () {
      //loader.style.display = 'none';
    }, 1000);
  }
}
