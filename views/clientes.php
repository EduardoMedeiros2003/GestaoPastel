<?php
/** @var array $data */
$q = trim((string) ($_GET['q'] ?? ''));
$contactOf = [];
foreach ($data['contacts'] as $c) {
    $contactOf[$c['companyId'] ?? ''] ??= $c;
}
$rows = [];
foreach ($data['companies'] as $c) {
    $deals = array_filter($data['deals'], fn($d) => $d['companyId'] === $c['id']);
    $open = array_filter($deals, fn($d) => $d['status'] === 'open');
    usort($open, fn($a, $b) => $b['value'] <=> $a['value']);
    $contact = $contactOf[$c['id']] ?? null;
    if ($q !== '' && !str_contains(mb_strtolower($c['name'] . ' ' . ($contact['name'] ?? '') . ' ' . ($c['industry'] ?? '')), mb_strtolower($q))) {
        continue;
    }
    $rows[] = [
        'company' => $c,
        'contact' => $contact,
        'main' => $open[0] ?? (array_values($deals)[0] ?? null),
        'open' => array_sum(array_column($open, 'value')),
        'bought' => array_sum(array_column(array_filter($deals, fn($d) => $d['status'] === 'won'), 'value')),
    ];
}
usort($rows, fn($a, $b) => strcoll($a['company']['name'], $b['company']['name']));
?>
<div class="page-head">
  <div><h1>Clientes</h1><p><?= count($data['companies']) ?> cadastrados</p></div>
  <form class="tools" method="get">
    <input type="hidden" name="p" value="clientes">
    <input name="q" value="<?= e($q) ?>" placeholder="Procurar cliente ou contato" style="width:240px">
    <button class="btn">Procurar</button>
    <?php if ($q !== ''): ?><a class="btn ghost" href="?p=clientes">Limpar</a><?php endif ?>
  </form>
</div>

<div class="panel" style="overflow:auto">
  <table>
    <thead><tr><th>Cliente</th><th>Contato</th><th>Tipo</th><th class="right">Em aberto</th><th class="right">Já comprou</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php if ($r['main']): ?><a class="row-link" href="?p=negocio&amp;id=<?= e($r['main']['id']) ?>"><?= e($r['company']['name']) ?></a><?php else: ?><b><?= e($r['company']['name']) ?></b><?php endif ?></td>
        <td><?php if ($r['contact']): ?><?= e($r['contact']['name']) ?><div class="small muted"><?= e($r['contact']['role']) ?> · <?= e($r['contact']['phone']) ?></div><?php else: ?><span class="muted">–</span><?php endif ?></td>
        <td><?= e($r['company']['industry'] ?? '') ?></td>
        <td class="right"><?= $r['open'] ? money($r['open']) : '<span class="muted">–</span>' ?></td>
        <td class="right"><?= $r['bought'] ? '<span class="green">' . money($r['bought']) . '</span>' : '<span class="muted">–</span>' ?></td>
      </tr>
    <?php endforeach ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="muted">Nenhum cliente encontrado.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
