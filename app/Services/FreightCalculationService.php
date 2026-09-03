<?php

namespace App\Services;

class FreightCalculationService
{
    public const IATA_DIVISOR = 5000;

    /**
     * Calculate volumetric weight in kilograms.
     */
    public static function calculateVolumetricWeight(
        ?float $length,
        ?float $width,
        ?float $height,
        int $qty = 1,
        string $dimensionType = 'standard',
        ?float $diameter = null
    ): float {
        $qty = max(1, $qty);

        if ($dimensionType === 'diameter' && $diameter > 0 && $height > 0) {
            // Cubic bounding box for cylinder: (Dia * Dia * H) / 5000 * Qty
            $volWeight = (($diameter * $diameter * $height) / self::IATA_DIVISOR) * $qty;

            return round($volWeight, 3);
        }

        if ($length > 0 && $width > 0 && $height > 0) {
            $volWeight = (($length * $width * $height) / self::IATA_DIVISOR) * $qty;

            return round($volWeight, 3);
        }

        return 0.0;
    }

    /**
     * Calculate Cubic Meters (CBM).
     */
    public static function calculateCbm(
        ?float $length,
        ?float $width,
        ?float $height,
        int $qty = 1,
        string $dimensionType = 'standard',
        ?float $diameter = null
    ): float {
        $qty = max(1, $qty);

        if ($dimensionType === 'diameter' && $diameter > 0 && $height > 0) {
            // True cylindrical CBM: π * (d/2)^2 * h in m³
            $radius = $diameter / 2;
            $volumeCm3 = M_PI * ($radius * $radius) * $height;

            return round(($volumeCm3 / 1000000) * $qty, 4);
        }

        if ($length > 0 && $width > 0 && $height > 0) {
            $volumeCm3 = $length * $width * $height;

            return round(($volumeCm3 / 1000000) * $qty, 4);
        }

        return 0.0;
    }

    /**
     * Chargeable weight is the greater of actual gross weight and volumetric weight.
     */
    public static function calculateChargeableWeight(float $grossWeight, float $volumetricWeight): float
    {
        return round(max($grossWeight, $volumetricWeight), 3);
    }

    /**
     * Calculate system freight amount given chargeable weight and rate per kg.
     */
    public static function calculateFreightAmount(float $chargeableWeight, float $ratePerKg): float
    {
        return round($chargeableWeight * $ratePerKg, 2);
    }

    public static function calculateSystemAmount(float $chargeableWeight, float $ratePerKg): float
    {
        return self::calculateFreightAmount($chargeableWeight, $ratePerKg);
    }
}
