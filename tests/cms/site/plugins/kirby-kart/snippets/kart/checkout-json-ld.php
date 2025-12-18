<?php
/** @var \Kirby\Cms\Page $page */
?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CheckoutPage",
        "name": "<?= $page->title() ?>",
        "url": "<?= $page->url() ?>",
        "isPartOf": {
            "@type": "WebSite",
            "name": "<?= $page->site()->title() ?>",
            "url": "<?= $page->site()->url() ?>"
        }
    }
</script>