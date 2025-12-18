<?php if (get('status') == 'sent') { ?>
    <p>Signup link was sent. Check your inbox.</p>
<?php } else { ?>
    <form action="<?= kart()->urls()->signup_magic() ?>" method="POST">
        <label>
            <input type="email" name="email" required
               placeholder="<?= t('email') ?>" autocomplete="email"
               value="<?= urldecode((string) get('email', '')) ?>">
        </label>
        <label>
            <input type="text" name="name" required
               placeholder="<?= t('name') ?>" autocomplete="name"
               value="<?= get('name') ?>">
        </label>
        <?php // TODO: You should add a CAPTCHA here, like...?>
        <?php // snippet('kart/turnstile-form') // or?>
        <?php snippet('kart/captcha') ?>
        <input type="hidden" name="redirect" value="<?= url('kart/signup') ?>?status=sent">
        <input type="hidden" name="success_url" value="<?= url('kart') ?>?msg=Welcome">
        <button type="submit">Sign up with magic link</button>
    </form>
<?php }
