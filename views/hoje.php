<?php
/** @var array $data @var array $me */
$q = $data['quarter'];
$pct = min(100, (int) round($q['won'] / $q['goal'] * 100));
$hour = (int) date('G');
$hello = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
$client = fn(array $deal) => $data['companies'][$deal['companyId'] ?? '']['name'] ?? 'Sem cliente';
?>
<div class="page-head">
  <div><h1><?= $hello ?>, <?= e(first_name($me)) ?></h1><p><?= today_long() ?></p></div>
</div>

<div class="stats">
  <div class="panel stat">
    <div class="label"><span class="chip-icon tone-green"><?= icon('wallet') ?></span>Vendido no <?= e($q['label']) ?></div>
    <div class="num"><?= money($q['won']) ?></div>
    <div class="hint"><?= $pct ?>% da meta de <?= money($q['goal']) ?> · faltam <?= $q['daysLeft'] ?> dias</div>
    <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
  </div>
  <div class="panel stat">
    <div class="label"><span class="chip-icon tone-blue"><?= icon('columns') ?></span>Em negociação</div>
    <div class="num"><?= money($q['open']) ?></div>
    <div class="hint"><?= $q['openCount'] ?> negócios abertos</div>
  </div>
  <div class="panel stat">
    <div class="label"><span class="chip-icon tone-red"><?= icon('alert') ?></span>Parados</div>
    <div class="num"><?= $q['staleCount'] ?></div>
    <div class="hint"><?= money($q['stale']) ?> sem contato · <a href="?p=funil&amp;f=parados">ver no funil</a></div>
  </div>
</div>

<div class="cols">
  <div class="panel">
    <div class="panel-head"><h2>Para fazer hoje</h2><span class="small muted"><?= count($data['tasks']) ?> tarefas</span></div>
    <div class="panel-body">
      <?php if ($data['tasks']): ?>
      <ul class="list">
        <?php foreach ($data['tasks'] as $t):
            $deal = $data['deals'][$t['dealId']];
            [$ico, $tone] = TASK_LOOK[$t['kind']];
            // "Combinar próximo passo" e "atualizar data" pedem uma decisão: o botão abre o negócio.
            $needsDecision = in_array($t['kind'], ['plan', 'date'], true); ?>
        <li class="task">
          <span class="ico <?= $tone ?>"><?= icon($ico) ?></span>
          <a class="what" href="?p=negocio&amp;id=<?= e($deal['id']) ?>">
            <b><?= e($t['title']) ?></b>
            <span class="small muted"><?= e($deal['title']) ?> · <?= e($client($deal)) ?> · <?= e($t['why']) ?></span>
          </a>
          <span class="value"><?= money($t['value']) ?></span>
          <?php if ($needsDecision): ?>
            <a class="btn small" href="?p=negocio&amp;id=<?= e($deal['id']) ?>">Abrir</a>
          <?php else: ?>
            <form method="post">
              <?= form_head('task_done', $deal['id']) ?>
              <input type="hidden" name="title" value="<?= e($t['title']) ?>">
              <input type="hidden" name="channel" value="<?= e($t['channel']) ?>">
              <button class="btn small"><?= icon('check') ?>Feito</button>
            </form>
          <?php endif ?>
        </li>
        <?php endforeach ?>
      </ul>
      <?php else: ?><p class="empty">Nada pendente.</p><?php endif ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Últimos contatos</h2></div>
    <div class="panel-body">
      <ul class="list feed">
        <?php foreach (array_slice($data['activities'], 0, 8) as $a):
            $user = $data['users'][$a['userId'] ?? ''] ?? null;
            $deal = $data['deals'][$a['dealId']] ?? null; ?>
        <li>
          <?= avatar($user) ?>
          <div>
            <b><?= e(first_name($user)) ?></b> <span class="muted">· <?= ACTIVITY_LABEL[$a['type']] ?> · <?= time_ago($a['at']) ?></span>
            <div><?= e($a['text']) ?></div>
            <?php if ($deal): ?><a class="small" href="?p=negocio&amp;id=<?= e($deal['id']) ?>"><?= e($deal['title']) ?></a><?php endif ?>
          </div>
        </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
</div>
