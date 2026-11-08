<?php
/**
 * Generator QR Code sederhana (PHP murni, tanpa library).
 * Mode byte, error correction level L, versi 1-5 (maks. 106 karakter), mask 0.
 * Hasil: matriks 0/1 atau SVG.
 */
final class QR
{
    // [jumlah data codeword, jumlah error-correction codeword] per versi (level L, 1 blok)
    private const VER = [1 => [19, 7], 2 => [34, 10], 3 => [55, 15], 4 => [80, 20], 5 => [108, 26]];
    private const ALIGN = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30]];

    private static array $exp = [];
    private static array $log = [];

    private static function initGf(): void
    {
        if (self::$exp) {
            return;
        }
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11d;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }
        self::$exp = $exp;
        self::$log = $log;
    }

    private static function gmul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    private static function rsEc(array $data, int $n): array
    {
        $g = [1];
        for ($i = 0; $i < $n; $i++) {
            $q = array_fill(0, count($g) + 1, 0);
            foreach ($g as $j => $c) {
                $q[$j] ^= $c;
                $q[$j + 1] ^= self::gmul($c, self::$exp[$i]);
            }
            $g = $q;
        }
        $len = count($data);
        $r = array_merge($data, array_fill(0, $n, 0));
        for ($i = 0; $i < $len; $i++) {
            $c = $r[$i];
            if ($c !== 0) {
                for ($j = 1, $m = count($g); $j < $m; $j++) {
                    $r[$i + $j] ^= self::gmul($g[$j], $c);
                }
            }
        }
        return array_slice($r, $len);
    }

    /** @return int[][] matriks [baris][kolom], 1 = gelap */
    public static function matrix(string $text): array
    {
        self::initGf();
        $bytes = array_values(unpack('C*', $text) ?: []);
        $version = 0;
        for ($v = 1; $v <= 5; $v++) {
            if (count($bytes) <= self::VER[$v][0] - 2) {
                $version = $v;
                break;
            }
        }
        if ($version === 0) {
            throw new InvalidArgumentException('Teks QR terlalu panjang (maks. 106 karakter).');
        }
        [$dcw, $ecw] = self::VER[$version];

        // --- susun bit data
        $bits = [];
        $put = function (int $val, int $n) use (&$bits): void {
            for ($i = $n - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };
        $put(4, 4);
        $put(count($bytes), 8);
        foreach ($bytes as $b) {
            $put($b, 8);
        }
        $cap = $dcw * 8;
        $put(0, min(4, $cap - count($bits)));
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $pad = [0xEC, 0x11];
        for ($k = 0; count($bits) < $cap; $k++) {
            $put($pad[$k % 2], 8);
        }
        $data = [];
        for ($i = 0, $n = count($bits); $i < $n; $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $bits[$i + $j];
            }
            $data[] = $byte;
        }
        $cw = array_merge($data, self::rsEc($data, $ecw));

        // --- matriks + function patterns
        $size = 17 + 4 * $version;
        $m = array_fill(0, $size, array_fill(0, $size, 0));
        $f = array_fill(0, $size, array_fill(0, $size, false));
        $set = function (int $x, int $y, $dark) use (&$m, &$f): void {
            $m[$y][$x] = $dark ? 1 : 0;
            $f[$y][$x] = true;
        };
        $finder = function (int $cx, int $cy) use ($size, $set): void {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $xx = $cx + $dx;
                    $yy = $cy + $dy;
                    if ($xx >= 0 && $xx < $size && $yy >= 0 && $yy < $size) {
                        $dist = max(abs($dx), abs($dy));
                        $set($xx, $yy, $dist !== 2 && $dist !== 4);
                    }
                }
            }
        };
        $finder(3, 3);
        $finder($size - 4, 3);
        $finder(3, $size - 4);
        for ($i = 0; $i < $size; $i++) {
            if (!$f[6][$i]) {
                $set($i, 6, $i % 2 === 0);
            }
            if (!$f[$i][6]) {
                $set(6, $i, $i % 2 === 0);
            }
        }
        $ap = self::ALIGN[$version];
        $last = $ap ? $ap[count($ap) - 1] : 0;
        foreach ($ap as $cy) {
            foreach ($ap as $cx) {
                if (($cx === 6 && $cy === 6) || ($cx === 6 && $cy === $last) || ($cx === $last && $cy === 6)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $set($cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }

        // --- format info (level L, mask 0)
        $d = 8;
        $rem = $d;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $fb = (($d << 10) | $rem) ^ 0x5412;
        $g = fn(int $i): int => ($fb >> $i) & 1;
        for ($i = 0; $i < 6; $i++) {
            $set(8, $i, $g($i));
        }
        $set(8, 7, $g(6));
        $set(8, 8, $g(7));
        $set(7, 8, $g(8));
        for ($i = 9; $i < 15; $i++) {
            $set(14 - $i, 8, $g($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $set($size - 1 - $i, 8, $g($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $set(8, $size - 15 + $i, $g($i));
        }
        $set(8, $size - 8, 1);

        // --- tempatkan data (zigzag) + mask 0
        $all = [];
        foreach ($cw as $c) {
            for ($i = 7; $i >= 0; $i--) {
                $all[] = ($c >> $i) & 1;
            }
        }
        $idx = 0;
        $total = count($all);
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $xx = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $yy = $upward ? ($size - 1 - $vert) : $vert;
                    if (!$f[$yy][$xx]) {
                        $bit = $idx < $total ? $all[$idx] : 0;
                        $idx++;
                        if (($xx + $yy) % 2 === 0) {
                            $bit ^= 1;
                        }
                        $m[$yy][$xx] = $bit;
                    }
                }
            }
        }
        return $m;
    }

    /** SVG responsif (tanpa quiet zone; atur padding lewat CSS). */
    public static function svg(string $text, string $color = '#21324F'): string
    {
        $m = self::matrix($text);
        $n = count($m);
        $path = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($m[$y][$x]) {
                    $path .= 'M' . $x . ' ' . $y . 'h1v1h-1z';
                }
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $n . ' ' . $n . '" shape-rendering="crispEdges" role="img" aria-label="Kode QRIS">'
            . '<path fill="' . htmlspecialchars($color, ENT_QUOTES) . '" d="' . $path . '"/></svg>';
    }
}
