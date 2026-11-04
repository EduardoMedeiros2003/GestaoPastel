<?php
/** @var string $page @var array $pages @var string $content @var ?string $message @var ?string $error @var array $me @var string $theme */
$nav = ['hoje' => ['Hoje', 'home'], 'funil' => ['Funil', 'columns'], 'clientes' => ['Clientes', 'users'], 'relatorios' => ['Relatórios', 'chart']];
$activeNav = in_array($page, ['negocio', 'novo'], true) ? 'funil' : $page;
$back = '?' . ($_SERVER['QUERY_STRING'] ?? '');
$themes = ['' => ['Auto', 'auto'], 'light' => ['Claro', 'sun'], 'dark' => ['Escuro', 'moon']];
?>
<!doctype html>
<html lang="pt-BR"<?= $theme ? ' data-theme="' . $theme . '"' : '' ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pages[$page]) ?> – Leo do Pastel</title>
  <link rel="icon" href="data:image/svg+xml;base64,<?= base64_encode(logo_svg()) ?>">
  <style><?php require __DIR__ . '/_estilo.php' ?></style>
</head>
<body>
<div class="shell">
  <aside class="side">
    <a class="logo" href="?p=hoje"><?= logo_svg(34) ?><span>Leo do Pastel<small>Vendas</small></span></a>
    <nav>
      <?php foreach ($nav as $key => [$label, $ico]): ?>
        <a href="?p=<?= $key ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><?= icon($ico) ?><span class="label"><?= $label ?></span></a>
      <?php endforeach ?>
    </nav>
    <div class="side-foot">
      <form method="post" class="theme-switch" aria-label="Tema">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="theme">
        <input type="hidden" name="back" value="<?= e($back) ?>">
        <?php foreach ($themes as $value => [$label, $ico]): ?>
          <button name="theme" value="<?= $value ?>" class="<?= $theme === $value ? 'on' : '' ?>" title="Tema: <?= $label ?>"><?= icon($ico) ?><span class="label"><?= $label ?></span></button>
        <?php endforeach ?>
      </form>
      <div class="me"><?= avatar($me) ?><div><b><?= e($me['name']) ?></b><span class="muted"><?= e($me['role']) ?></span></div></div>
    </div>
  </aside>

  <div class="main">
    <header class="bar">
      <form class="search" method="get" role="search">
        <input type="hidden" name="p" value="busca">
        <?= icon('search') ?>
        <input name="q" value="<?= e($page === 'busca' ? ($_GET['q'] ?? '') : '') ?>" placeholder="Buscar ou criar: Colégio Aurora 800 pastéis 9,6k sexta" autocomplete="off">
      </form>
      <a class="btn primary" href="?p=novo"><?= icon('plus') ?><span class="label">Novo negócio</span></a>
    </header>
    <main>
      <?php if ($message): ?><div class="alert ok"><?= icon('check') ?><?= e($message) ?></div><?php endif ?>
      <?php if ($error): ?><div class="alert error"><?= icon('alert') ?><?= e($error) ?></div><?php endif ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
