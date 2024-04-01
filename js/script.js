//text translations
var constants = navigator.language == "pt" ? constants_pt : constants_en;

$(window).on("pageshow", function (event) {
  showLoadingAnimation(true);
  var historyTraversal =
    event.persisted ||
    (typeof window.performance != "undefined" &&
      window.performance.getEntriesByType("navigation")[0].type ===
        "back_forward");
  if (historyTraversal) {
    window.location.reload();
  }
  document.getElementById("year").innerHTML = new Date().getFullYear();
  document.getElementById("version").innerHTML = configs.version;
  $.getJSON(configs.apiURL, function (data) {
    json = data;
    systemActive = json["active"];
    if (!systemActive) {
      bootbox.alert({
        message: constants.waitInstructions,
        size: configs.boxSize,
        closeButton: false,
        title: constants.wait,
        centerVertical: true,
        callback: function (result) {
          window.location.href = configs.gitURL;
        },
      });
      return;
    }
    startModalChoose();
  }).fail(function () {
    bootbox.alert({
      message: constants.apiError,
      size: configs.boxSize,
      closeButton: false,
      title: constants.apiErrorTitle,
      centerVertical: true,
      callback: function (result) {
        window.location.href = configs.gitURL;
      },
    });
  });
});

function startModalChoose() {
  showLoadingAnimation(false);
  bootbox
    .dialog({
      title: constants.dayShiftTitle,
      centerVertical: true,
      message: constants.shiftQuestion,
      closeButton: false,
      buttons: getShiftButtons(),
    })
    .find(".modal-content")
    .css({
      "background-color": configs.modalBackColor,
      color: configs.modalFontColor,
    });
}

function getShiftButtons() {
  //ToDo: make this listing more dynamic
  return {
    dayShift: {
      label: constants.diurnal,
      className: "btn-info",
      callback: function () {
        modalDisciplineChoose(configs.diurnalParam);
      },
    },
    nightShift: {
      label: constants.nightShift,
      className: "btn-primary",
      callback: function () {
        modalDisciplineChoose(configs.nightParam);
      },
    },
    dlcShift: {
      label: constants.dlcTitle,
      className: "dlcButton",
      callback: function () {
        modalDisciplineChoose(configs.dlc);
      },
    },
  };
}

function modalDisciplineChoose(shift) {
  showLoadingAnimation(false);
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
      "background-color": configs.modalBackColor,
      color: configs.modalFontColor,
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
  showLoadingAnimation(true);
  link != "" && link != null
    ? (window.location.href = configs.gitURL + link)
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
          "background-color": configs.modalBackColor,
          color: configs.modalFontColor,
        });
}

function showLoadingAnimation(loading) {
  loading ? $("#loadingAnimation").fadeIn() : $("#loadingAnimation").fadeOut();
}
