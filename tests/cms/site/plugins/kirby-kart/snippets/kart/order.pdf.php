<?php /** @var OrderPage $order */ ?>
<main>
    <h1>Your Order: <a href="<?= $order->urlWithSignature() ?>" style="text-decoration: none"><code><?= $order->title() ?></code></a></h1>

    <table>
        <tr>
            <td>Customer</td>
            <td><?= $order->customer()->toUser()?->email() ?></td>
        </tr>
        <tr>
            <td>Invoice Number</td>
            <td>#<?= $order->invoiceNumber() ?></td>
        </tr>
        <tr>
            <td>Order Date</td>
            <td><?= $order->paidDate()->toDate('Y-m-d H:i') ?></td>
        </tr>
        <tr>
            <td>Order Status</td>
            <td><?= $order->paymentComplete()->toBool() ? 'paid' : 'open' ?></td>
        </tr>
        <tr>
            <td>Order Total</td>
            <td><?= $order->formattedTotal() ?></td>
        </tr>
    </table>

    <table>
        <?php /** @var ProductPage|null $product */
        /** @var \Bnomei\Kart\OrderLine $line */
        foreach ($order->orderLines() as $line) {
            $product = $line->product();
            ?>
            <tr>
                <td><img src="<?= $product?->gallery()->toFile()?->resize(128)->url() ?>" alt=""></td>
                <td><?= $product?->title() ?></td>
                <td><?= $line->quantity() ?>x</td>
                <td><?= $line->formattedPrice() ?></td>
                <td><?= $line->formattedTotal() ?></td>
            </tr>
        <?php } ?>
    </table>
</main>
