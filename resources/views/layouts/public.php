<?php
$seo = is_array($seo ?? null) ? $seo : [];
$title = trim((string) ($seo['title'] ?? ($title ?? 'Comanda360')));
$description = trim((string) ($seo['description'] ?? ''));
$keywords = trim((string) ($seo['keywords'] ?? ''));
$canonical = trim((string) ($seo['canonical'] ?? app_url('/')));
$robots = trim((string) ($seo['robots'] ?? 'index,follow'));
$ogImage = trim((string) ($seo['og_image'] ?? asset_url('/img/logo-comanda360.png')));
$structuredData = is_array($seo['structured_data'] ?? null) ? $seo['structured_data'] : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <meta name="robots" content="<?= htmlspecialchars($robots) ?>">
    <meta name="theme-color" content="#05131f">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(asset_url('/img/comanda360.ico')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php foreach ($structuredData as $schema): ?>
        <?php if (is_array($schema)): ?>
            <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body>
<?= $content ?>
</body>
</html>
