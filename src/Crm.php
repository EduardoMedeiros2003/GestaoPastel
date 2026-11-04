<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

/**
 * Regras do CRM: negócios, contatos registrados, clientes e tarefas.
 * Erros de validação viram InvalidArgumentException com a mensagem que aparece na tela.
 */
final class Crm
{
    public const ACTIVITY_TYPES = ['call' => 'Ligação', 'whatsapp' => 'WhatsApp', 'meeting' => 'Reunião', 'email' => 'E-mail', 'note' => 'Anotação'];
    public const ME = 'u_leo'; // ainda não tem login

    public array $settings;
    private ?array $cache = null;

    public function __construct(private Store $db)
    {
        if (!$db->exists('deals')) {
            Seeder::run($db);
        }
        $this->settings = $db->all('settings');
    }

    /** Tudo que as telas usam, com a nota de cada negócio já calculada. */
    public function data(): array
    {
        if ($this->cache) {
            return $this->cache;
        }
        $activities = $this->db->all('activities');
        usort($activities, fn($a, $b) => strcmp($b['at'], $a['at']));
        $users = $this->db->all('users');
        $sales = new Sales($this->settings, $activities);
        $deals = array_map(fn($d) => $d + ['health' => $sales->health($d)], $this->db->all('deals'));

        return $this->cache = [
            'settings' => $this->settings,
            'users' => array_column($users, null, 'id'),
            'companies' => array_column($this->db->all('companies'), null, 'id'),
            'contacts' => array_column($this->db->all('contacts'), null, 'id'),
            'stages' => array_column($this->settings['stages'], null, 'id'),
            'deals' => array_column($deals, null, 'id'),
            'activities' => $activities,
            'tasks' => $sales->tasks($deals),
            'quarter' => $sales->quarter($deals, (float) $this->settings['quarterGoal']),
            'byPerson' => $sales->byPerson($deals, $users, $activities),
            'lostReasons' => $sales->lostReasons($deals),
            'bySource' => $sales->bySource($deals),
        ];
    }

    public function createDeal(array $in): array
    {
        $fields = $this->dealFields($in, false);
        $deal = $fields + [
            'id' => Store::id('d'), 'stage' => $this->settings['stages'][0]['id'], 'status' => 'open',
            'ownerId' => self::ME, 'companyId' => null, 'contactId' => null, 'expectedClose' => null,
            'nextStep' => null, 'nextStepDate' => null, 'source' => 'Outro', 'champion' => false,
            'lostReason' => null, 'closedAt' => null, 'tags' => [],
            'createdAt' => self::now(), 'lastActivityAt' => self::now(),
        ];
        $this->db->insert('deals', $deal);
        $this->log($deal['id'], 'stage', 'Negócio criado');
        $this->cache = null;
        return $deal;
    }

    /** Formulário "Novo negócio": cliente pelo nome (cadastra se for novo). */
    public function createFromForm(array $post): array
    {
        $client = trim((string) ($post['company'] ?? ''));
        if ($client === '') {
            throw new InvalidArgumentException('Informe o cliente.');
        }
        $companyId = $this->findOrCreateCompany($client);
        return $this->createDeal([
            'title' => $post['title'] ?? '',
            'value' => $post['value'] ?? '',
            'stage' => $post['stage'] ?? '',
            'expectedClose' => $post['expectedClose'] ?? '',
            'companyId' => $companyId,
            'contactId' => $this->firstContact($companyId),
        ]);
    }

    /** Busca: "Colégio Aurora 800 pastéis 9,6k orçamento sexta via whatsapp" vira um negócio. */
    public function quickAdd(string $text): array
    {
        $p = (new QuickParser($this->settings['stages']))->parse($text);
        if (!$p['company']) {
            throw new InvalidArgumentException('Escreva pelo menos o nome do cliente.');
        }
        $companyId = $this->findOrCreateCompany($p['company']);
        $name = $this->db->find('companies', $companyId)['name'];
        return $this->createDeal(array_filter([
            'title' => $p['quantity']
                ? 'Pedido de ' . number_format($p['quantity']['n'], 0, ',', '.') . ' ' . $p['quantity']['unit']
                : 'Negócio ' . $name,
            'companyId' => $companyId,
            'contactId' => $this->firstContact($companyId),
            'value' => $p['value'] ?? 0,
            'stage' => $p['stage'],
            'expectedClose' => $p['expectedClose'],
            'source' => $p['source'],
            'tags' => $p['tags'],
        ], fn($v) => $v !== null));
    }

    public function parse(string $text): array
    {
        return (new QuickParser($this->settings['stages']))->parse($text);
    }

    /** Salva o formulário de dados do negócio. Mudança de etapa entra no histórico. */
    public function updateDeal(string $id, array $post): void
    {
        $before = $this->db->find('deals', $id) ?? throw new InvalidArgumentException('Negócio não encontrado.');
        $patch = $this->dealFields([
            'title' => $post['title'] ?? '',
            'value' => $post['value'] ?? '',
            'stage' => $post['stage'] ?? '',
            'nextStep' => $post['nextStep'] ?? '',
            'nextStepDate' => $post['nextStepDate'] ?? '',
            'expectedClose' => $post['expectedClose'] ?? '',
            'ownerId' => $post['ownerId'] ?? '',
            'champion' => ($post['champion'] ?? '0') === '1',
        ], true);
        if ($patch['stage'] !== $before['stage']) {
            $names = array_column($this->settings['stages'], 'name', 'id');
            $this->log($id, 'stage', "{$names[$before['stage']]} → {$names[$patch['stage']]}");
            $patch['lastActivityAt'] = self::now();
        }
        $this->db->update('deals', $id, $patch);
        $this->cache = null;
    }

    public function setStatus(string $id, string $status, ?string $reason = null): void
    {
        $before = $this->db->find('deals', $id) ?? throw new InvalidArgumentException('Negócio não encontrado.');
        if (!in_array($status, ['open', 'won', 'lost'], true)) {
            throw new InvalidArgumentException('Status inválido.');
        }
        if ($status === 'lost' && !in_array($reason, $this->settings['lostReasons'], true)) {
            throw new InvalidArgumentException('Escolha o motivo da perda.');
        }
        if ($status === $before['status']) {
            return;
        }
        $this->db->update('deals', $id, [
            'status' => $status,
            'lostReason' => $status === 'lost' ? $reason : null,
            'closedAt' => $status === 'open' ? null : self::now(),
        ]);
        $label = ['won' => 'Fechado (ganho)', 'lost' => 'Perdido: ' . $reason, 'open' => 'Reaberto'][$status];
        $this->log($id, 'stage', $label);
        $this->cache = null;
    }

    public function deleteDeal(string $id): void
    {
        $this->db->delete('deals', $id) || throw new InvalidArgumentException('Negócio não encontrado.');
        $this->db->mutate('activities', function (array &$rows) use ($id) {
            $rows = array_values(array_filter($rows, fn($a) => $a['dealId'] !== $id));
        });
        $this->cache = null;
    }

    public function addActivity(string $dealId, mixed $type, mixed $text): void
    {
        $this->db->find('deals', $dealId) ?? throw new InvalidArgumentException('Negócio não encontrado.');
        if (!isset(self::ACTIVITY_TYPES[$type])) {
            throw new InvalidArgumentException('Escolha o tipo de contato.');
        }
        $text = trim((string) $text);
        if ($text === '' || mb_strlen($text) > 1000) {
            throw new InvalidArgumentException('Escreva o que aconteceu.');
        }
        $this->log($dealId, $type, $text);
        $this->cache = null;
    }

    /** Marca uma tarefa do dia como feita: vira um contato registrado no negócio. */
    public function completeTask(string $dealId, mixed $title, mixed $channel): void
    {
        $this->db->find('deals', $dealId) ?? throw new InvalidArgumentException('Negócio não encontrado.');
        $type = isset(self::ACTIVITY_TYPES[$channel]) ? $channel : 'note';
        $this->log($dealId, $type, 'Feito: ' . mb_substr(trim((string) $title) ?: 'tarefa', 0, 200));
        $this->cache = null;
    }

    private function dealFields(array $in, bool $partial): array
    {
        $out = [];
        $stageIds = array_column($this->settings['stages'], 'id');
        $date = fn($v, $msg) => $v === null || $v === '' ? null : (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) && strtotime($v) ? $v : throw new InvalidArgumentException($msg));

        if (!$partial || array_key_exists('title', $in)) {
            $title = trim((string) ($in['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 160) {
                throw new InvalidArgumentException('Informe o título do negócio.');
            }
            $out['title'] = $title;
        }
        if (!$partial || array_key_exists('value', $in)) {
            // Aceita "12.000,50", "2.400" (ponto de milhar) e "12000.50": viram 12000.5, 2400 e 12000.5.
            $value = trim((string) ($in['value'] ?? ''));
            if (str_contains($value, ',')) {
                $value = str_replace(['.', ','], ['', '.'], $value);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                $value = str_replace('.', '', $value);
            }
            if (!is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException('Valor inválido.');
            }
            $out['value'] = (float) $value;
        }
        if (array_key_exists('stage', $in) && $in['stage'] !== '') {
            in_array($in['stage'], $stageIds, true) || throw new InvalidArgumentException('Etapa inválida.');
            $out['stage'] = $in['stage'];
        }
        foreach (['expectedClose' => 'Data de fechamento inválida.', 'nextStepDate' => 'Data do próximo passo inválida.'] as $k => $msg) {
            if (array_key_exists($k, $in)) {
                $out[$k] = $date($in[$k], $msg);
            }
        }
        if (array_key_exists('nextStep', $in)) {
            $step = trim((string) $in['nextStep']);
            mb_strlen($step) <= 200 || throw new InvalidArgumentException('Próximo passo muito longo.');
            $out['nextStep'] = $step ?: null;
        }
        if (array_key_exists('ownerId', $in)) {
            $this->db->find('users', (string) $in['ownerId']) || throw new InvalidArgumentException('Responsável inválido.');
            $out['ownerId'] = $in['ownerId'];
        }
        foreach (['companyId', 'contactId', 'source'] as $k) {
            if (array_key_exists($k, $in)) {
                $out[$k] = $in[$k] === null ? null : (string) $in[$k];
            }
        }
        if (array_key_exists('champion', $in)) {
            $out['champion'] = (bool) $in['champion'];
        }
        if (array_key_exists('tags', $in)) {
            $out['tags'] = array_values(array_map('strval', (array) $in['tags']));
        }
        return $out;
    }

    private function log(string $dealId, string $type, string $text): void
    {
        $a = $this->db->insert('activities', [
            'id' => Store::id('a'), 'dealId' => $dealId, 'userId' => self::ME,
            'type' => $type, 'text' => $text, 'at' => self::now(),
        ]);
        if ($type !== 'stage') {
            $this->db->update('deals', $dealId, ['lastActivityAt' => $a['at']]);
        }
    }

    private function findOrCreateCompany(string $name): string
    {
        $lower = mb_strtolower($name);
        foreach ($this->db->all('companies') as $c) {
            $existing = mb_strtolower($c['name']);
            if ($existing === $lower || str_starts_with($existing, $lower . ' ')) {
                return $c['id'];
            }
        }
        if (mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Nome do cliente muito longo.');
        }
        return $this->db->insert('companies', ['id' => Store::id('c'), 'name' => $name, 'industry' => null, 'size' => null, 'website' => null])['id'];
    }

    private function firstContact(string $companyId): ?string
    {
        foreach ($this->db->all('contacts') as $c) {
            if ($c['companyId'] === $companyId) {
                return $c['id'];
            }
        }
        return null;
    }

    private static function now(): string
    {
        return date('Y-m-d\TH:i:s');
    }
}
