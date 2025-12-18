<fieldset>
    <legend><?= t('view.account') ?></legend>
<?php if ($user = kirby()->user()) { ?>
    <figure>
        <img src="<?= $user->gravatar(48 * 2) ?>" alt="">
        <figcaption><?= $user->nameOrEmail() ?></figcaption>
    </figure>
    <?php snippet('kart/logout') ?>
<?php } else {
        snippet('kart/login-magic'); ?>
    <br>or <a href="<?= url('kart/signup') ?>">sign up</a>
<?php } ?>
</fieldset>
