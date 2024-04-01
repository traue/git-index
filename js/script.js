$.getScript('js/constants_pt.js', function() {
  alert('Load was performed.');
  
});


$(window).on("pageshow", function (event) {
  showLoadingAnimation(true);
  var historyTraversal =
    event.persisted ||
    (typeof window.performance != "undefined" &&
      window.performance.getEntriesByType("navigation")[0].type ===
        "back_forward");
  if (historyTraversal) {
    // Handle page restore.
    window.location.reload();
  }
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
      "background-color": constants.modalBackColor,
      color: constants.modalFontColor,
    });
}

function getShiftButtons() {
  //ToDo: make this listing dynamic
  return {
    dayShift: {
      label: constants.diurnal,
      className: "btn-info",
      callback: function () {
        modalDisciplineChoose(constants.diurnalParam);
      },
    },
    nightShift: {
      label: constants.nightShift,
      className: "btn-primary",
      callback: function () {
        modalDisciplineChoose(constants.nightParam);
      },
    },
    dlcShift: {
      label: constants.dlcTitle,
      className: "dlcButton",
      callback: function () {
        modalDisciplineChoose(constants.dlc);
      },
    },
  };
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
  showLoadingAnimation(true);
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

function showLoadingAnimation(loading) {
  loading ? $("#loadingAnimation").fadeIn() : $("#loadingAnimation").fadeOut();
}
