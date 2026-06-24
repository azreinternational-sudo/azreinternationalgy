<?php
/**
 * Dynamic product image generator.
 * Returns an SVG placeholder for a product when no real image is uploaded.
 *
 * Usage: /product_image.php?name=EVEREST+5W-30&brand=Everest&cat=Motor+Oil&sku=FP53000EV012SB
 */
define('AZRE_BOOTSTRAP', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';

// Parse query params
$slug  = trim($_GET['slug']  ?? '');
$name  = trim($_GET['name']  ?? '');
$brand = trim($_GET['brand'] ?? '');
$cat   = trim($_GET['cat']   ?? 'Other');
$sku   = trim($_GET['sku']   ?? '');

// If we got a slug, look up the product so we can pull brand/cat/sku from the DB
if ($slug !== '' && function_exists('db')) {
    try {
        $stmt = db()->prepare(
            'SELECT p.name, p.brand, p.sku, c.name AS cat '
            . 'FROM products p LEFT JOIN categories c ON c.id = p.category_id '
            . 'WHERE p.slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) {
            if ($name === '')  $name  = $row['name']  ?? '';
            if ($brand === '') $brand = $row['brand'] ?? '';
            if ($cat === 'Other') $cat = $row['cat'] ?? 'Other';
            if ($sku === '')  $sku  = $row['sku']   ?? '';
        }
    } catch (Throwable $e) {
        // ignore — fall through to defaults
    }
}

// Sanitize — letters, digits, spaces, basic punctuation
$name  = mb_substr(preg_replace('/[^\pL\pN \-\/&,.()]/u', '', $name), 0, 80);
$brand = mb_substr(preg_replace('/[^\pL\pN \-\/&,.()]/u', '', $brand), 0, 40);
$cat   = mb_substr(preg_replace('/[^\pL\pN \-\/&,.()]/u', '', $cat), 0, 40);
$sku   = mb_substr(preg_replace('/[^\pL\pN\-_.]/', '', $sku), 0, 40);

// Category color palette (gradient stops)
$palette = [
    'Motor Oil'             => ['#ff8c1a', '#c45400'],
    'Heavy Duty'            => ['#d94a3a', '#7a1a1a'],
    'Transmission'          => ['#3b6cd9', '#1a2a6a'],
    'Coolant'               => ['#3acdd6', '#0a4a6a'],
    'DEF'                   => ['#5fb84f', '#1a5a1a'],
    'Additives'             => ['#8e5fd6', '#3a1a6a'],
    'Grease'                => ['#a3671a', '#3a2a0a'],
    'Chemicals'             => ['#d6c63a', '#6a5a0a'],
    'Refrigerant'           => ['#6ab9d6', '#1a4a6a'],
    'Hydraulic'             => ['#6a7a90', '#1a2a4a'],
    'Industrial'            => ['#3a8e5a', '#0a2a1a'],
    'Gear & Transmission'   => ['#a07a3a', '#4a2a0a'],
    'Other'                 => ['#4a5a7a', '#0a1a2a'],
];
$colors = $palette[$cat] ?? $palette['Other'];

// Initials: first letter of brand, or first letter of name if no brand
if ($brand !== '') {
    $parts = preg_split('/\s+/', $brand);
    $initials = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0], 1, 1)));
} else {
    $initials = strtoupper(mb_substr($name, 0, 2));
}
if ($initials === '') $initials = 'AZ';

// SVG icon per category (simple stylized shape)
$icon = match (true) {
    str_contains($cat, 'Oil')      => '<path d="M 200 80 C 165 130, 165 180, 200 220 C 235 180, 235 130, 200 80 Z M 175 220 L 175 260 Q 175 270 185 270 L 215 270 Q 225 270 225 260 L 225 220" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="3" stroke-linejoin="round"/>',
    str_contains($cat, 'Coolant') || str_contains($cat, 'DEF') => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><path d="M 200 80 L 200 240"/><circle cx="200" cy="80" r="6"/><path d="M 170 110 Q 200 130 230 110"/><path d="M 165 140 Q 200 165 235 140"/></g>',
    str_contains($cat, 'Heavy')    => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><circle cx="200" cy="160" r="70"/><circle cx="200" cy="160" r="20"/><line x1="200" y1="90" x2="200" y2="50"/><line x1="200" y1="230" x2="200" y2="270"/><line x1="130" y1="160" x2="100" y2="160"/><line x1="270" y1="160" x2="300" y2="160"/></g>',
    str_contains($cat, 'Trans')    => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><circle cx="200" cy="160" r="60"/><circle cx="200" cy="160" r="10" fill="rgba(255,255,255,0.35)"/><line x1="200" y1="100" x2="200" y2="80"/><line x1="200" y1="220" x2="200" y2="240"/><line x1="140" y1="160" x2="120" y2="160"/><line x1="260" y1="160" x2="280" y2="160"/></g>',
    str_contains($cat, 'Gear')     => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><circle cx="200" cy="160" r="60"/><circle cx="200" cy="160" r="15"/><line x1="200" y1="80" x2="200" y2="100"/><line x1="200" y1="220" x2="200" y2="240"/><line x1="120" y1="160" x2="140" y2="160"/><line x1="260" y1="160" x2="280" y2="160"/><line x1="145" y1="105" x2="160" y2="120"/><line x1="240" y1="200" x2="255" y2="215"/><line x1="145" y1="215" x2="160" y2="200"/><line x1="240" y1="120" x2="255" y2="105"/></g>',
    str_contains($cat, 'Hydraulic') => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><circle cx="200" cy="160" r="60"/><path d="M 175 130 L 200 100 L 225 130 L 225 220 L 175 220 Z"/><line x1="200" y1="100" x2="200" y2="220"/></g>',
    str_contains($cat, 'Refrigerant') || str_contains($cat, 'Freon') => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><circle cx="200" cy="160" r="50"/><path d="M 200 110 L 220 150 L 180 150 Z M 200 210 L 180 170 L 220 170 Z"/></g>',
    str_contains($cat, 'Grease')   => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><rect x="160" y="120" width="80" height="160" rx="6"/><line x1="160" y1="160" x2="240" y2="160"/><line x1="160" y1="200" x2="240" y2="200"/><line x1="160" y1="240" x2="240" y2="240"/></g>',
    str_contains($cat, 'Chemical') => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><path d="M 200 90 L 240 200 L 160 200 Z"/><circle cx="200" cy="170" r="5"/></g>',
    default                     => '<g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5"><circle cx="200" cy="160" r="60"/><circle cx="200" cy="160" r="20"/></g>',
};

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');
?><?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" role="img" aria-label="<?= htmlspecialchars($name ?: 'Product', ENT_QUOTES) ?>">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="<?= $colors[0] ?>"/>
      <stop offset="1" stop-color="<?= $colors[1] ?>"/>
    </linearGradient>
    <linearGradient id="shine" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="rgba(255,255,255,0.18)"/>
      <stop offset="1" stop-color="rgba(255,255,255,0)"/>
    </linearGradient>
    <pattern id="dot" width="20" height="20" patternUnits="userSpaceOnUse">
      <circle cx="10" cy="10" r="0.8" fill="rgba(255,255,255,0.10)"/>
    </pattern>
  </defs>

  <!-- Background -->
  <rect width="400" height="300" fill="url(#bg)"/>
  <rect width="400" height="300" fill="url(#dot)"/>
  <rect width="400" height="160" fill="url(#shine)"/>

  <!-- Category icon (subtle, large) -->
  <g transform="translate(0 -10)" opacity="0.85"><?= $icon ?></g>

  <!-- Large brand initials -->
  <text x="200" y="195" text-anchor="middle" fill="#fff" font-family="Inter, system-ui, sans-serif" font-size="68" font-weight="800" letter-spacing="-2"><?= htmlspecialchars($initials, ENT_QUOTES) ?></text>

  <!-- Bottom label band -->
  <rect x="0" y="252" width="400" height="48" fill="rgba(0,0,0,0.35)"/>
  <text x="200" y="276" text-anchor="middle" fill="#fff" font-family="Inter, sans-serif" font-size="14" font-weight="700"><?= htmlspecialchars(strtoupper($brand ?: 'AZRE'), ENT_QUOTES) ?></text>
  <text x="200" y="293" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-family="Inter, sans-serif" font-size="10" font-weight="500"><?= htmlspecialchars(mb_strtoupper($cat), ENT_QUOTES) ?></text>

  <!-- Top-right small SKU badge (if provided) -->
  <?php if ($sku !== ''): ?>
  <g>
    <rect x="296" y="14" width="92" height="22" rx="11" fill="rgba(0,0,0,0.4)"/>
    <text x="342" y="29" text-anchor="middle" fill="rgba(255,255,255,0.85)" font-family="ui-monospace, SFMono-Regular, Menlo, monospace" font-size="9" font-weight="600"><?= htmlspecialchars($sku, ENT_QUOTES) ?></text>
  </g>
  <?php endif; ?>
</svg>
