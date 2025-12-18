<?php if ($sitekey = kart()->option('turnstile.sitekey')) { ?>
<div class="cf-turnstile" data-sitekey="<?= $sitekey ?>"></div>
<?php } ?>
