<?php use Bnomei\Kart\Router;

if (option('bnomei.kart.provider') === 'snipcart') { ?>
    <script type="text/javascript" defer>
        window.SnipcartSettings = {
            publicApiKey: '<?= kart()->provider()->option('public_key') ?>',
            loadStrategy: 'manual',
            currency: '<?= kart()->currency() ?>',
            // modalStyle: 'full',
            addProductBehavior: 'none',
        };

        (()=>{var c,d;(d=(c=window.SnipcartSettings).version)!=null||(c.version="3.0");var s,S;(S=(s=window.SnipcartSettings).timeoutDuration)!=null||(s.timeoutDuration=2750);var l,p;(p=(l=window.SnipcartSettings).domain)!=null||(l.domain="cdn.snipcart.com");var w,u;(u=(w=window.SnipcartSettings).protocol)!=null||(w.protocol="https");var f=window.SnipcartSettings.version.includes("v3.0.0-ci")||window.SnipcartSettings.version!="3.0"&&window.SnipcartSettings.version.localeCompare("3.4.0",void 0,{numeric:!0,sensitivity:"base"})===-1,m=["focus","mouseover","touchmove","scroll","keydown"];window.LoadSnipcart=o;document.readyState==="loading"?document.addEventListener("DOMContentLoaded",r):r();function r(){window.SnipcartSettings.loadStrategy?window.SnipcartSettings.loadStrategy==="on-user-interaction"&&(m.forEach(t=>document.addEventListener(t,o)),setTimeout(o,window.SnipcartSettings.timeoutDuration)):o()}var a=!1;function o(){if(a)return;a=!0;let t=document.getElementsByTagName("head")[0],e=document.querySelector("#snipcart"),i=document.querySelector(`src[src^="${window.SnipcartSettings.protocol}://${window.SnipcartSettings.domain}"][src$="snipcart.js"]`),n=document.querySelector(`link[href^="${window.SnipcartSettings.protocol}://${window.SnipcartSettings.domain}"][href$="snipcart.css"]`);e||(e=document.createElement("div"),e.id="snipcart",e.setAttribute("hidden","true"),document.body.appendChild(e)),v(e),i||(i=document.createElement("script"),i.src=`${window.SnipcartSettings.protocol}://${window.SnipcartSettings.domain}/themes/v${window.SnipcartSettings.version}/default/snipcart.js`,i.async=!0,t.appendChild(i)),n||(n=document.createElement("link"),n.rel="stylesheet",n.type="text/css",n.href=`${window.SnipcartSettings.protocol}://${window.SnipcartSettings.domain}/themes/v${window.SnipcartSettings.version}/default/snipcart.css`,t.prepend(n)),m.forEach(g=>document.removeEventListener(g,o))}function v(t){!f||(t.dataset.apiKey=window.SnipcartSettings.publicApiKey,window.SnipcartSettings.addProductBehavior&&(t.dataset.configAddProductBehavior=window.SnipcartSettings.addProductBehavior),window.SnipcartSettings.modalStyle&&(t.dataset.configModalStyle=window.SnipcartSettings.modalStyle),window.SnipcartSettings.currency&&(t.dataset.currency=window.SnipcartSettings.currency),window.SnipcartSettings.templatesUrl&&(t.dataset.templatesUrl=window.SnipcartSettings.templatesUrl))}})();

        window.LoadSnipcart();

        document.addEventListener('snipcart.ready', () => {

            setTimeout(async function () {
                try {
                    const items = <?= json_encode(array_values(kart()->cart()->lines()->toArray(fn (\Bnomei\Kart\CartLine $line) => [
                        'id' => $line->product()?->uuid()->id(),
                        'name' => $line->product()?->title()->value(),
                        'price' => $line->product()?->price()->toFloat(),
                        'url' => $line->product()?->url(),
                        'quantity' => $line->quantity(),
                    ]))) ?>;

                    for (const item of items) {
                        try {
                            await Snipcart.api.cart.items.add(item);
                        } catch (e) {
                            console.error(e);
                        }
                    }

                } catch (error) {
                    console.log(error)
                }

                Snipcart.api.theme.cart.open();
            }, 100);

            // https://docs.snipcart.com/v3/sdk/events#cartconfirmed
            Snipcart.events.on('cart.confirmed', (cartConfirmResponse) => {
               window.location.href = '<?= url(Router::PROVIDER_SUCCESS) ?>';
            });

            // make the cancel buttons from snipcart return to shop
            document.addEventListener('click', (event) => {
               if (event.target.closest('.snipcart-modal__close') || event.target.closest('.snipcart-base-button__label')) {
                   event.preventDefault()
                   window.location.href = '<?= url(Router::PROVIDER_CANCEL) ?>';
               }
            });
        });
    </script>
<?php }
