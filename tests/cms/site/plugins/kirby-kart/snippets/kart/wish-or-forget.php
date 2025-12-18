<?php
/** @var ProductPage $page */
$product ??= $page;

if (kart()->wishlist()->has($product) === false) { ?>
    <form method="POST" action="<?= $product->wish() ?>">
        <button type="submit" onclick="this.disabled=true;this.form.submit();">Add to wishlist</button>
    </form>
<?php } else { ?>
    <form method="POST" action="<?= $product->forget() ?>">
        <button type="submit" onclick="this.disabled=true;this.form.submit();">Remove from wishlist</button>
    </form>
<?php }
