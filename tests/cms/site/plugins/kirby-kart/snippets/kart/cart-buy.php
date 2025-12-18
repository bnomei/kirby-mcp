<?php
/** @var ProductPage $page */
$product ??= $page;

if ($product->inStock()) { ?>
    <form method="POST" action="<?= $product->buy() ?>">
        <input type="hidden" name="redirect" value="<?= $redirect ?? $page->url() ?>">
        <button type="submit" onclick="this.disabled=true;this.form.submit();">Buy now</button>
    </form>
<?php } else { ?>
    <p><mark>out of stock</mark></p>
<?php }
