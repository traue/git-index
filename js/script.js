// Language detection
var constants = navigator.language.includes("pt") ? constants_pt : constants_en;
var apiData = null;

document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("year").textContent = new Date().getFullYear();
  document.getElementById("version").textContent = configs.version;
  loadAPI();
});

// Reload on back/forward navigation to avoid stale state
window.addEventListener("pageshow", function (e) {
  if (e.persisted) window.location.reload();
});

function loadAPI() {
  showState("state-loading");

  fetch(configs.apiURL)
    .then(function (res) {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then(function (data) {
      apiData = data;
      if (!data.active) {
        showState("state-inactive");
        return;
      }
      showState("step-shift");
    })
    .catch(function () {
      showState("state-error");
    });
}

function showState(id) {
  var states = document.querySelectorAll(".state");
  for (var i = 0; i < states.length; i++) {
    states[i].classList.remove("active");
  }
  var el = document.getElementById(id);
  if (el) el.classList.add("active");
}

// Shift selection
document.addEventListener("click", function (e) {
  var btn = e.target.closest(".btn-shift");
  if (!btn) return;
  showDisciplines(btn.dataset.shift);
});

// Back button
document.addEventListener("click", function (e) {
  if (e.target.id === "btn-back") showState("step-shift");
});

function showDisciplines(shift) {
  var disciplines = apiData[configs.turnosKey][shift];
  var list = document.getElementById("discipline-list");
  list.innerHTML = "";

  if (!disciplines || disciplines.length === 0) {
    var p = document.createElement("p");
    p.className = "state-text";
    p.textContent = "Nenhuma disciplina encontrada.";
    list.appendChild(p);
    showState("step-disciplines");
    return;
  }

  for (var i = 0; i < disciplines.length; i++) {
    var disc = disciplines[i];
    var a = document.createElement("a");
    a.className = "discipline-item";
    a.href = configs.gitURL + disc.repo;
    a.style.animationDelay = (i * 0.06) + "s";

    if (disc.dia) {
      var badge = document.createElement("span");
      badge.className = "discipline-day";
      badge.textContent = disc.dia;
      a.appendChild(badge);
    }

    var nome = document.createElement("span");
    nome.className = "discipline-name";
    nome.textContent = disc.nome;
    a.appendChild(nome);

    list.appendChild(a);
  }

  showState("step-disciplines");
}
