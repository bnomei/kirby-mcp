<?php
/** @var ProductPage $product */
$product ??= $page;
?>
<?php if (! kart()->wishlist()->has($product)) { ?>
    <button hx-post="<?= $product->wish() ?>" hx-disabled-elt="this" hx-swap="outerHTML" title="add to wishlist">Add to wishlist</button>
<?php } else { ?>
    <button hx-post="<?= $product->forget() ?>" hx-disabled-elt="this" hx-swap="outerHTML" title="remove from wishlist">Remove from wishlist</button>
<?php }
