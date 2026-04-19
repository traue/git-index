/* -------- Data / State -------- */
const TURNOS = [
  { key: "diurno",  nome: "Diurno",  hint: "Aulas pela manhã" },
  { key: "noturno", nome: "Noturno", hint: "Aulas à noite" },
  { key: "ead",     nome: "EaD",     hint: "Ensino a distância, assíncrono" }
];

const DEFAULT_TWEAKS = { palette: "paper", density: "normal", layout: "list" };
const TWEAKS = Object.assign({}, DEFAULT_TWEAKS, JSON.parse(localStorage.getItem("gi_tweaks") || "null"));

const STATE = {
  data: null,          // { diurno: [...], noturno: [...], ead: [...] }
  route: null,         // null | "diurno" | "noturno" | "ead"
  query: "",
  favs: new Set(JSON.parse(localStorage.getItem("gi_favs") || "[]")),
  layout: TWEAKS.layout
};

const app = document.getElementById("app");
const subbarCount = document.getElementById("subbar-count");
const statusChip = document.getElementById("status-chip");

/* -------- Boot -------- */
document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("year").textContent = new Date().getFullYear();
  document.getElementById("version").textContent = configs.version;

  applyTweak("palette", TWEAKS.palette);
  applyTweak("density", TWEAKS.density);
  applyTweak("layout",  TWEAKS.layout);

  wireTweaks();
  wireKeyboard();

  loadAPI();
});

/* -------- API -------- */
function loadAPI() {
  renderLoading();
  setStatus("loading");

  fetch(configs.apiURL)
    .then(res => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then(payload => {
      if (!payload.active) {
        setStatus("off");
        renderInactive();
        return;
      }
      STATE.data = normalize(payload[configs.turnosKey] || {});
      subbarCount.textContent = totalCount() + " disciplinas";
      setStatus("ok");
      renderHome();
    })
    .catch(() => {
      setStatus("error");
      renderError();
    });
}

function normalize(turnos) {
  const out = {};
  for (const t of TURNOS) {
    out[t.key] = (turnos[t.key] || []).map(d => {
      const { nome, curso } = splitNome(d.nome || "");
      return {
        nome,
        curso: curso || null,
        dia: d.dia || null,
        repo: d.repo || ""
      };
    });
  }
  return out;
}

function splitNome(raw) {
  const m = raw.match(/^(.*?)\s*\(([^)]+)\)\s*$/);
  if (m) return { nome: m[1].trim(), curso: m[2].trim() };
  return { nome: raw.trim(), curso: null };
}

function totalCount() {
  return Object.values(STATE.data || {}).reduce((a, b) => a + b.length, 0);
}

function setStatus(kind) {
  if (!statusChip) return;
  const map = {
    loading: { text: "carregando", cls: "dot--off" },
    ok:      { text: "em dia",     cls: "" },
    off:     { text: "inativo",    cls: "dot--off" },
    error:   { text: "erro",       cls: "dot--error" }
  };
  const s = map[kind] || map.ok;
  statusChip.innerHTML = `<span class="dot ${s.cls}"></span>${s.text}`;
}

/* -------- Render: status screens -------- */
function renderLoading() {
  app.innerHTML = `<div class="screen loading">Carregando disciplinas</div>`;
}

function renderError() {
  app.innerHTML = `
    <div class="screen empty">
      Não foi possível carregar as disciplinas.
      <span class="small">Tente novamente em instantes.</span>
      <a href="#" class="retry" id="retry">Tentar novamente</a>
    </div>`;
  document.getElementById("retry").addEventListener("click", e => { e.preventDefault(); loadAPI(); });
}

function renderInactive() {
  app.innerHTML = `
    <div class="screen empty">
      Aguarde instruções do professor.
      <span class="small">Esta aplicação ainda não foi ativada</span>
    </div>`;
}

/* -------- Render: home (turnos) -------- */
function renderHome() {
  const total = totalCount();
  app.innerHTML = `
    <section class="screen">
      <div class="hero">
        <div class="eyebrow">Disciplinas · 26.1</div>
        <h1 class="display">Selecione<br/>seu <em>turno</em>.</h1>
        <p class="lede">Repositórios de ensino do Prof. Thiago G. Traue — materiais, roteiros de aula e exercícios, organizados por disciplina.</p>
      </div>
      <div class="turnos">
        ${TURNOS.map((t, i) => {
          const n = (STATE.data[t.key] || []).length;
          const empty = n === 0;
          return `
            <div class="turno-row ${empty ? "is-empty" : ""}" data-turno="${t.key}">
              <div class="num">0${i + 1}</div>
              <div>
                <div class="name serif">${t.nome}<small>${t.hint} · ${n} disciplina${n === 1 ? "" : "s"}</small></div>
              </div>
              <div class="count">
                <span>${String(n).padStart(2, "0")} / ${String(total).padStart(2, "0")}</span>
                <span class="arrow">→</span>
              </div>
            </div>`;
        }).join("")}
      </div>
    </section>`;

  app.querySelectorAll(".turno-row").forEach(el => {
    if (el.classList.contains("is-empty")) return;
    el.addEventListener("click", () => go(el.dataset.turno));
  });
}

/* -------- Render: disciplinas -------- */
function renderDisc(turno) {
  const list = STATE.data[turno] || [];
  const t = TURNOS.find(x => x.key === turno);
  const q = STATE.query.trim().toLowerCase();
  const filtered = list.filter(d =>
    !q ||
    d.nome.toLowerCase().includes(q) ||
    (d.curso || "").toLowerCase().includes(q) ||
    d.repo.toLowerCase().includes(q)
  );
  filtered.sort((a, b) => (STATE.favs.has(b.repo) - STATE.favs.has(a.repo)));

  const layout = STATE.layout;

  app.innerHTML = `
    <section class="screen">
      <a class="back" href="#" id="back">← Voltar · Turnos</a>

      <div class="d-header">
        <h2 class="serif">Turno <em>${t.nome}</em></h2>
        <div class="counts"><b>${list.length}</b> disciplinas · Semestre 26.1</div>
      </div>

      <div class="toolbar">
        <div class="search">
          <span class="mono" style="color:var(--ink-4);font-size:12px;">/</span>
          <input id="search" placeholder="Buscar disciplina, curso ou repositório…" value="${escapeHtml(STATE.query)}" />
          <span class="kbd">ESC</span>
        </div>
        <div class="view-toggle">
          <button data-layout="list" class="${layout === "list" ? "active" : ""}">Lista</button>
          <button data-layout="grid" class="${layout === "grid" ? "active" : ""}">Grade</button>
        </div>
      </div>

      ${list.length === 0
        ? `<div class="empty">Nenhuma disciplina neste turno.<span class="small">volte e escolha outro</span></div>`
        : filtered.length === 0
          ? `<div class="empty">Nada encontrado para <em>"${escapeHtml(STATE.query)}"</em>.<span class="small">tente outro termo ou limpe a busca</span></div>`
          : (layout === "list" ? renderList(filtered) : renderGrid(filtered))}
    </section>`;

  document.getElementById("back").addEventListener("click", e => { e.preventDefault(); go(null); });

  const searchInput = document.getElementById("search");
  if (searchInput) {
    searchInput.addEventListener("input", e => {
      STATE.query = e.target.value;
      renderDisc(turno);
      const s = document.getElementById("search");
      if (s) { s.focus(); s.setSelectionRange(STATE.query.length, STATE.query.length); }
    });
  }

  app.querySelectorAll(".view-toggle button").forEach(b => {
    b.addEventListener("click", () => {
      applyTweak("layout", b.dataset.layout);
      renderDisc(turno);
    });
  });

  app.querySelectorAll(".fav").forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      e.stopPropagation();
      const repo = btn.dataset.repo;
      if (STATE.favs.has(repo)) STATE.favs.delete(repo); else STATE.favs.add(repo);
      localStorage.setItem("gi_favs", JSON.stringify([...STATE.favs]));
      renderDisc(turno);
    });
  });
}

function renderList(filtered) {
  return `
    <div class="d-list">
      ${filtered.map((d, i) => `
        <a class="d-row" href="${repoHref(d.repo)}" target="_blank" rel="noopener noreferrer" data-repo="${escapeAttr(d.repo)}">
          <div class="idx mono">${String(i + 1).padStart(2, "0")}</div>
          <div class="title">
            ${escapeHtml(d.nome)}
            <span class="sub">${escapeHtml(d.curso || "—")}</span>
          </div>
          <div class="when">
            <span class="label">Encontro</span>
            ${escapeHtml(d.dia || "—")}
          </div>
          <div class="repo">
            <span class="label">Repositório</span>
            ${escapeHtml(d.repo)}
          </div>
          <button class="fav ${STATE.favs.has(d.repo) ? "on" : ""}" data-repo="${escapeAttr(d.repo)}" aria-label="Favoritar">
            ${starSvg(STATE.favs.has(d.repo))}
          </button>
          <div class="arrow serif">→</div>
        </a>
      `).join("")}
    </div>`;
}

function renderGrid(filtered) {
  return `
    <div class="d-grid">
      ${filtered.map((d, i) => `
        <a class="d-card" href="${repoHref(d.repo)}" target="_blank" rel="noopener noreferrer" data-repo="${escapeAttr(d.repo)}">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div class="idx mono">${String(i + 1).padStart(2, "0")} / ${escapeHtml((d.curso || "—").toUpperCase())}</div>
            <button class="fav ${STATE.favs.has(d.repo) ? "on" : ""}" data-repo="${escapeAttr(d.repo)}" aria-label="Favoritar">
              ${starSvg(STATE.favs.has(d.repo))}
            </button>
          </div>
          <div class="title">${escapeHtml(d.nome)}</div>
          <div class="footer">
            <div class="when">
              <span class="label">Encontro</span>
              ${escapeHtml(d.dia || "—")}
            </div>
            <div class="mono" style="font-size:10.5px;color:var(--ink-4);">↗</div>
          </div>
        </a>
      `).join("")}
    </div>`;
}

function starSvg(on) {
  return on
    ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18 22l-6-3.6L6 22l1.5-7.2L2 10l7.1-1.1L12 2z"/></svg>`
    : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3.5l2.7 6.4 6.8 1-5.1 4.6 1.4 6.8L12 18.8 6.2 22.3 7.6 15.5 2.5 10.9l6.8-1L12 3.5z"/></svg>`;
}

function repoHref(repo) {
  return configs.gitURL + encodeURIComponent(repo);
}

function escapeHtml(s) {
  return (s || "").replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
}

function escapeAttr(s) {
  return escapeHtml(s).replace(/'/g, "&#39;");
}

function go(route) {
  STATE.route = route;
  STATE.query = "";
  if (route) renderDisc(route); else renderHome();
  window.scrollTo({ top: 0, behavior: "instant" });
}

/* -------- Keyboard shortcuts -------- */
function wireKeyboard() {
  window.addEventListener("keydown", e => {
    if (e.key === "/" && STATE.route) {
      const s = document.getElementById("search");
      if (s && document.activeElement !== s) { e.preventDefault(); s.focus(); }
    } else if (e.key === "Escape") {
      const s = document.getElementById("search");
      if (STATE.route && STATE.query) { STATE.query = ""; renderDisc(STATE.route); }
      else if (STATE.route) { go(null); }
      if (s) s.blur();
    }
  });
}

/* -------- Tweaks panel -------- */
function applyTweak(k, v) {
  if (k === "palette") document.documentElement.setAttribute("data-palette", v);
  if (k === "density") document.body.setAttribute("data-density", v);
  if (k === "layout")  STATE.layout = v;
  TWEAKS[k] = v;
  localStorage.setItem("gi_tweaks", JSON.stringify(TWEAKS));
  syncTweakPanel();
}

function syncTweakPanel() {
  document.querySelectorAll(".tweaks .sw").forEach(s =>
    s.classList.toggle("on", s.dataset.p === TWEAKS.palette));
  document.querySelectorAll(".tweaks .opts").forEach(g => {
    const key = g.dataset.k;
    g.querySelectorAll("button").forEach(b =>
      b.classList.toggle("on", b.dataset.v === TWEAKS[key]));
  });
}

function wireTweaks() {
  const panel = document.getElementById("tweaks");
  document.getElementById("tweaks-open").addEventListener("click", () => panel.classList.toggle("open"));
  document.getElementById("tweaks-close").addEventListener("click", () => panel.classList.remove("open"));

  document.querySelectorAll(".tweaks .sw").forEach(sw => {
    sw.addEventListener("click", () => applyTweak("palette", sw.dataset.p));
  });
  document.querySelectorAll(".tweaks .opts").forEach(g => {
    const key = g.dataset.k;
    g.querySelectorAll("button").forEach(b => {
      b.addEventListener("click", () => {
        applyTweak(key, b.dataset.v);
        if (key === "layout" && STATE.route) renderDisc(STATE.route);
      });
    });
  });
}

/* Reload on bfcache restore to avoid stale state */
window.addEventListener("pageshow", e => { if (e.persisted) window.location.reload(); });
