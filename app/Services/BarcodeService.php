<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;

class BarcodeService
{
    public function generateBarcode($assetId, $type = 'C128')
    {
        $asset = Asset::findOrFail($assetId);
        $barcode = DNS1D::getBarcodePNG($asset->asset_tag, $type);

        $filename = 'barcodes/'.$asset->asset_tag.'.png';
        Storage::disk('public')->put($filename, base64_decode($barcode));

        return Storage::url($filename);
    }

    public function generateQrCode($assetId)
    {
        $asset = Asset::findOrFail($assetId);
        $url = route('assets.show', $asset);

        $qrCode = DNS2D::getBarcodePNG(
            $url,
            'QRCODE',
            10,
            10
        );

        $filename = 'qrcodes/'.$asset->asset_tag.'.png';
        Storage::disk('public')->put($filename, base64_decode($qrCode));

        return Storage::url($filename);
    }

    public function generateBarcodeForAllAssets($type = 'C128')
    {
        $assets = Asset::all();
        $barcodes = [];

        foreach ($assets as $asset) {
            $barcodes[$asset->id] = [
                'barcode' => $this->generateBarcode($asset->id, $type),
                'qrcode' => $this->generateQrCode($asset->id),
                'asset' => $asset,
            ];
        }

        return $barcodes;
    }

    public function scanBarcode($barcodeData)
    {
        // This would typically interface with a barcode scanner
        // For now, we'll just search by asset tag
        return Asset::where('asset_tag', $barcodeData)->first();
    }
}
