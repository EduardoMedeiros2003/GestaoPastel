<?php
/** @var array $data */
$filters = ['todos' => 'Todos', 'meus' => 'Meus', 'parados' => 'Parados'];
$filter = isset($filters[$_GET['f'] ?? '']) ? $_GET['f'] : 'todos';
$deals = array_filter($data['deals'], function (array $d) use ($filter) {
    if ($d['status'] !== 'open') {
        return false;
    }
    return match ($filter) {
        'meus' => $d['ownerId'] === App\Crm::ME,
        'parados' => $d['health']['stale'],
        default => true,
    };
});
$total = array_sum(array_column($deals, 'value'));
?>
<div class="page-head">
  <div><h1>Funil</h1><p><?= count($deals) ?> negócios abertos · <?= money($total) ?></p></div>
  <div class="tools">
    <nav class="segmented" aria-label="Filtro">
      <?php foreach ($filters as $key => $label): ?>
        <a href="?p=funil&amp;f=<?= $key ?>" class="<?= $filter === $key ? 'on' : '' ?>"><?= $label ?></a>
      <?php endforeach ?>
    </nav>
  </div>
</div>

<div class="board">
  <?php foreach ($data['settings']['stages'] as $stage):
      $cards = array_filter($deals, fn($d) => $d['stage'] === $stage['id']);
      usort($cards, fn($a, $b) => $b['value'] <=> $a['value']); ?>
    <section class="col">
      <div class="col-head">
        <span><b><?= e($stage['name']) ?></b><span class="count"><?= count($cards) ?></span></span>
        <span class="sum"><?= money(array_sum(array_column($cards, 'value'))) ?></span>
      </div>
      <?php foreach ($cards as $d):
          $h = $d['health'];
          $when = $h['stale'] ? '<span class="red">parado há ' . $h['idleDays'] . ' dias</span>'
              : ($h['idleDays'] === 0 ? 'contato hoje' : 'contato ' . time_ago($d['lastActivityAt'])); ?>
        <a class="card <?= $h['stale'] ? 'stale' : '' ?>" href="?p=negocio&amp;id=<?= e($d['id']) ?>">
          <span class="title"><?= e($d['title']) ?></span>
          <span class="client"><?= e($data['companies'][$d['companyId'] ?? '']['name'] ?? 'Sem cliente') ?></span>
          <span class="row"><span class="value"><?= money($d['value']) ?></span><?= avatar($data['users'][$d['ownerId'] ?? ''] ?? null, true) ?></span>
          <span class="foot"><span><?= $when ?></span><span>nota <span class="score"><?= $h['score'] ?></span></span></span>
        </a>
      <?php endforeach ?>
      <?php if (!$cards): ?><p class="empty small" style="text-align:center">Nenhum negócio.</p><?php endif ?>
    </section>
  <?php endforeach ?>
</div>
<p class="small muted">Para mudar a etapa, marcar como ganho ou perdido, abra o negócio.</p>
