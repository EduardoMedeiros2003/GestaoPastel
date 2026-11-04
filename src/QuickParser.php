<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Lê a frase digitada na busca e separa os campos do negócio.
 *
 *   "Colégio Aurora 800 pastéis 9,6k orçamento sexta via whatsapp #festa"
 *   cliente Colégio Aurora, 800 pastéis, R$ 9.600, etapa Orçamento,
 *   fecha na próxima sexta, origem WhatsApp, etiqueta festa
 */
final class QuickParser
{
    private const WEEKDAYS = ['domingo' => 0, 'segunda' => 1, 'terca' => 2, 'terça' => 2, 'quarta' => 3,
        'quinta' => 4, 'sexta' => 5, 'sabado' => 6, 'sábado' => 6];

    private const SOURCES = ['indicacao' => 'Indicação', 'indicação' => 'Indicação', 'inbound' => 'Inbound',
        'outbound' => 'Outbound', 'linkedin' => 'LinkedIn', 'evento' => 'Evento', 'site' => 'Google',
        'instagram' => 'Instagram', 'insta' => 'Instagram', 'whatsapp' => 'WhatsApp', 'zap' => 'WhatsApp',
        'balcao' => 'Balcão', 'balcão' => 'Balcão', 'feira' => 'Feira', 'google' => 'Google'];

    public function __construct(private array $stages, private ?DateTimeImmutable $now = null)
    {
        $this->now ??= new DateTimeImmutable('today');
    }

    public function parse(string $text): array
    {
        $rest = ' ' . trim(preg_replace('/\s+/u', ' ', $text)) . ' ';
        $out = ['value' => null, 'stage' => null, 'expectedClose' => null, 'source' => null, 'tags' => [], 'company' => null, 'quantity' => null];

        // Quantidade sai primeiro, senão "800 pastéis" vira R$ 800.
        if (preg_match('/\s(\d{1,3}(?:\.\d{3})*|\d+)\s?(past[eé]is|pastel|pastéis|unidades|un|pessoas|convidados)(?=\s)/iu', $rest, $m)) {
            $n = (int) str_replace('.', '', $m[1]);
            $unit = match (true) {
                (bool) preg_match('/^(pessoas|convidados)$/iu', $m[2]) => mb_strtolower($m[2]),
                (bool) preg_match('/^(unidades|un)$/iu', $m[2]) => 'unidades',
                default => $n === 1 ? 'pastel' : 'pastéis',
            };
            $out['quantity'] = ['n' => $n, 'unit' => $unit];
            $rest = str_replace($m[0], ' ', $rest);
        }

        $suffix = '(k|mil|mi|m|milh(?:ão|ao|ões|oes))';
        $found = preg_match('/\sr\$\s?(\d+(?:[.,]\d+)*)\s?' . $suffix . '?(?=\s)/iu', $rest, $m)   // R$ 9.600 | R$ 9,6k
            || preg_match('/\s(\d+(?:[.,]\d+)*)\s?' . $suffix . '(?=\s)/iu', $rest, $m)                // 9,6k | 80 mil
            || preg_match('/\s(\d+(?:[.,]\d+)*)(?=\s)/u', $rest, $m);                                 // 9.600
        if ($found) {
            $num = $m[1];
            $mult = match (mb_strtolower($m[2] ?? '')) {
                'k', 'mil' => 1_000,
                'mi', 'm', 'milhão', 'milhao', 'milhões', 'milhoes' => 1_000_000,
                default => 1,
            };
            if ($mult === 1) {
                $num = str_replace(['.', ','], ['', '.'], $num); // 45.000,50 → 45000.50
            } else {
                $num = str_replace(',', '.', $num);               // 1,5k → 1.5
            }
            if ($mult > 1 || (float) $num >= 100) {
                $out['value'] = (float) $num * $mult;
                $rest = str_replace($m[0], ' ', $rest);
            }
        }

        if (preg_match_all('/\s#([\p{L}\d_-]+)/u', $rest, $m)) {
            $out['tags'] = $m[1];
            $rest = preg_replace('/\s#[\p{L}\d_-]+/u', ' ', $rest);
        }

        foreach (self::SOURCES as $key => $label) {
            if (preg_match('/\s(?:via\s)?' . preg_quote($key, '/') . '(?=\s)/iu', $rest, $m)) {
                $out['source'] = $label;
                $rest = str_replace($m[0], ' ', $rest);
                break;
            }
        }

        foreach ($this->stages as $s) {
            foreach (array_merge([$s['name']], $s['aliases'] ?? []) as $alias) {
                if (preg_match('/\s' . preg_quote(mb_strtolower($alias), '/') . '(?=\s)/iu', mb_strtolower($rest), $m)) {
                    $out['stage'] = $s['id'];
                    $rest = preg_replace('/\s' . preg_quote($alias, '/') . '(?=\s)/iu', ' ', $rest, 1);
                    break 2;
                }
            }
        }

        $rest = $this->extractDate($rest, $out);

        // O que sobrar é o nome do cliente.
        $out['company'] = trim(preg_replace('/\s+(para|pra|com|de|em)\s*$/iu', '', trim($rest))) ?: null;
        return $out;
    }

    private function extractDate(string $rest, array &$out): string
    {
        $now = $this->now;
        $patterns = [
            '/\s(?:at[ée]\s)?hoje(?=\s)/iu' => fn() => $now,
            '/\s(?:at[ée]\s)?amanh[ãa](?=\s)/iu' => fn() => $now->modify('+1 day'),
            '/\s(?:at[ée]\s)?fim do m[êe]s(?=\s)/iu' => fn() => $now->modify('last day of this month'),
            '/\s(?:at[ée]\s)?fim do trimestre(?=\s)/iu' => fn() => $this->quarterEnd(),
            '/\sem\s(\d+)\s(dia|dias|semana|semanas|m[êe]s|meses)(?=\s)/iu' => function ($m) use ($now) {
                $unit = str_starts_with(mb_strtolower($m[2]), 'sem') ? 'week' : (str_starts_with(mb_strtolower($m[2]), 'dia') ? 'day' : 'month');
                return $now->modify("+{$m[1]} {$unit}");
            },
            '/\s(?:at[ée]\s|dia\s)?(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?(?=\s)/u' => function ($m) use ($now) {
                $y = isset($m[3]) ? (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3]) : $now->format('Y');
                $d = DateTimeImmutable::createFromFormat('Y-n-j', "{$y}-{$m[2]}-{$m[1]}");
                return $d && $d < $now && !isset($m[3]) ? $d->modify('+1 year') : $d;
            },
        ];
        foreach (self::WEEKDAYS as $name => $dow) {
            $patterns['/\s(?:at[ée]\s|na\s|no\s)?' . $name . '(?:-feira)?(?=\s)/iu'] = function () use ($now, $dow) {
                $diff = ($dow - (int) $now->format('w') + 7) % 7 ?: 7;
                return $now->modify("+{$diff} day");
            };
        }
        foreach ($patterns as $re => $fn) {
            if (preg_match($re, $rest, $m)) {
                $date = $fn($m);
                if ($date) {
                    $out['expectedClose'] = $date->format('Y-m-d');
                    return str_replace($m[0], ' ', $rest);
                }
            }
        }
        return $rest;
    }

    private function quarterEnd(): DateTimeImmutable
    {
        $q = (int) ceil((int) $this->now->format('n') / 3);
        return new DateTimeImmutable($this->now->format('Y') . '-' . ($q * 3) . '-01 last day of this month');
    }
}
