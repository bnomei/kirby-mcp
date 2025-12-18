<?php if (option('bnomei.kart.provider') === 'paddle') { ?>
    <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
    <script type="text/javascript">
        Paddle.Initialize({
            token: '<?= kart()->provider()->option('public_token') ?>',
            pwCustomer: {
                <?php if ($customerId = kart()->provider()->userData('customerId')) { ?>
                id: '<?= $customerId ?>'
                <?php } ?>
            },
            checkout: {
                settings:{
                    locale: '<?= kirby()->language()?->code() ?? 'en' ?>',
                    successUrl: '<?= url(\Bnomei\Kart\Router::PROVIDER_SUCCESS).'?session_id='.get('_ptxn') ?>',
                }
            }
        });
        <?php if (kirby()->environment()->isLocal()) { ?>
        Paddle.Environment.set("sandbox");
        <?php } ?>
    </script>
<?php }
