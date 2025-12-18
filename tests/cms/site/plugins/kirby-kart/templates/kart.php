<?php
snippet('kart/kart', slots: true);
// COPY and modify the code below this line --------
?>

<main>
    <output>
        <?php foreach (kart()->products()->random(min(kart()->products()->count(), 4)) as $product) {
            snippet('kart/product-card', ['product' => $product]);
        } ?>
    </output>
</main>
