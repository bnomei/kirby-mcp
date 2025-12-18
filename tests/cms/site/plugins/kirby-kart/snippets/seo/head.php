<title><?= page()->isHomePage() ? site()->title() : page()->title().' | '.site()->title() ?></title>
<meta name="description" content="<?= Str::esc(page()->description()->kti()) ?>">