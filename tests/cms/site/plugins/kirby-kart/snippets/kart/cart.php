<fieldset>
    <legend>Cart (<?= kart()->cart()->quantity() ?>)</legend>
    <menu>
        <?php foreach (kart()->cart()->lines() as $line) {
            /** @var CartLine $line */
            /** @var ProductPage $product */
            $product = $line->product(); ?>
            <li>
                <a href="<?= $product->url() ?>"><?= $product->title() ?></a>
                <?php if ($line->hasStockForQuantity() === false) { ?>
                    <span><?= $product->stock(withHold: true) ?> of <?= $line->quantity() ?>x</span>
                <?php } else { ?>
                    <span><?= $line->quantity() ?>x</span>
                <?php } ?>
                <span><?= $line->formattedPrice() ?></span>
                <?php /* <span><strong><?= $line->formattedSubtotal() ?></strong></span> */ ?>
                <div>
                    <form method="POST" action="<?= $product->add() ?>">
                        <button type="submit" onclick="this.disabled=true;this.form.submit();">+</button>
                    </form>
                    <form method="POST" action="<?= $product->remove() ?>">
                        <button onclick="this.disabled=true;this.form.submit();" type="submit">–</button>
                    </form>
                    <form method="POST" action="<?= $product->later() ?>">
                        <button onclick="this.disabled=true;this.form.submit();" type="submit">⊻</button>
                    </form>
                </div>
            </li>
        <?php } ?>
    </menu>
    <hr>
    <p><strong><?= kart()->cart()->formattedSubtotal() ?> +tax</strong></p>
    <form method="POST" action="<?= kart()->urls()->cart_checkout() ?>">
        <?php // TODO: You should add an invisible CAPTCHA here, like...?>
        <?php // snippet('kart/turnstile-form')?>
        <input type="hidden" name="redirect" value="<?= $page?->url() ?>">
        <button type="submit" onclick="this.disabled=true;this.form.submit();" <?= kart()->cart()->canCheckout() === false ? 'disabled' : '' ?>>Checkout</button>
    </form>
</fieldset>
