<!DOCTYPE html>
<html lang="<?= kirby()->language()?->code() ?? 'en' ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php snippet('seo/head'); ?>
    <style>
        @font-face {
            font-family: "Geist";
            font-style: normal;
            font-weight: 100 900;
            font-display: block;
            src: local("Geist"), url('/assets/fonts/Geist[wght].woff2') format("woff2");
        }
        body {
            overflow-y: scroll;
            font-family: "Geist", sans-serif;
            --pico-font-family-sans-serif: "Geist", sans-serif;
        }
    </style>
    <?= css('assets/css/pico.pumpkin.min.css') ?>
    <?= css('@auto') ?>
    <?= js('@auto') ?>
    <?php if (kart()->option('turnstile.sitekey')) { ?><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php } ?>
</head>
<body>
<?= $slot ?>
<?php snippet('seo/schemas'); ?>
</body>
</html>