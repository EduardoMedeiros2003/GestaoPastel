<?php
/** @var array $data @var App\Crm $crm */
$q = trim((string) ($_GET['q'] ?? ''));
$client = fn(array $d) => $data['companies'][$d['companyId'] ?? '']['name'] ?? 'Sem cliente';

// Se a frase parece um negócio (tem cliente e valor, quantidade, etapa, data ou origem), oferece criar.
$parsed = null;
if (str_contains($q, ' ')) {
    $p = $crm->parse($q);
    if ($p['company'] && ($p['value'] || $p['quantity'] || $p['stage'] || $p['expectedClose'] || $p['source'])) {
        $parsed = $p;
    }
}
$found = $q === '' ? [] : array_filter($data['deals'], fn($d) => str_contains(mb_strtolower($d['title'] . ' ' . $client($d)), mb_strtolower($q)));
?>
<div class="page-head">
  <div><h1>Busca</h1><p><?= $q === '' ? 'Digite no campo lá em cima.' : 'Resultados para “' . e($q) . '”' ?></p></div>
</div>

<?php if ($parsed): ?>
<div class="panel" style="margin-bottom:20px">
  <div class="panel-head"><h2>Criar negócio</h2></div>
  <div class="panel-body">
    <dl class="parsed">
      <div><dt>Cliente</dt><dd><?= e($parsed['company']) ?></dd></div>
      <?php if ($parsed['quantity']): ?><div><dt>Quantidade</dt><dd><?= number_format($parsed['quantity']['n'], 0, ',', '.') ?> <?= e($parsed['quantity']['unit']) ?></dd></div><?php endif ?>
      <?php if ($parsed['value']): ?><div><dt>Valor</dt><dd><?= money($parsed['value']) ?></dd></div><?php endif ?>
      <?php if ($parsed['stage']): ?><div><dt>Etapa</dt><dd><?= e($data['stages'][$parsed['stage']]['name']) ?></dd></div><?php endif ?>
      <?php if ($parsed['expectedClose']): ?><div><dt>Fecha</dt><dd><?= dmy($parsed['expectedClose']) ?></dd></div><?php endif ?>
      <?php if ($parsed['source']): ?><div><dt>Origem</dt><dd><?= e($parsed['source']) ?></dd></div><?php endif ?>
      <?php if ($parsed['tags']): ?><div><dt>Etiquetas</dt><dd><?= e('#' . implode(' #', $parsed['tags'])) ?></dd></div><?php endif ?>
    </dl>
    <form method="post" class="actions">
      <?= form_head('quick_add') ?>
      <input type="hidden" name="text" value="<?= e($q) ?>">
      <button class="btn primary"><?= icon('plus') ?>Criar negócio para <?= e($parsed['company']) ?></button>
    </form>
  </div>
</div>
<?php endif ?>

<?php if ($q !== ''): ?>
<div class="panel">
  <div class="panel-head"><h2>Negócios</h2><span class="small muted"><?= count($found) ?> encontrados</span></div>
  <div class="panel-body">
    <?php if ($found): ?>
    <ul class="list">
      <?php foreach (array_slice($found, 0, 20) as $d): ?>
        <li class="result">
          <a href="?p=negocio&amp;id=<?= e($d['id']) ?>"><b><?= e($d['title']) ?></b> <span class="muted">· <?= e($client($d)) ?></span></a>
          <span><span class="pill <?= $d['health']['state'] ?>"><?= STATE_LABEL[$d['health']['state']] ?></span> <?= money($d['value']) ?></span>
        </li>
      <?php endforeach ?>
    </ul>
    <?php else: ?>
      <p class="empty">Nenhum negócio com esse nome<?= $parsed ? '. Use o botão acima para criar.' : '.' ?></p>
    <?php endif ?>
  </div>
</div>
<?php endif ?>
