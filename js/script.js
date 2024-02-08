$(window).on("pageshow", function () {
  document.getElementById("year").innerHTML = new Date().getFullYear();
  document.getElementById("version").innerHTML = constants.version;
  $.getJSON(constants.apiURL, function (data) {
    json = data;
    systemActive = json["active"];
    if (!systemActive) {
      bootbox.alert({
        message: constants.waitInstructions,
        size: constants.boxSize,
        closeButton: false,
        title: constants.wait,
        centerVertical: true,
        callback: function (result) {
          window.location.href = constants.gitURL;
        },
      });
      return;
    }

    startModalChoose();
  }).fail(function () {
    bootbox.alert({
      message: constants.apiError,
      size: constants.boxSize,
      closeButton: false,
      title: constants.apiErrorTitle,
      centerVertical: true,
      callback: function (result) {
        window.location.href = constants.gitURL;
      },
    });
  });
});

function startModalChoose() {
  bootbox
    .dialog({
      title: constants.dayShiftTitle,
      centerVertical: true,
      message: constants.shiftQuestion,
      closeButton: false,
      buttons: {
        diurno: {
          label: constants.diurnal,
          className: "btn-info",
          callback: function () {
            modalDisciplineChoose(constants.diurnalParam);
          },
        },
        noturno: {
          label: constants.nightShift,
          className: "btn-primary",
          callback: function () {
            modalDisciplineChoose(constants.nightParam);
          },
        },
      },
    })
    .find(".modal-content")
    .css({
      "background-color": constants.modalBackColor,
      color: constants.modalFontColor,
    });
}

function modalDisciplineChoose(shift) {
  options = getDisciplines(json["regulares"][shift]);
  bootbox
    .prompt({
      title: constants.disciplineSelectTitle,
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
    })
    .find(".modal-content")
    .css({
      "background-color": constants.modalBackColor,
      color: constants.modalFontColor,
    });
}

function getDisciplines(shift) {
  let options = [];
  options.push({ text: constants.select, value: "" });
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
    ? (window.location.href = constants.gitURL + link)
    : bootbox
        .alert({
          title: constants.ops,
          message: constants.shouldSelectDiscipline,
          closeButton: false,
          centerVertical: true,
          callback: function (result) {
            startModalChoose();
          },
        })
        .find(".modal-content")
        .css({
          "background-color": constants.modalBackColor,
          color: constants.modalFontColor,
        });
}

// /**
//  * Mostra ou oculta a progress bar (toDO)
//  * @param {boolean} loading
//  */
// function loadingPainel(loading) {
//   if (loading) {
//     //loader.style.display = 'block';
//   } else {
//     setTimeout(function () {
//       //loader.style.display = 'none';
//     }, 1000);
//   }
// }
