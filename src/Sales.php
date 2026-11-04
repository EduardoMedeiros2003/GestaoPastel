<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Cálculos em cima dos negócios: nota de cada negócio, tarefas do dia e números do trimestre.
 * Roda a cada leitura, então nada disso é salvo em disco.
 */
final class Sales
{
    private DateTimeImmutable $today;
    private array $stages;
    private array $activitiesByDeal = [];

    public function __construct(array $settings, array $activities, ?DateTimeImmutable $today = null)
    {
        $this->today = $today ?? new DateTimeImmutable('today');
        $this->stages = array_column($settings['stages'], null, 'id');
        foreach ($activities as $a) {
            $this->activitiesByDeal[$a['dealId']][] = $a;
        }
    }

    /**
     * Nota de 0 a 100. Cada item da lista "reasons" diz quantos pontos somou ou tirou,
     * para o vendedor entender a nota em vez de confiar nela.
     */
    public function health(array $deal): array
    {
        if ($deal['status'] !== 'open') {
            return ['score' => $deal['status'] === 'won' ? 100 : 0, 'state' => $deal['status'], 'reasons' => [], 'idleDays' => 0, 'stale' => false];
        }

        $stage = $this->stages[$deal['stage']];
        $idle = $this->daysSince($deal['lastActivityAt'] ?? $deal['createdAt']);
        $reasons = [];

        $reasons[] = ["Etapa {$stage['name']}", (int) round($stage['probability'] * 0.35)];

        // Um próximo passo marcado para o futuro conta como movimento, mesmo sem contato recente.
        $hasFutureStep = !empty($deal['nextStepDate']) && $this->daysUntil($deal['nextStepDate']) >= 0;
        $stale = $idle > $stage['staleAfter'] && !$hasFutureStep;
        if ($stale) {
            $reasons[] = ["Sem contato há " . self::days($idle), -min(20, ($idle - $stage['staleAfter']) * 2)];
        } elseif ($idle === 0) {
            $reasons[] = ['Teve contato hoje', 25];
        } else {
            $reasons[] = ["Último contato há " . self::days($idle), (int) round(25 * max(0, 1 - $idle / ($stage['staleAfter'] * 1.5)))];
        }

        $weight = ['meeting' => 6, 'call' => 4, 'whatsapp' => 3, 'email' => 2];
        $recent = array_filter($this->activitiesByDeal[$deal['id']] ?? [], fn($a) => $this->daysSince($a['at']) <= 14);
        $points = 0;
        foreach ($recent as $a) {
            $points += $weight[$a['type']] ?? 1;
        }
        $reasons[] = [(count($recent) === 1 ? '1 contato' : count($recent) . ' contatos') . ' nas últimas 2 semanas', min(20, $points)];

        if (empty($deal['nextStep']) || empty($deal['nextStepDate'])) {
            $reasons[] = ['Sem próximo passo', -10];
        } elseif ($this->daysUntil($deal['nextStepDate']) < 0) {
            $reasons[] = ['Próximo passo atrasado', -5];
        } else {
            $reasons[] = ['Próximo passo marcado', 10];
        }

        if (!empty($deal['expectedClose'])) {
            $d = $this->daysUntil($deal['expectedClose']);
            if ($d < 0) {
                $reasons[] = ['Data de fechamento já passou', -10];
            } else {
                $reasons[] = ['Fecha ' . ($d === 0 ? 'hoje' : 'em ' . self::days($d)), $d <= 30 ? 10 : 5];
            }
        }

        if (!empty($deal['champion'])) {
            $reasons[] = ['Cliente defende a compra lá dentro', 5];
        }

        $score = max(0, min(100, array_sum(array_column($reasons, 1))));
        $state = $stale ? 'stale' : ($score >= 65 ? 'hot' : ($score >= 40 ? 'warm' : 'cold'));
        usort($reasons, fn($a, $b) => abs($b[1]) <=> abs($a[1]));

        return [
            'score' => $score,
            'state' => $state,
            'reasons' => array_map(fn($r) => ['label' => $r[0], 'pts' => $r[1]], $reasons),
            'idleDays' => $idle,
            'stale' => $stale,
        ];
    }

    /** Lista do que fazer hoje, com os negócios de maior valor esperado primeiro. */
    public function tasks(array $deals): array
    {
        $tasks = [];
        foreach ($deals as $deal) {
            if ($deal['status'] !== 'open') {
                continue;
            }
            $h = $deal['health'];
            $stage = $this->stages[$deal['stage']];
            $expected = $deal['value'] * max(0.2, $stage['probability'] / 100);

            if (!empty($deal['nextStepDate']) && $this->daysUntil($deal['nextStepDate']) <= 0) {
                $late = -$this->daysUntil($deal['nextStepDate']);
                $tasks[] = $this->task($deal, 'step', $deal['nextStep'] ?: 'Fazer o próximo passo',
                    $late ? "Era para {$this->date($deal['nextStepDate'])}" : 'Marcado para hoje', $expected * 1.6, 'call');
            }
            if ($h['stale']) {
                $tasks[] = $this->task($deal, 'followup', 'Retomar contato', 'Sem resposta há ' . self::days($h['idleDays']), $expected * 1.4, 'whatsapp');
            } elseif (empty($deal['nextStep'])) {
                $tasks[] = $this->task($deal, 'plan', 'Combinar o próximo passo', 'Ninguém sabe o que vem agora', $expected * 0.9, 'note');
            }
            if (!empty($deal['expectedClose']) && $this->daysUntil($deal['expectedClose']) < 0) {
                $tasks[] = $this->task($deal, 'date', 'Atualizar a data de fechamento',
                    "Previsto para {$this->date($deal['expectedClose'])}", $expected * 0.7, 'note');
            }
            if ($stage['probability'] >= 60 && $h['score'] >= 65 && !$h['stale']) {
                $tasks[] = $this->task($deal, 'close', 'Pedir o fechamento', "Cliente engajado e já em {$stage['name']}", $expected * 1.2, 'meeting');
            }
        }
        usort($tasks, fn($a, $b) => $b['priority'] <=> $a['priority']);
        return array_slice($tasks, 0, 8);
    }

    public function quarter(array $deals, float $goal): array
    {
        [$start, $end] = $this->quarterBounds();
        $won = 0;
        $open = 0;
        $stale = 0;
        $staleCount = 0;
        $openCount = 0;
        foreach ($deals as $d) {
            if ($d['status'] === 'won' && $this->inRange($d['closedAt'], $start, $end)) {
                $won += $d['value'];
            }
            if ($d['status'] === 'open') {
                $open += $d['value'];
                $openCount++;
                if ($d['health']['stale']) {
                    $stale += $d['value'];
                    $staleCount++;
                }
            }
        }
        return [
            'label' => ceil((int) $this->today->format('n') / 3) . 'º tri',
            'goal' => $goal,
            'won' => $won,
            'daysLeft' => (int) $this->today->diff($end)->days,
            'open' => $open,
            'openCount' => $openCount,
            'stale' => $stale,
            'staleCount' => $staleCount,
        ];
    }

    public function byPerson(array $deals, array $users, array $activities): array
    {
        [$start, $end] = $this->quarterBounds();
        $rows = [];
        foreach ($users as $u) {
            $won = array_filter($deals, fn($d) => $d['ownerId'] === $u['id'] && $d['status'] === 'won' && $this->inRange($d['closedAt'], $start, $end));
            $week = array_filter($activities, fn($a) => $a['userId'] === $u['id'] && $this->daysSince($a['at']) < 7);
            $rows[] = [
                'user' => $u,
                'won' => array_sum(array_column($won, 'value')),
                'wonCount' => count($won),
                'open' => count(array_filter($deals, fn($d) => $d['ownerId'] === $u['id'] && $d['status'] === 'open')),
                'contacts7d' => count($week),
            ];
        }
        usort($rows, fn($a, $b) => $b['won'] <=> $a['won']);
        return $rows;
    }

    public function lostReasons(array $deals): array
    {
        $out = [];
        foreach ($deals as $d) {
            if ($d['status'] === 'lost') {
                $reason = $d['lostReason'] ?: 'Não informado';
                $out[$reason] = ($out[$reason] ?? 0) + $d['value'];
            }
        }
        arsort($out);
        return $out;
    }

    public function bySource(array $deals): array
    {
        $out = [];
        foreach ($deals as $d) {
            if ($d['status'] === 'open') {
                continue;
            }
            $source = $d['source'] ?? 'Outro';
            $out[$source] ??= ['won' => 0, 'lost' => 0];
            $out[$source][$d['status']]++;
        }
        return $out;
    }

    private function task(array $deal, string $kind, string $title, string $why, float $priority, string $channel): array
    {
        return [
            'dealId' => $deal['id'], 'kind' => $kind, 'title' => $title, 'why' => $why,
            'priority' => round($priority, 2), 'channel' => $channel, 'value' => $deal['value'],
        ];
    }

    private function quarterBounds(): array
    {
        $q = (int) ceil((int) $this->today->format('n') / 3);
        $start = new DateTimeImmutable(sprintf('%s-%02d-01', $this->today->format('Y'), ($q - 1) * 3 + 1));
        return [$start, $start->modify('+3 months -1 second')];
    }

    private function inRange(?string $date, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        if (!$date) {
            return false;
        }
        $d = new DateTimeImmutable($date);
        return $d >= $start && $d <= $end;
    }

    private function daysSince(string $date): int
    {
        return (int) (new DateTimeImmutable(substr($date, 0, 10)))->diff($this->today)->format('%r%a');
    }

    private function daysUntil(string $date): int
    {
        return -$this->daysSince($date);
    }

    private static function days(int $n): string
    {
        return $n === 1 ? '1 dia' : "{$n} dias";
    }

    private function date(string $ymd): string
    {
        return (new DateTimeImmutable($ymd))->format('d/m');
    }
}
