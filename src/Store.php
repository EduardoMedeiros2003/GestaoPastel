<?php
declare(strict_types=1);

namespace App;

/**
 * Guarda cada coleção num arquivo PHP que só devolve um array (data/deals.php, data/activities.php...).
 * Gravação: var_export num arquivo temporário e rename, com flock em data/trava.php,
 * para duas requisições ao mesmo tempo não corromperem o arquivo.
 * Se alguém abrir data/*.php pelo navegador, não aparece nada: o arquivo só faz "return".
 */
final class Store
{
    public function __construct(private string $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $lock = $dir . '/trava.php';
        if (!is_file($lock)) {
            file_put_contents($lock, "<?php\n// Arquivo usado só para travar a gravação.\n");
        }
    }

    public function exists(string $collection): bool
    {
        return is_file($this->path($collection));
    }

    public function all(string $collection): array
    {
        $file = $this->path($collection);
        if (!is_file($file)) {
            return [];
        }
        return $this->withLock(LOCK_SH, fn() => $this->read($file));
    }

    public function find(string $collection, string $id): ?array
    {
        foreach ($this->all($collection) as $row) {
            if (($row['id'] ?? null) === $id) {
                return $row;
            }
        }
        return null;
    }

    public function put(string $collection, array $data): void
    {
        $this->withLock(LOCK_EX, fn() => $this->write($this->path($collection), $data));
    }

    /** Lê, deixa $fn alterar as linhas e grava, tudo com a trava exclusiva. */
    public function mutate(string $collection, callable $fn): mixed
    {
        $file = $this->path($collection);
        return $this->withLock(LOCK_EX, function () use ($file, $fn) {
            $rows = is_file($file) ? $this->read($file) : [];
            $result = $fn($rows);
            $this->write($file, $rows);
            return $result;
        });
    }

    public function insert(string $collection, array $row): array
    {
        $this->mutate($collection, function (array &$rows) use ($row) {
            $rows[] = $row;
        });
        return $row;
    }

    public function update(string $collection, string $id, array $patch): ?array
    {
        return $this->mutate($collection, function (array &$rows) use ($id, $patch) {
            foreach ($rows as &$row) {
                if ($row['id'] === $id) {
                    $row = array_merge($row, $patch);
                    return $row;
                }
            }
            return null;
        });
    }

    public function delete(string $collection, string $id): bool
    {
        return $this->mutate($collection, function (array &$rows) use ($id) {
            $before = count($rows);
            $rows = array_values(array_filter($rows, fn($r) => $r['id'] !== $id));
            return count($rows) < $before;
        });
    }

    public static function id(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(5));
    }

    private function read(string $file): array
    {
        // Sem isso o OPcache pode devolver a versão antiga do arquivo.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
        $data = include $file;
        return is_array($data) ? $data : [];
    }

    private function write(string $file, array $data): void
    {
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp.php';
        file_put_contents($tmp, "<?php\n// Gerado pelo sistema. Não edite com o sistema aberto.\nreturn " . var_export($data, true) . ";\n");
        rename($tmp, $file);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }

    private function withLock(int $mode, callable $fn): mixed
    {
        $fh = fopen($this->dir . '/trava.php', 'r');
        flock($fh, $mode);
        try {
            return $fn();
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private function path(string $collection): string
    {
        if (!preg_match('/^[a-z]+$/', $collection)) {
            throw new \InvalidArgumentException('Coleção inválida');
        }
        return $this->dir . '/' . $collection . '.php';
    }
}
