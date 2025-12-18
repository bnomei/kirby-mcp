<?php

use Bnomei\Kart\Router;

if (option('bnomei.kart.provider') === 'sumup') { ?>
    <div id="sumup-card"></div>
    <script type="text/javascript" src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>
    <script type="text/javascript">
        SumUpCard.mount({
            id: 'sumup-card',
            checkoutId: '<?= kirby()->session()->get('bnomei.kart.sumup.session_id') ?>',
            onResponse: function (type, body) {
                if(type === 'success') {
                    window.location = '<?= url(Router::PROVIDER_SUCCESS) ?>';
                }
                // console.log('Type', type);
                // console.log('Body', body);
            },
        });
    </script>
<?php }
