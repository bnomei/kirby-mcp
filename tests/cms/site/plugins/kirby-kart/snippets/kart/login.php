<form method="POST" action="<?= kart()->urls()->login() ?>">
    <label>
        <input type="email" name="email" required
                  placeholder="<?= t('email') ?>" autocomplete="email"
                  value="<?= urldecode((string) get('email', '')) ?>">
    </label>
    <label>
        <input type="password" name="password" required
               placeholder="<?= t('password') ?>" autocomplete="off">
    </label>
    <?php // TODO: You should add an invisible CAPTCHA here, like...?>
    <?php // snippet('kart/turnstile-form')?>
    <input type="hidden" name="redirect" value="<?= $page?->url() ?>">
    <button type="submit" onclick="this.disabled=true;this.form.submit();"><?= t('login') ?></button>
</form>
