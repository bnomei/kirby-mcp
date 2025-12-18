<?php

kart()->validateSignatureOrGo();

snippet('kart/kart', slots: true);
// COPY and modify the code below this line --------

/** @var OrderPage $order */
$order ??= $page;
?>

<main>1
    <article>
        <header>
            <h1>Your Order <?= $order->title() ?></h1>
        </header>

        <p>
            Invoice Number: #<?= $order->invoiceNumber() ?><br>
            Order Date: <?= $order->paidDate()->toDate('Y-m-d H:i') ?><br>
            Order Status: <?= $order->paymentComplete()->toBool() ? 'paid' : 'open' ?><br>
            Order Total: <?= $order->formattedTotal() ?>
        </p>

        <table>
            <?php foreach ($order->orderLines() as $line) {
                /** @var \Bnomei\Kart\OrderLine $line */
                /** @var ProductPage|null $product */
                $product = $line->product();
                ?>
                <tr>
                    <td><img src="<?= $product?->gallery()->toFile()?->url() ?>" alt=""></td>
                    <td><a href="<?= $product?->url() ?>"><?= $product?->title() ?></a></td>
                    <td><?= $line->quantity() ?>x</td>
                    <td><?= $line->formattedPrice() ?></td>
                    <td><?= $line->formattedTotal() ?></td>
                </tr>
            <?php } ?>
        </table>
    </article>

    <nav>
        <h2>Previous Orders</h2>
<?php
$user = kirby()->user();
if ($user && $user === $page->customer()->toUser()) { ?>
            <ol>
                <?php foreach ($user->orders()->not($order) as $order) { ?>
                    <li><a href="<?= $order->urlWithSignature() ?>"><?= $order->paidDate()->toDate('Y-m-d H:i') ?> <?= $order->title() ?></a></li>
                <?php } ?>
            </ol>
        <?php } else { ?>
            <p><mark>Please <a href="<?= url('kart/login') ?>">log in</a> to see previous orders.</mark></p>
        <?php } ?>
    </nav>
</main>
