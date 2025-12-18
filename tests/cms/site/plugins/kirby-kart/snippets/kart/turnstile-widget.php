<?php if ($sitekey = kart()->option('turnstile.sitekey')) { ?>
<div class="cf-turnstile" data-sitekey="<?= $sitekey ?>" data-callback="javascriptCallback"></div>
<?php } ?>
