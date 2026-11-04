<?php
declare(strict_types=1);

namespace App;

/**
 * Dados de exemplo. As datas são relativas a hoje para os negócios
 * parados continuarem parados não importa quando o sistema for aberto.
 */
final class Seeder
{
    public static function settings(): array
    {
        return [
            'company' => 'Leo do Pastel',
            'currency' => 'BRL',
            'quarterGoal' => 120000,
            'stages' => [
                ['id' => 'lead', 'name' => 'Contato', 'probability' => 10, 'staleAfter' => 3, 'aliases' => ['lead', 'contato']],
                ['id' => 'qualified', 'name' => 'Briefing', 'probability' => 25, 'staleAfter' => 5, 'aliases' => ['qualificado', 'qualificação', 'qualificacao', 'briefing']],
                ['id' => 'demo', 'name' => 'Degustação', 'probability' => 45, 'staleAfter' => 7, 'aliases' => ['degustacao', 'degustação', 'demo', 'prova', 'visita']],
                ['id' => 'proposal', 'name' => 'Orçamento', 'probability' => 65, 'staleAfter' => 5, 'aliases' => ['orcamento', 'orçamento', 'proposta']],
                ['id' => 'negotiation', 'name' => 'Fechando', 'probability' => 80, 'staleAfter' => 4, 'aliases' => ['fechando', 'negociação', 'negociacao', 'sinal', 'contrato']],
            ],
            'lostReasons' => ['Preço', 'Fechou com outro buffet', 'Sem verba', 'Data indisponível', 'Sem resposta', 'Cardápio não atende'],
        ];
    }

    public static function run(Store $db): void
    {
        $users = [
            ['id' => 'u_leo', 'name' => 'Leo Martins', 'initials' => 'LM', 'color' => '#e8522a', 'role' => 'Dono · Contas grandes'],
            ['id' => 'u_bia', 'name' => 'Bia Fontes', 'initials' => 'BF', 'color' => '#f2a516', 'role' => 'Eventos & Encomendas'],
            ['id' => 'u_rafa', 'name' => 'Rafa Oliveira', 'initials' => 'RO', 'color' => '#2bb673', 'role' => 'Revenda & Atacado'],
        ];

        $companies = [
            ['Prisma Contabilidade', 'Escritório', '51-200'], ['Rede Sabor Investimentos', 'Franquia', '11-50'],
            ['Colégio Santa Clara', 'Educação', '51-200'], ['Bar do Zé da Lagoa', 'Bar', '1-10'],
            ['Construtora Alicerce', 'Construção', '201-500'], ['Academia Corpo em Movimento', 'Fitness', '11-50'],
            ['Laço Dourado Assessoria', 'Casamentos', '1-10'], ['Mercado Bom Preço', 'Supermercado', '201-500'],
            ['Feira Gastronômica Vila Nova', 'Eventos', '11-50'], ['Empório da Vila', 'Bar/Empório', '1-10'],
            ['Coworking Colmeia', 'Coworking', '11-50'], ['Hotel Mar Aberto', 'Hotelaria', '51-200'],
            ['Hospital Vida Nova', 'Saúde', '500+'], ['Agência Faísca Eventos', 'Agência', '11-50'],
        ];
        $first = ['Rafael', 'Juliana', 'Marcos', 'Zé', 'Diego', 'Fernanda', 'Lucas', 'Beatriz', 'Thiago', 'Camila', 'André', 'Larissa', 'Gustavo', 'Renata'];
        $last = ['Almeida', 'Costa', 'Souza', 'Lima', 'Pereira', 'Rocha', 'Martins', 'Barbosa', 'Gomes', 'Carvalho', 'Freitas', 'Moreira', 'Dias', 'Teixeira'];
        $roles = ['Sócio(a)', 'RH / Eventos', 'Gerente de Compras', 'Proprietário(a)', 'Coordenador(a) de Eventos', 'Gerente'];

        $companyRows = $contactRows = [];
        foreach ($companies as $i => [$name, $industry, $size]) {
            $cid = 'c_' . $i;
            $slug = strtolower(preg_replace('/[^a-z]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', explode(' ', $name)[0])));
            $companyRows[] = ['id' => $cid, 'name' => $name, 'industry' => $industry, 'size' => $size, 'website' => "{$slug}.com.br"];
            $contactRows[] = [
                'id' => 'p_' . $i, 'name' => $first[$i] . ' ' . $last[$i], 'companyId' => $cid, 'role' => $roles[$i % count($roles)],
                'email' => preg_replace('/[^a-z]/', '', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $first[$i]))) . "@{$slug}.com.br",
                'phone' => '+55 11 9' . str_pad((string) (8000 + $i * 37), 4, '0') . '-' . str_pad((string) (1000 + $i * 211), 4, '0'),
            ];
        }

        // cliente, título, valor, etapa ou status, responsável, criado há N dias, último contato há N dias,
        // fecha em N dias, próximo passo, passo em N dias, origem, cliente defende a compra, motivo da perda
        $spec = [
            [0, 'Coffee break mensal – 12 meses', 28800, 'negotiation', 'u_leo', 30, 1, 7, 'Assinar contrato e receber sinal', 1, 'Indicação', true],
            [1, 'Franquia – unidade Shopping Norte', 120000, 'proposal', 'u_leo', 40, 12, 25, null, null, 'Instagram', true],
            [2, 'Festa junina – 800 pastéis', 9600, 'demo', 'u_bia', 12, 2, 20, 'Degustação com a comissão de pais', 0, 'WhatsApp', false],
            [3, 'Pastel congelado – entrega semanal', 18000, 'qualified', 'u_rafa', 10, 4, 30, 'Levar amostras de massa e recheio', 2, 'Balcão', false],
            [4, 'Confraternização de obra – 300 pessoas', 13500, 'proposal', 'u_bia', 18, 3, 12, 'Enviar orçamento revisado', -2, 'Indicação', true],
            [5, 'Lanche pós-treino – parceria', 4800, 'lead', 'u_rafa', 3, 1, 40, 'Ligar para o dono da academia', 1, 'Instagram', false],
            [6, 'Mesa de pastéis em casamento', 7200, 'demo', 'u_bia', 15, 9, -2, 'Degustação com os noivos', -4, 'Instagram', false],
            [7, 'Revenda em 6 lojas – congelados', 54000, 'negotiation', 'u_rafa', 45, 0, 5, 'Fechar preço por caixa', 2, 'Feira', true],
            [8, 'Food truck no festival (3 dias)', 36000, 'qualified', 'u_leo', 14, 4, 45, 'Visita técnica ao espaço', 5, 'Feira', false],
            [9, 'Pastel de boteco no cardápio', 6600, 'lead', 'u_rafa', 7, 6, 35, null, null, 'Balcão', false],
            [11, 'Café da manhã de hóspedes', 15600, 'proposal', 'u_leo', 20, 2, 10, 'Ligar para saber do orçamento', 0, 'Google', true],
            [12, 'Lanche do plantão noturno', 21000, 'demo', 'u_bia', 25, 13, 28, null, null, 'Indicação', false],
            [13, 'Convenção anual – 1.200 pessoas', 42000, 'proposal', 'u_leo', 32, 3, 18, 'Call com a produtora', 3, 'Indicação', true],
            [10, 'Aniversário do coworking', 2800, 'lead', 'u_bia', 2, 0, 15, 'Mandar cardápio de festa', 1, 'WhatsApp', false],
            // fechados
            [0, 'Festa de fim de ano 2025', 8400, 'won', 'u_leo', 70, 20, -20, null, null, 'Indicação', true],
            [7, 'Teste de revenda – 1 loja', 9600, 'won', 'u_rafa', 60, 12, -12, null, null, 'Feira', true],
            [2, 'Feira de ciências – lanche', 3200, 'won', 'u_bia', 40, 8, -8, null, null, 'WhatsApp', true],
            [11, 'Evento de hóspedes – Réveillon', 11000, 'won', 'u_leo', 50, 30, -30, null, null, 'Google', true],
            [9, 'Porções para o fim de semana', 4100, 'won', 'u_rafa', 25, 5, -5, null, null, 'Balcão', false],
            [3, 'Fornecimento diário de massa', 22000, 'lost', 'u_rafa', 80, 25, -25, null, null, 'Balcão', false, 'Preço'],
            [5, 'Kit lanche para alunos', 5600, 'lost', 'u_bia', 40, 15, -15, null, null, 'Instagram', false, 'Sem verba'],
            [12, 'Semana da enfermagem', 7800, 'lost', 'u_bia', 66, 40, -40, null, null, 'Indicação', false, 'Fechou com outro buffet'],
            [6, 'Chá de panela', 1900, 'lost', 'u_bia', 30, 18, -18, null, null, 'WhatsApp', false, 'Sem resposta'],
            [4, 'Inauguração de stand de vendas', 6500, 'lost', 'u_leo', 55, 22, -22, null, null, 'Google', false, 'Data indisponível'],
        ];

        $deals = $activities = [];
        $types = ['call', 'email', 'meeting', 'whatsapp', 'note'];
        $texts = [
            'call' => ['Conversa sobre cardápio e quantidades', 'Alinhamento de data e local', 'Validação de orçamento'],
            'email' => ['Enviado cardápio com sabores e preços', 'Fotos de eventos anteriores enviadas', 'Mandou mensagem cobrando retorno'],
            'meeting' => ['Degustação na loja', 'Visita ao local do evento', 'Reunião com a comissão da festa'],
            'whatsapp' => ['Confirmou número de convidados', 'Pediu opção vegana', 'Pediu ajuste de prazo'],
            'note' => ['Quem decide é o dono', 'Concorrente na mesa: buffet do bairro', 'Verba liberada para o mês'],
        ];
        foreach ($spec as $i => $s) {
            [$ci, $title, $value, $stage, $owner, $created, $touch, $close, $next, $nextIn, $source, $champion] = $s;
            $status = in_array($stage, ['won', 'lost'], true) ? $stage : 'open';
            $id = 'd_' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $deals[] = [
                'id' => $id, 'title' => $title, 'companyId' => 'c_' . $ci, 'contactId' => 'p_' . $ci, 'ownerId' => $owner,
                'value' => $value, 'stage' => $status === 'open' ? $stage : 'negotiation', 'status' => $status,
                'createdAt' => self::ago($created), 'lastActivityAt' => self::ago($touch),
                'expectedClose' => self::day($close), 'closedAt' => $status !== 'open' ? self::ago(-$close) : null,
                'nextStep' => $next, 'nextStepDate' => $nextIn !== null ? self::day($nextIn) : null,
                'source' => $source, 'champion' => $champion, 'lostReason' => $s[12] ?? null,
                'tags' => [],
            ];
            $n = $status === 'open' ? 2 + $i % 4 : 3;
            for ($k = 0; $k < $n; $k++) {
                $t = $types[($i + $k) % count($types)];
                $daysAgo = (int) round($touch + ($created - $touch) * ($k / max(1, $n)));
                $activities[] = [
                    'id' => 'a_' . $i . '_' . $k, 'dealId' => $id, 'userId' => $owner, 'type' => $t,
                    'text' => $texts[$t][($i + $k) % 3], 'at' => self::ago($daysAgo, 9 + ($k * 3) % 8),
                ];
            }
        }
        usort($activities, fn($a, $b) => strcmp($b['at'], $a['at']));
        $last = [];
        foreach ($activities as $a) {
            $last[$a['dealId']] ??= $a['at'];
        }
        foreach ($deals as &$d) {
            $d['lastActivityAt'] = $last[$d['id']] ?? $d['lastActivityAt'];
        }
        unset($d);

        $db->put('settings', self::settings());
        $db->put('users', $users);
        $db->put('companies', $companyRows);
        $db->put('contacts', $contactRows);
        $db->put('deals', $deals);
        $db->put('activities', $activities);
    }

    private static function ago(int $days, int $hour = 10): string
    {
        return (new \DateTimeImmutable("today -{$days} days"))->setTime($hour, 0)->format('Y-m-d\TH:i:s');
    }

    private static function day(int $offset): string
    {
        return (new \DateTimeImmutable('today'))->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d');
    }
}
