<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class SprQr
{
    public static function dataUri(string $url): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => true,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 8,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
        ]);

        return (new QRCode($options))->render($url);
    }
}
