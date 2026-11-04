<?php
/**
 * Estilo das telas. Fica num arquivo PHP para o projeto ter só PHP;
 * o layout imprime este conteúdo dentro de <style>.
 * Tema: segue o sistema; o botão Claro/Escuro grava um cookie e o layout marca data-theme no <html>.
 */
?>
:root {
  --bg: #f6f4f1;
  --surface: #fff;
  --surface-2: #faf8f5;
  --column: #efebe6;
  --border: #e7e2db;
  --border-strong: #d5cdc2;
  --text: #1e1a16;
  --muted: #6e665d;
  --faint: #9a9188;

  --brand: #d4441f;
  --brand-hover: #b8381a;
  --brand-soft: #fbe8e1;
  --brand-text: #b23716;
  --green: #1f8a54;
  --green-soft: #e1f2e8;
  --amber: #9a5c00;
  --amber-soft: #fbefd8;
  --blue: #2f6fb5;
  --blue-soft: #e3edf8;
  --gray-soft: #eeeae5;

  --shadow-sm: 0 1px 2px rgba(30, 26, 22, .06);
  --shadow-md: 0 6px 24px rgba(30, 26, 22, .10);
  --radius: 10px;
  color-scheme: light;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #151311; --surface: #1d1a17; --surface-2: #232019; --column: #191714;
    --border: #2f2a25; --border-strong: #423b34; --text: #eee8e1; --muted: #a39a90; --faint: #776f66;
    --brand: #e85634; --brand-hover: #f06a4a; --brand-soft: rgba(232, 86, 52, .15); --brand-text: #f28b6e;
    --green: #52c38b; --green-soft: rgba(82, 195, 139, .14); --amber: #e2a541; --amber-soft: rgba(226, 165, 65, .14);
    --blue: #74a9e4; --blue-soft: rgba(116, 169, 228, .14); --gray-soft: #2a2622;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, .3); --shadow-md: 0 8px 30px rgba(0, 0, 0, .45);
    color-scheme: dark;
  }
}
:root[data-theme="dark"] {
  --bg: #151311; --surface: #1d1a17; --surface-2: #232019; --column: #191714;
  --border: #2f2a25; --border-strong: #423b34; --text: #eee8e1; --muted: #a39a90; --faint: #776f66;
  --brand: #e85634; --brand-hover: #f06a4a; --brand-soft: rgba(232, 86, 52, .15); --brand-text: #f28b6e;
  --green: #52c38b; --green-soft: rgba(82, 195, 139, .14); --amber: #e2a541; --amber-soft: rgba(226, 165, 65, .14);
  --blue: #74a9e4; --blue-soft: rgba(116, 169, 228, .14); --gray-soft: #2a2622;
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, .3); --shadow-md: 0 8px 30px rgba(0, 0, 0, .45);
  color-scheme: dark;
}

* { box-sizing: border-box; }
html, body { margin: 0; height: 100%; }
body {
  background: var(--bg);
  color: var(--text);
  font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  font-variant-numeric: tabular-nums;
  -webkit-font-smoothing: antialiased;
}
button, input, select, textarea { font: inherit; color: inherit; }
button { cursor: pointer; }
a { color: var(--brand-text); text-decoration: none; }
a:hover { text-decoration: underline; }
h1 { font-size: 24px; font-weight: 600; letter-spacing: -.01em; margin: 0; }
h2 { font-size: 15px; font-weight: 600; margin: 0; }
.muted { color: var(--muted); }
.small { font-size: 12.5px; }
.right { text-align: right; }
.red { color: var(--brand-text); }
.green { color: var(--green); }
svg.i { width: 16px; height: 16px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

/* estrutura */
.shell { display: grid; grid-template-columns: 228px minmax(0, 1fr); min-height: 100%; }
.side { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; gap: 22px; padding: 20px 14px; border-right: 1px solid var(--border); background: var(--surface); }
.logo { display: flex; align-items: center; gap: 10px; padding: 0 6px; color: var(--text); font-weight: 700; font-size: 16px; line-height: 1.15; white-space: nowrap; }
.logo:hover { text-decoration: none; }
.logo small { display: block; font-weight: 500; font-size: 12px; color: var(--muted); }
.side nav { display: flex; flex-direction: column; gap: 2px; }
.side nav a { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; color: var(--muted); font-weight: 500; }
.side nav a:hover { background: var(--surface-2); color: var(--text); text-decoration: none; }
.side nav a.active { background: var(--brand-soft); color: var(--brand-text); }
.side-foot { margin-top: auto; display: flex; flex-direction: column; gap: 10px; }
.theme-switch { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; padding: 3px; border-radius: 9px; background: var(--column); border: 1px solid var(--border); }
.theme-switch button { display: flex; align-items: center; justify-content: center; gap: 5px; padding: 6px 4px; border: 0; border-radius: 6px; background: none; color: var(--muted); font-size: 12px; font-weight: 500; }
.theme-switch button svg.i { width: 14px; height: 14px; }
.theme-switch button:hover { color: var(--text); }
.theme-switch button.on { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }
.me { display: flex; align-items: center; gap: 10px; padding: 10px 6px 0; border-top: 1px solid var(--border); font-size: 13px; }
.me b { display: block; font-weight: 600; }

.main { min-width: 0; display: flex; flex-direction: column; }
.bar { display: flex; align-items: center; gap: 12px; padding: 14px 28px; border-bottom: 1px solid var(--border); background: var(--surface); position: sticky; top: 0; z-index: 5; }
.search { display: flex; align-items: center; gap: 8px; width: min(460px, 100%); padding: 0 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface-2); color: var(--faint); }
.search:focus-within { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
.search input { border: 0; background: none; padding: 8px 0; box-shadow: none !important; }
.bar .btn.primary { margin-left: auto; }
main { padding: 28px; max-width: 1400px; width: 100%; }
.page-head { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 22px; }
.page-head p { margin: 2px 0 0; color: var(--muted); }
.page-head .tools { margin-left: auto; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

/* botões, campos */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text); font-weight: 500; white-space: nowrap; box-shadow: var(--shadow-sm); }
.btn:hover { border-color: var(--border-strong); text-decoration: none; }
.btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
.btn.primary:hover { background: var(--brand-hover); border-color: var(--brand-hover); }
.btn.small { padding: 4px 10px; font-size: 12.5px; }
.btn.won { color: var(--green); }
.btn.lost { color: var(--brand-text); }
.btn:disabled { opacity: .5; cursor: default; }
.segmented { display: inline-flex; padding: 3px; border-radius: 9px; background: var(--column); border: 1px solid var(--border); }
.segmented a { padding: 5px 12px; border-radius: 6px; color: var(--muted); font-weight: 500; font-size: 13px; }
.segmented a:hover { color: var(--text); text-decoration: none; }
.segmented a.on { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }
.link { border: 0; background: none; padding: 0; color: var(--brand-text); font-weight: 500; }
input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); }
input:focus, select:focus, textarea:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }

/* blocos */
.panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); }
.panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px 0; }
.panel-body { padding: 14px 18px 18px; }
.cols { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 20px; align-items: start; }
.stack { display: grid; gap: 20px; }

.stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-bottom: 20px; }
.stat { padding: 18px; }
.stat .label { display: flex; align-items: center; gap: 8px; color: var(--muted); font-weight: 500; }
.stat .label .chip-icon { width: 28px; height: 28px; border-radius: 7px; display: grid; place-items: center; }
.stat .num { font-size: 28px; font-weight: 600; letter-spacing: -.02em; margin: 10px 0 2px; }
.stat .hint { color: var(--muted); font-size: 13px; }
.progress { height: 8px; background: var(--gray-soft); border-radius: 99px; overflow: hidden; margin-top: 12px; }
.progress i { display: block; height: 100%; border-radius: 99px; background: var(--green); }
.tone-green { background: var(--green-soft); color: var(--green); }
.tone-blue { background: var(--blue-soft); color: var(--blue); }
.tone-red { background: var(--brand-soft); color: var(--brand-text); }
.tone-amber { background: var(--amber-soft); color: var(--amber); }

/* tarefas e contatos */
.list { list-style: none; margin: 0; padding: 0; }
.task { display: grid; grid-template-columns: 32px minmax(0, 1fr) auto auto; gap: 12px; align-items: center; padding: 12px 0; border-top: 1px solid var(--border); }
.task:first-child { border-top: 0; padding-top: 4px; }
.task .ico { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; }
.task .what { min-width: 0; color: var(--text); display: flex; flex-direction: column; }
.task .what:hover { text-decoration: none; }
.task .what b { font-weight: 600; }
.task .what:hover b { color: var(--brand-text); }
.task .value { font-weight: 600; }
.feed li { display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 10px; padding: 10px 0; border-top: 1px solid var(--border); font-size: 13px; }
.feed li:first-child { border-top: 0; padding-top: 2px; }
.empty { color: var(--muted); padding: 10px 0; margin: 0; }

.avatar { width: 28px; height: 28px; border-radius: 50%; display: inline-grid; place-items: center; font-size: 11px; font-weight: 600; color: #fff; flex: none; }
.avatar.sm { width: 22px; height: 22px; font-size: 9.5px; }

/* etiquetas */
.pill { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 99px; font-size: 12px; font-weight: 500; white-space: nowrap; }
.pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.pill.hot { background: var(--brand-soft); color: var(--brand-text); }
.pill.warm { background: var(--amber-soft); color: var(--amber); }
.pill.cold { background: var(--blue-soft); color: var(--blue); }
.pill.stale, .pill.lost { background: var(--gray-soft); color: var(--muted); }
.pill.won { background: var(--green-soft); color: var(--green); }

/* funil */
.board { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(200px, 1fr); gap: 14px; overflow-x: auto; padding-bottom: 10px; align-items: start; }
.col { background: var(--column); border: 1px solid var(--border); border-radius: var(--radius); padding: 10px; min-height: 260px; transition: border-color .15s, background .15s; }
.col.over { border-color: var(--brand); background: var(--brand-soft); }
.col-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; padding: 4px 4px 10px; }
.col-head b { font-weight: 600; }
.col-head .count { font-size: 12px; color: var(--muted); background: var(--surface); border: 1px solid var(--border); border-radius: 99px; padding: 0 7px; margin-left: 6px; }
.col-head .sum { font-size: 12.5px; color: var(--muted); }
.card { display: block; color: var(--text); position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 11px 12px; margin-bottom: 8px; cursor: grab; box-shadow: var(--shadow-sm); transition: border-color .15s, box-shadow .15s; }
.card:hover { border-color: var(--border-strong); box-shadow: var(--shadow-md); text-decoration: none; }
.card .title, .card .client { display: block; }
.card .title { font-weight: 600; line-height: 1.35; }
.card .client { font-size: 12.5px; color: var(--muted); margin-top: 1px; }
.card .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; }
.card .value { font-weight: 600; }
.card .foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border); font-size: 12px; color: var(--muted); }
.card.stale { border-left: 3px solid var(--brand); }
.score { font-weight: 600; color: var(--text); }
/* tabelas */
table { width: 100%; border-collapse: collapse; }
th { text-align: left; font-weight: 500; font-size: 12.5px; color: var(--muted); padding: 10px 14px; border-bottom: 1px solid var(--border); background: var(--surface-2); }
td { padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
tbody tr:last-child td { border-bottom: 0; }
tr.click { cursor: pointer; }
tr.click:hover td { background: var(--surface-2); }
.panel > table th:first-child { border-top-left-radius: var(--radius); }
.panel > table th:last-child { border-top-right-radius: var(--radius); }
.who-cell { display: flex; align-items: center; gap: 10px; }
.bar-track { height: 6px; border-radius: 99px; background: var(--gray-soft); margin-top: 6px; overflow: hidden; }
.bar-track i { display: block; height: 100%; border-radius: 99px; background: var(--brand); }

.score-box summary { cursor: pointer; display: flex; align-items: center; gap: 10px; list-style: none; }
.score-box summary::-webkit-details-marker { display: none; }
.score-box .big { font-size: 22px; font-weight: 600; }
.score-box .toggle { margin-left: auto; color: var(--brand-text); font-size: 13px; font-weight: 500; }
.score-box[open] .toggle::after { content: " (esconder)"; }
.score-list { list-style: none; padding: 0; margin: 12px 0 0; font-size: 13px; }
.score-list li { display: flex; justify-content: space-between; padding: 6px 0; border-top: 1px solid var(--border); }
.form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form label { display: block; font-size: 12.5px; font-weight: 500; color: var(--muted); }
.form label > * { margin-top: 5px; color: var(--text); font-weight: 400; }
.form .full { grid-column: 1 / -1; }
.note-form { display: flex; flex-direction: column; gap: 8px; }
.note-types { display: flex; gap: 6px; flex-wrap: wrap; }
.note-form textarea { min-height: 72px; resize: vertical; }
.note-form .btn { align-self: flex-end; }
.timeline { list-style: none; margin: 0; padding: 0; }
.timeline li { position: relative; padding: 0 0 16px 30px; font-size: 13.5px; }
.timeline li::before { content: ""; position: absolute; left: 10px; top: 24px; bottom: 0; width: 1px; background: var(--border); }
.timeline li:last-child::before { display: none; }
.timeline .dot { position: absolute; left: 0; top: 0; width: 21px; height: 21px; border-radius: 50%; display: grid; place-items: center; background: var(--gray-soft); color: var(--muted); }
.timeline .dot svg.i { width: 12px; height: 12px; }
.timeline .meta { color: var(--muted); font-size: 12px; margin-top: 1px; }
.danger-zone { display: flex; justify-content: flex-end; }

.reasons { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
.buttons { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; }

@media (max-width: 1080px) {
  .cols, .stats { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 760px) {
  .page-head .tools { margin-left: 0; }
  .shell { grid-template-columns: minmax(0, 1fr); }
  .side { position: static; height: auto; flex-direction: row; align-items: center; gap: 12px; padding: 10px 16px; border-right: 0; border-bottom: 1px solid var(--border); overflow-x: auto; }
  .logo small, .me, .theme-switch .label { display: none; }
  .side nav { flex-direction: row; }
  .side nav a { padding: 7px 9px; }
  .side nav a .label { display: none; }
  .side-foot { margin: 0 0 0 auto; }
  .bar { padding: 10px 16px; position: static; }
  .confirm { grid-template-columns: 1fr; }
  .reasons { justify-content: flex-start; }
  main { padding: 18px 16px 40px; }
  .form { grid-template-columns: 1fr; }
  .task { grid-template-columns: 32px minmax(0, 1fr) auto; }
  .task .value { display: none; }
}
@media (prefers-reduced-motion: reduce) {
  .card, .col { transition: none; }
}

/* páginas sem JavaScript */
.alert { display: flex; align-items: center; gap: 8px; padding: 11px 14px; margin-bottom: 18px; border-radius: 10px; font-weight: 600; }
.alert.ok { background: var(--green-soft); color: var(--green); }
.alert.error { background: var(--brand-soft); color: var(--brand-text); }
.confirm { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 18px; margin-bottom: 20px; border-color: var(--border-strong); box-shadow: var(--shadow-md); }
.confirm h2 { margin-bottom: 2px; }
.confirm p { margin: 0; }
.back-link { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px; color: var(--muted); font-size: 13px; font-weight: 500; }
.back-link svg.i { width: 15px; height: 15px; }
.row-link { font-weight: 600; color: var(--text); }
.deal-value { font-size: 20px; font-weight: 600; color: var(--text); margin-right: 6px; }
.btn.danger { color: var(--brand-text); border-color: transparent; box-shadow: none; background: none; align-self: flex-start; }
.btn.ghost { border-color: transparent; box-shadow: none; background: none; color: var(--muted); }
.btn.danger-solid { background: var(--brand); border-color: var(--brand); color: #fff; }
.stack { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
.score-box { cursor: default; }
.form .buttons { margin-top: 4px; }
.note-types { display: flex; gap: 6px; flex-wrap: wrap; }
.note-types label { position: relative; cursor: pointer; }
.note-types input { position: absolute; inset: 0; width: 100%; height: 100%; min-height: 0; margin: 0; padding: 0; opacity: 0; cursor: pointer; }
.note-types span { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border: 1px solid var(--border); border-radius: 99px; background: var(--surface); color: var(--muted); font-size: 12.5px; }
.note-types input:checked + span { border-color: var(--brand); background: var(--brand-soft); color: var(--brand-text); }
.note-types input:focus-visible + span { box-shadow: 0 0 0 3px var(--brand-soft); }
.timeline { list-style: none; margin: 0; padding: 0; }
.timeline li { position: relative; padding: 0 0 16px 30px; font-size: 13.5px; }
.timeline li::before { content: ""; position: absolute; left: 10px; top: 24px; bottom: 0; width: 1px; background: var(--border); }
.timeline li:last-child::before { display: none; }
.timeline .dot { position: absolute; left: 0; top: 0; width: 21px; height: 21px; border-radius: 50%; display: grid; place-items: center; background: var(--gray-soft); color: var(--muted); }
.timeline .dot svg.i { width: 12px; height: 12px; }
.timeline .meta { color: var(--muted); font-size: 12px; margin-top: 1px; }
.parsed { display: flex; flex-wrap: wrap; gap: 10px; margin: 0; }
.parsed div { padding: 8px 12px; border-radius: 9px; background: var(--surface-2); border: 1px solid var(--border); }
.parsed dt { font-size: 12px; color: var(--muted); }
.parsed dd { margin: 0; font-weight: 600; }
.result { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 10px 0; border-top: 1px solid var(--border); }
.result:first-child { border-top: 0; padding-top: 0; }
.result a { color: var(--text); }
.actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 16px; }
.page-head .tools form { margin: 0; }
