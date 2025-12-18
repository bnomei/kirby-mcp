<form method="POST" action="<?= kart()->urls()->logout() ?>">
    <input type="hidden" name="redirect" value="<?= url('kart') ?>">
    <button type="submit" onclick="this.disabled=true;this.form.submit();">Logout</button>
</form>
