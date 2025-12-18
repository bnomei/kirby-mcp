<?php
/** @var ProductPage $page */
$product ??= $page;
?>
<article>
    <a href="<?= $product->url() ?>">
        <img src="<?= $product->gallery()->toFile()?->url() ?>" alt="">
        <?= $product->title() ?>
    </a>
    <div><?= $product->formattedPrice() ?></div>
    <?php snippet('kart/cart-buy', [
        'product' => $product,
        'redirect' => kart()->urls()->cart(), // go to cart and be ready for checkout
    ]) ?>
    <?php snippet('kart/wish-or-forget', [
        'product' => $product,
    ]) ?>
</article>