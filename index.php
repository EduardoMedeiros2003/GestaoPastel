<?php
/**
 * Leo do Pastel – Vendas
 *
 * CRM da equipe comercial: encomendas para eventos, revenda de pastel congelado e franquias.
 * Projeto só com arquivos PHP: sem JavaScript, framework ou Composer. PHP 8.1+.
 *
 * Rodar:  php -S 127.0.0.1:8090   e abrir http://127.0.0.1:8090
 * Dados:  ficam em data/*.php (cada arquivo devolve um array). Na primeira vez são criados
 *         clientes e negócios de exemplo, todos fictícios; para recomeçar, apague a pasta data/.
 *
 * Telas: Hoje (vendido no trimestre, parados, tarefas do dia), Funil (negócios por etapa),
 * Clientes, Relatórios, página do negócio (dados, nota explicada, contatos, ganhou/perdeu),
 * Novo negócio e Busca. Na busca dá para escrever uma frase como
 * "Colégio Aurora 800 pastéis 9,6k orçamento sexta via whatsapp #festa" e criar o negócio.
 *
 * Nota do negócio (0 a 100): soma pontos por etapa, contato recente, próximo passo marcado
 * e data de fechamento; a página do negócio mostra cada item. Negócio parado: passou do prazo
 * sem contato da etapa e não tem próximo passo marcado para frente.
 *
 * index.php           recebe os formulários (POST) e escolhe a tela
 * src/helpers.php     formatação, ícones, CSRF, mensagens, logo
 * src/Crm.php         validação e gravação de negócios, contatos e tarefas
 * src/Sales.php       nota, negócios parados, tarefas do dia e relatórios
 * src/QuickParser.php leitura da frase digitada na busca
 * src/Store.php       gravação em data/*.php com trava e troca atômica
 * src/Seeder.php      dados de exemplo, etapas e motivos de perda
 * views/              uma tela por arquivo; views/_estilo.php é o estilo
 */
declare(strict_types=1);

spl_autoload_register(function (string $class) {
    $file = __DIR__ . '/src/' . basename(str_replace('\\', '/', $class)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
require __DIR__ . '/src/helpers.php';

use App\{Crm, Store};

date_default_timezone_set('America/Sao_Paulo');
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$crm = new Crm(new Store(__DIR__ . '/data'));
$page = $_GET['p'] ?? 'hoje';
$error = null;

// Ações (formulários). Depois de gravar, redireciona para não reenviar ao atualizar a página.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (string) ($_POST['id'] ?? '');
    try {
        if (!csrf_ok()) {
            throw new InvalidArgumentException('A página expirou. Tente de novo.');
        }
        switch ($action) {
            case 'theme':
                $theme = in_array($_POST['theme'] ?? '', ['light', 'dark'], true) ? $_POST['theme'] : '';
                setcookie('tema', $theme, ['expires' => $theme ? time() + 365 * 86400 : time() - 3600, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true]);
                redirect_back((string) ($_POST['back'] ?? ''));

            case 'deal_create':
                $deal = $crm->createFromForm($_POST);
                flash('Negócio criado.');
                redirect(['p' => 'negocio', 'id' => $deal['id']]);

            case 'quick_add':
                $deal = $crm->quickAdd((string) ($_POST['text'] ?? ''));
                flash('Negócio criado a partir da frase.');
                redirect(['p' => 'negocio', 'id' => $deal['id']]);

            case 'deal_update':
                $crm->updateDeal($id, $_POST);
                flash('Salvo.');
                redirect(['p' => 'negocio', 'id' => $id]);

            case 'deal_won':
                $crm->setStatus($id, 'won');
                flash('Negócio fechado. Boa!');
                redirect(['p' => 'negocio', 'id' => $id]);

            case 'deal_lost':
                $crm->setStatus($id, 'lost', (string) ($_POST['reason'] ?? ''));
                flash('Marcado como perdido.');
                redirect(['p' => 'negocio', 'id' => $id]);

            case 'deal_reopen':
                $crm->setStatus($id, 'open');
                flash('Negócio reaberto.');
                redirect(['p' => 'negocio', 'id' => $id]);

            case 'deal_delete':
                $crm->deleteDeal($id);
                flash('Negócio excluído.');
                redirect(['p' => 'funil']);

            case 'activity_add':
                $crm->addActivity($id, $_POST['type'] ?? '', $_POST['text'] ?? '');
                flash('Contato registrado.');
                redirect(['p' => 'negocio', 'id' => $id]);

            case 'task_done':
                $crm->completeTask($id, $_POST['title'] ?? '', $_POST['channel'] ?? '');
                flash('Tarefa feita.');
                redirect(['p' => 'hoje']);

            default:
                throw new InvalidArgumentException('Ação desconhecida.');
        }
    } catch (InvalidArgumentException $ex) {
        $error = $ex->getMessage();
        // Mostra de novo a tela do formulário, com o que foi digitado.
        $page = match ($action) {
            'deal_create' => 'novo',
            'quick_add' => 'busca',
            'deal_update', 'deal_won', 'deal_lost', 'deal_reopen', 'activity_add' => 'negocio',
            default => $page,
        };
        if ($page === 'negocio') {
            $_GET['id'] = $id;
        }
        if ($action === 'quick_add') {
            $_GET['q'] = (string) ($_POST['text'] ?? '');
        }
    }
}

$pages = [
    'hoje' => 'Hoje', 'funil' => 'Funil', 'clientes' => 'Clientes', 'relatorios' => 'Relatórios',
    'negocio' => 'Negócio', 'novo' => 'Novo negócio', 'busca' => 'Busca',
];
if (!isset($pages[$page])) {
    $page = 'hoje';
}
$data = $crm->data();
$me = $data['users'][Crm::ME];
$theme = in_array($_COOKIE['tema'] ?? '', ['light', 'dark'], true) ? $_COOKIE['tema'] : '';
$message = flash();

ob_start();
require __DIR__ . "/views/{$page}.php";
$content = ob_get_clean();

require __DIR__ . '/views/layout.php';
