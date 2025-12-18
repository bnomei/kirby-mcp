<?php

kart()->validateSignatureOrGo();

use Kirby\Cms\File;
use Kirby\Filesystem\F;

/** @var OrderPage $page */
/** @var File $zip */
$zip = $page->downloads();
$token = get('token', ''); // OrderPage sets this to current timestamp by default, fallback again here

// NOTE: you could add logic here to limit amount of downloads if you wanted to

if ($zip) {
    $filename = F::safeName($page->title().'.zip');
    if ($alt = Kart::sanitize(get('filename'))) {
        $filename = F::safeName($alt);
    }

    $size = $zip->size();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    header('Content-Transfer-Encoding: binary');

    echo file_get_contents($zip->root());
    // $zip->download($filename);

    kirby()->trigger('kart.order.download', [
        'order' => $page,
        'size' => $size,
        'token' => substr(trim(strip_tags(strval($token))), 0, 42),
        'timestamp' => time(),
        'ip' => sha1(kirby()->visitor()->ip()),
    ]);
}

exit();
