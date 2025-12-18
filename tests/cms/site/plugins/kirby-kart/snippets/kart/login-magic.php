<?php if (get('status') == 'sent') { ?>
    <p><i>Login link was sent. Check your inbox.</i></p>
<?php } else { ?>
    <form action="<?= kart()->urls()->magiclink() ?>" method="POST">
        <label>
            <input type="email" name="email" required
                      placeholder="<?= t('email') ?>" autocomplete="email"
                      value="<?= urldecode((string) get('email', '')) ?>">
        </label>
        <?php // TODO: You should add an CAPTCHA here, like...?>
        <?php // snippet('kart/turnstile-form') // or?>
        <?php snippet('kart/captcha')  ?>
        <input type="hidden" name="redirect" value="<?= url('kart/login') ?>?status=sent">
        <input type="hidden" name="success_url" value="<?= url('kart') ?>?msg=Welcome%20back">
        <button type="submit"><?= t('login') ?> <?= t('link') ?></button>
    </form>
<?php } ?>
