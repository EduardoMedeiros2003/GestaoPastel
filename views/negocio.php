<?php
/** @var array $data */
$d = $data['deals'][$_GET['id'] ?? ''] ?? null;
if (!$d) {
    echo '<div class="alert error">Negócio não encontrado.</div><a class="btn" href="?p=funil">' . icon('back') . 'Voltar ao funil</a>';
    return;
}
$h = $d['health'];
$contact = $data['contacts'][$d['contactId'] ?? ''] ?? null;
$company = $data['companies'][$d['companyId'] ?? '']['name'] ?? 'Sem cliente';
$events = array_filter($data['activities'], fn($a) => $a['dealId'] === $d['id']);
$askLost = isset($_GET['perder']) && $d['status'] === 'open';
$askDelete = isset($_GET['excluir']);
$sent = ($_POST['action'] ?? '') === 'deal_update' ? $_POST : null; // valores digitados quando deu erro
$val = fn(string $k) => e($sent[$k] ?? $d[$k] ?? '');
$noteType = $_POST['type'] ?? 'call';
$self = '?p=negocio&amp;id=' . e($d['id']);
?>
<div class="page-head">
  <div>
    <a class="back-link" href="?p=funil"><?= icon('back') ?>Funil</a>
    <div class="small muted"><?= e($company) ?><?= $contact ? ' · ' . e($contact['name']) . ', ' . e($contact['role']) : '' ?></div>
    <h1><?= e($d['title']) ?></h1>
    <p><span class="deal-value"><?= money($d['value']) ?></span> <span class="pill <?= $h['state'] ?>"><?= STATE_LABEL[$h['state']] ?></span></p>
  </div>
  <div class="tools">
    <?php if ($d['status'] === 'open'): ?>
      <form method="post"><?= form_head('deal_won', $d['id']) ?><button class="btn won"><?= icon('check') ?>Ganhou</button></form>
      <a class="btn lost" href="<?= $self ?>&amp;perder=1"><?= icon('x') ?>Perdeu</a>
      <?php if ($contact): ?>
        <a class="btn" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/\D/', '', $contact['phone'])) ?>"><?= icon('message') ?>WhatsApp</a>
        <a class="btn" href="mailto:<?= e($contact['email']) ?>"><?= icon('mail') ?>E-mail</a>
      <?php endif ?>
    <?php else: ?>
      <?php if ($d['lostReason']): ?><span class="muted">Motivo: <?= e($d['lostReason']) ?></span><?php endif ?>
      <form method="post"><?= form_head('deal_reopen', $d['id']) ?><button class="btn">Reabrir</button></form>
    <?php endif ?>
  </div>
</div>

<?php if ($askLost): ?>
<div class="panel confirm">
  <div><h2>Por que perdeu?</h2><p class="muted">Isso aparece no relatório de perdas.</p></div>
  <form method="post" class="reasons">
    <?= form_head('deal_lost', $d['id']) ?>
    <?php foreach ($data['settings']['lostReasons'] as $r): ?>
      <button class="btn" name="reason" value="<?= e($r) ?>"><?= e($r) ?></button>
    <?php endforeach ?>
    <a class="btn ghost" href="<?= $self ?>">Cancelar</a>
  </form>
</div>
<?php endif ?>

<?php if ($askDelete): ?>
<div class="panel confirm">
  <div><h2>Excluir negócio?</h2><p class="muted"><?= e($d['title']) ?> e todo o histórico serão apagados.</p></div>
  <form method="post" class="actions" style="margin:0">
    <?= form_head('deal_delete', $d['id']) ?>
    <a class="btn ghost" href="<?= $self ?>">Voltar</a>
    <button class="btn danger-solid"><?= icon('trash') ?>Excluir</button>
  </form>
</div>
<?php endif ?>

<div class="cols">
  <div class="stack">
    <?php if ($h['reasons']): ?>
    <div class="panel">
      <details class="score-box panel-body">
        <summary><span class="big"><?= $h['score'] ?></span><span class="muted">nota de 100</span><span class="toggle">Por quê?</span></summary>
        <ul class="score-list">
          <?php foreach ($h['reasons'] as $r): ?>
            <li><span><?= e($r['label']) ?></span><b class="<?= $r['pts'] < 0 ? 'red' : 'green' ?>"><?= $r['pts'] > 0 ? '+' : '' ?><?= $r['pts'] ?></b></li>
          <?php endforeach ?>
        </ul>
      </details>
    </div>
    <?php endif ?>

    <form method="post" class="panel">
      <?= form_head('deal_update', $d['id']) ?>
      <div class="panel-head"><h2>Dados</h2></div>
      <div class="panel-body form">
        <label class="full">Título<input name="title" value="<?= $val('title') ?>" required maxlength="160"></label>
        <label>Valor (R$)<input name="value" inputmode="decimal" value="<?= e($sent['value'] ?? number_format($d['value'], 0, ',', '')) ?>"></label>
        <label>Etapa<select name="stage">
          <?php foreach ($data['settings']['stages'] as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($sent['stage'] ?? $d['stage']) === $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
          <?php endforeach ?></select></label>
        <label class="full">Próximo passo<input name="nextStep" value="<?= $val('nextStep') ?>" maxlength="200" placeholder="Ex.: levar amostras na terça"></label>
        <label>Data do próximo passo<input name="nextStepDate" type="date" value="<?= $val('nextStepDate') ?>"></label>
        <label>Previsão de fechamento<input name="expectedClose" type="date" value="<?= $val('expectedClose') ?>"></label>
        <label>Responsável<select name="ownerId">
          <?php foreach ($data['users'] as $u): ?>
            <option value="<?= e($u['id']) ?>" <?= ($sent['ownerId'] ?? $d['ownerId']) === $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
          <?php endforeach ?></select></label>
        <label>Alguém lá dentro defende a compra?<select name="champion">
          <?php $champ = $sent['champion'] ?? ($d['champion'] ? '1' : '0'); ?>
          <option value="1" <?= $champ === '1' ? 'selected' : '' ?>>Sim</option>
          <option value="0" <?= $champ === '0' ? 'selected' : '' ?>>Não</option></select></label>
        <div class="full" style="display:flex;justify-content:flex-end"><button class="btn primary">Salvar</button></div>
      </div>
    </form>
  </div>

  <div class="stack">
    <form method="post" class="panel">
      <?= form_head('activity_add', $d['id']) ?>
      <div class="panel-head"><h2>Registrar contato</h2></div>
      <div class="panel-body note-form">
        <div class="note-types">
          <?php foreach (App\Crm::ACTIVITY_TYPES as $k => $label): ?>
            <label><input type="radio" name="type" value="<?= $k ?>" <?= $noteType === $k ? 'checked' : '' ?>><span><?= icon(ACTIVITY_ICON[$k]) ?><?= $label ?></span></label>
          <?php endforeach ?>
        </div>
        <textarea name="text" required maxlength="1000" placeholder="O que foi conversado?"><?= ($_POST['action'] ?? '') === 'activity_add' ? e($_POST['text'] ?? '') : '' ?></textarea>
        <button class="btn primary">Salvar contato</button>
      </div>
    </form>

    <div class="panel">
      <div class="panel-head"><h2>Histórico</h2></div>
      <div class="panel-body">
        <?php if ($events): ?>
        <ul class="timeline">
          <?php foreach ($events as $a): ?>
            <li><span class="dot"><?= icon(ACTIVITY_ICON[$a['type']]) ?></span><?= e($a['text']) ?>
              <div class="meta"><?= ACTIVITY_LABEL[$a['type']] ?> · <?= e(first_name($data['users'][$a['userId'] ?? ''] ?? null)) ?> · <?= time_ago($a['at']) ?></div></li>
          <?php endforeach ?>
        </ul>
        <?php else: ?><p class="empty">Nenhum contato registrado.</p><?php endif ?>
      </div>
    </div>

    <?php if (!$askDelete): ?>
      <a class="btn ghost danger" href="<?= $self ?>&amp;excluir=1"><?= icon('trash') ?>Excluir negócio</a>
    <?php endif ?>
  </div>
</div>
