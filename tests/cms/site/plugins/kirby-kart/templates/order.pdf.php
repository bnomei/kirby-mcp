<?php

kart()->validateSignatureOrGo();

snippet('kart/kart', slots: true);
// COPY and modify the code below this line --------
// TODO: composer require mpdf/mpdf

use Mpdf\Mpdf;

$mpdf = new Mpdf;
$mpdf->WriteHTML(snippet('kart/order.pdf', ['order' => $page], return: true));
$mpdf->Output($page->slug().'.pdf', 'D'); // D = download, I = in browser

exit();
