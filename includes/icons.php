<?php
/** Ikon SVG inline (stroke mengikuti warna teks / currentColor). */
function icon(string $name, int $size = 24, string $class = ''): string
{
    $p = [
        'store'   => '<path d="M5 4h14l2 6c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3z"/><path d="M5 13v8h14v-8"/><path d="M10 21v-5h4v5"/>',
        'cart'    => '<path d="M2 3h3l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.7a1.5 1.5 0 0 0 1.5-1.1L21 7H6"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17.5" cy="20" r="1.2"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'minus'   => '<path d="M6 12h12"/>',
        'plus'    => '<path d="M12 6v12M6 12h12"/>',
        'trash'   => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>',
        'back'    => '<path d="M20 12H4"/><path d="m10 6-6 6 6 6"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'qr'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20h1"/>',
    ];
    $inner = $p[$name] ?? '';
    return '<svg class="icon ' . e($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}
