<?php
/** @var array $data */
$stages = $data['settings']['stages'];
$order = array_flip(array_column($stages, 'id'));
$reached = [];
foreach ($stages as $i => $s) {
    $reached[$i] = count(array_filter($data['deals'], fn($d) => $d['status'] === 'won' || $order[$d['stage']] >= $i));
}
$lost = $data['lostReasons'];
$maxLost = max([1, ...array_values($lost)]);
?>
<div class="page-head"><div><h1>Relatórios</h1><p><?= e($data['quarter']['label']) ?></p></div></div>

<div class="cols">
  <div class="stack">
    <div class="panel" style="overflow:auto">
      <div class="panel-head" style="padding-bottom:14px"><h2>Vendas por pessoa</h2></div>
      <table>
        <thead><tr><th>Vendedor</th><th class="right">Vendido</th><th class="right">Fechados</th><th class="right">Abertos</th><th class="right">Contatos (7 dias)</th></tr></thead>
        <tbody>
        <?php foreach ($data['byPerson'] as $p): ?>
          <tr>
            <td><div class="who-cell"><?= avatar($p['user']) ?><div><?= e($p['user']['name']) ?><div class="small muted"><?= e($p['user']['role']) ?></div></div></div></td>
            <td class="right"><b><?= money($p['won']) ?></b></td>
            <td class="right"><?= $p['wonCount'] ?></td>
            <td class="right"><?= $p['open'] ?></td>
            <td class="right"><?= $p['contacts7d'] ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>

    <div class="panel" style="overflow:auto">
      <div class="panel-head" style="padding-bottom:14px"><h2>Quantos negócios chegam em cada etapa</h2></div>
      <table>
        <thead><tr><th>Etapa</th><th class="right">Negócios</th><th class="right">Passaram da anterior</th></tr></thead>
        <tbody>
        <?php foreach ($stages as $i => $s): ?>
          <tr>
            <td><?= e($s['name']) ?></td>
            <td class="right"><?= $reached[$i] ?></td>
            <td class="right"><?= $i ? round($reached[$i] / max(1, $reached[$i - 1]) * 100) . '%' : '<span class="muted">–</span>' ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="stack">
    <div class="panel">
      <div class="panel-head"><h2>Por que perdemos</h2></div>
      <div class="panel-body">
        <?php foreach ($lost as $reason => $value): ?>
          <div style="margin-bottom:12px">
            <div style="display:flex;justify-content:space-between"><span><?= e($reason) ?></span><b><?= money($value) ?></b></div>
            <div class="bar-track"><i style="width:<?= round($value / $maxLost * 100, 1) ?>%"></i></div>
          </div>
        <?php endforeach ?>
        <?php if (!$lost): ?><p class="empty">Nenhuma perda registrada.</p><?php endif ?>
      </div>
    </div>

    <div class="panel" style="overflow:auto">
      <div class="panel-head" style="padding-bottom:14px"><h2>De onde vêm os clientes</h2></div>
      <table>
        <thead><tr><th>Origem</th><th class="right">Ganhos</th><th class="right">Perdidos</th><th class="right">Taxa</th></tr></thead>
        <tbody>
        <?php foreach ($data['bySource'] as $source => $c): ?>
          <tr>
            <td><?= e($source) ?></td>
            <td class="right"><?= $c['won'] ?></td>
            <td class="right"><?= $c['lost'] ?></td>
            <td class="right"><b><?= round($c['won'] / max(1, $c['won'] + $c['lost']) * 100) ?>%</b></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
