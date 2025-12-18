<?php
/** @var ProductPage $product */
$product ??= $page;
if ($product->variants()->isNotEmpty()) { ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProductGroup",
        "name": "<?= $product->title() ?>",
        "description": "<?= $product->description()->esc() ?>",
        "productGroupID": "<?= $product->slug() ?>",
        "variesBy": ["<?= implode('","', array_keys($product->variantGroups())) ?>"],
        "category": ["<?= implode('","', $product->categories()->split()) ?>"],
        "keywords": ["<?= implode('","', $product->tags()->split()) ?>"],
        "url": "<?= $product->url() ?>",
        "mainEntityOfPage": "<?= $product->url() ?>",
        "image": "<?= $product->gallery()->toFile()?->resize(1920)->url() ?>",
        "hasVariant": [
<?php foreach ($product->variants()->toStructure() as $item) {
    $variant = $item->variant()->value();
    if (empty($variant) || ! str_contains($variant, ':')) {
        continue;
    }
    $v = explode(':', $variant);
    ?>
            {
                "@type": "Product",
                "@id": "<?= $product->urlWithVariant($variant) ?>",
                "name": "<?= $product->title() ?> – <?= $variant ?>",
                "url": "<?= $product->urlWithVariant($variant) ?>",
                "image": "<?= $item->image()->toFile()?->resize(1920)->url() ?>",
                "description": "<?= $item->description()->or($product->description())->esc() ?>",
<?php if (strtolower($v[0]) === 'color') { ?>
                "color": "<?= $v[1] ?>",
<?php } ?>
<?php if (strtolower($v[0]) === 'size') { ?>
                "size": "<?= $v[1] ?>",
<?php } ?>
                "offers": {
                    "@type": "Offer",
                    "price": "<?= $product->priceWithVariant($variant) ?>",
                    "priceCurrency": "<?= kart()->currency() ?>",
                    "availability": "https://schema.org/<?= $product->stock(variant: $variant) > 0 ? 'InStock' : 'OutOfStock' ?>",
                    "url": "<?= $product->urlWithVariant($variant) ?>"
                }
            }
            <?php if (! $item->isLast()) { ?>,<?php } ?>
<?php } ?>
        ]
    }
</script>
<?php } else { ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "<?= $product->title() ?>",
        "image": "<?= $product->gallery()->toFile()?->resize(1920)->url() ?>",
        "description": "<?= $product->description()->esc() ?>",
        "url": "<?= $product->url() ?>",
        "category": ["<?= implode('","', $product->categories()->split()) ?>"],
        "keywords": ["<?= implode('","', $product->tags()->split()) ?>"],
        "offers": {
            "@type": "Offer",
            "price": "<?= $product->price()->toFloat() ?>",
            "priceCurrency": "<?= kart()->currency() ?>",
            "availability": "https://schema.org/<?= $product->stock() > 0 ? 'InStock' : 'OutOfStock' ?>",
            "url": "<?= $product->url() ?>"
        }
    }
</script>
<?php }
