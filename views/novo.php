<?php
/** @var array $data */
$sent = ($_POST['action'] ?? '') === 'deal_create' ? $_POST : [];
$v = fn(string $k, string $default = '') => e($sent[$k] ?? $default);
?>
<div class="page-head">
  <div>
    <a class="back-link" href="?p=funil"><?= icon('back') ?>Funil</a>
    <h1>Novo negócio</h1>
    <p>Também dá para criar pela busca, escrevendo a frase: <i>Colégio Aurora 800 pastéis 9,6k sexta</i>.</p>
  </div>
</div>

<form method="post" class="panel" style="max-width:720px">
  <?= form_head('deal_create') ?>
  <div class="panel-body form">
    <label class="full">Cliente<input name="company" list="clientes" required maxlength="120" value="<?= $v('company') ?>" placeholder="Nome do cliente"></label>
    <datalist id="clientes"><?php foreach ($data['companies'] as $c): ?><option value="<?= e($c['name']) ?>"><?php endforeach ?></datalist>
    <label class="full">O que ele quer<input name="title" required maxlength="160" value="<?= $v('title') ?>" placeholder="Ex.: 500 pastéis para festa da firma"></label>
    <label>Valor (R$)<input name="value" inputmode="decimal" required value="<?= $v('value') ?>" placeholder="0"></label>
    <label>Etapa<select name="stage">
      <?php foreach ($data['settings']['stages'] as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($sent['stage'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach ?></select></label>
    <label class="full">Previsão de fechamento<input name="expectedClose" type="date" value="<?= $v('expectedClose') ?>"></label>
    <div class="full buttons">
      <a class="btn ghost" href="?p=funil">Cancelar</a>
      <button class="btn primary">Criar</button>
    </div>
  </div>
</form>
