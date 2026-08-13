document.addEventListener("DOMContentLoaded", function () {
  var tipo = document.getElementById("campo-tipo");
  var turnoWrap = document.getElementById("campo-turno-wrap");
  var diaWrap = document.getElementById("campo-dia-wrap");
  var turno = document.getElementById("campo-turno");
  var dia = document.getElementById("campo-dia");

  if (!tipo) return;

  function aplicar() {
    var presencial = tipo.value === "presencial";
    turnoWrap.style.display = presencial ? "" : "none";
    diaWrap.style.display = presencial ? "" : "none";
    if (!presencial) {
      turno.value = "";
      dia.value = "";
    }
  }

  tipo.addEventListener("change", aplicar);
  aplicar();
});
