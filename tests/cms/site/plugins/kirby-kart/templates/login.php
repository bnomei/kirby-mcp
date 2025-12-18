<?php
snippet('kart/kart', slots: true);
// COPY and modify the code below this line --------
?>

<main>
    <?php if (kirby()->user()?->isCustomer()) {
        snippet('kart/profile');
    } else {
        snippet('kart/login-magic'); ?>
        <br>or <a href="<?= url('kart/signup') ?>">sign up</a>
    <?php } ?>
</main>
