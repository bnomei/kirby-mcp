<?php

snippet('kart/kart', slots: true);
// COPY and modify the code below this line --------

/** @var ProductPage $page */
$product ??= $page;
?>

<main>
    <article>
        <img src="<?= $product->gallery()->toFile()?->url() ?>" alt="">
        <h1><?= $product->title() ?></h1>
        <?= $product->description()->kirbytext() ?>
        <div><?= $product->formattedPrice() ?></div>
        <?php snippet('kart/cart-add') ?>
        <?php snippet('kart/wish-or-forget') ?>
    </article>
</main>

<aside>
    <?php snippet('kart/profile') ?>
    <?php snippet('kart/cart') ?>
    <?php snippet('kart/wishlist') ?>
</aside>

<?php snippet('kart/product-json-ld') ?>
