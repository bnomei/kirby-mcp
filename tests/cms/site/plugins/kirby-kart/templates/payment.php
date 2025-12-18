<?php
snippet('kart/kart', slots: true);
// Unless you want to create a custom checkout view you can ignore this template.
// I used it in the online and localhost demo for the fake payment provider.
?>

<main>
    <nav>
        <a href="<?= \Bnomei\Kart\Router::get('cancel_url') ?>">Back</a>
    </nav>

    <h1>Fake Payment Provider</h1>

    <form method="POST" action="<?= \Bnomei\Kart\Router::get('success_url') ?>">
        <?php // TODO: You should add an invisible CAPTCHA here, like...?>
        <?php // snippet('kart/turnstile-form')?>
        <label>
            <input type="email" name="email" placeholder="E-Mail (provide for account creation)"
                   autocomplete="email" style="min-width: 42ch;"
                   value="<?= kirby()->user()?->email() ?>"
                   <?= kirby()->user() ? 'readonly' : '' ?>
            />
        </label>
        <button type="submit" onclick="this.disabled=true;this.form.submit();">Pay</button>
    </form>
</main>
