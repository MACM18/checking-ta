<?php

namespace Tests\Unit;

use App\Services\FreightCalculationService;
use PHPUnit\Framework\TestCase;

class FreightCalculationTest extends TestCase
{
    private FreightCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FreightCalculationService;
    }

    public function test_standard_box_volumetric_weight_calculation(): void
    {
        // Box 50cm x 40cm x 30cm, qty 2
        // (50 * 40 * 30 / 5000) * 2 = 12 * 2 = 24.0 kg
        $volWeight = $this->service->calculateVolumetricWeight(
            length: 50,
            width: 40,
            height: 30,
            qty: 2,
            dimensionType: 'standard',
            diameter: null
        );

        $this->assertEquals(24.0, $volWeight);
    }

    public function test_cylindrical_diameter_volumetric_weight_calculation(): void
    {
        // Cylinder diameter 40cm, height 50cm, qty 3
        // (40 * 40 * 50 / 5000) * 3 = 16 * 3 = 48.0 kg
        $volWeight = $this->service->calculateVolumetricWeight(
            length: null,
            width: null,
            height: 50,
            qty: 3,
            dimensionType: 'diameter',
            diameter: 40
        );

        $this->assertEquals(48.0, $volWeight);
    }

    public function test_cbm_calculation_for_box_and_cylinder(): void
    {
        // Box 100cm x 50cm x 40cm, qty 1 = 0.2000 m3
        $boxCbm = $this->service->calculateCbm(
            length: 100,
            width: 50,
            height: 40,
            qty: 1,
            dimensionType: 'standard',
            diameter: null
        );
        $this->assertEquals(0.2, $boxCbm);

        // Cylinder Dia 50cm, height 100cm, qty 1
        // pi * (0.25)^2 * 1 = pi * 0.0625 = ~0.1963 m3
        $cylCbm = $this->service->calculateCbm(
            length: null,
            width: null,
            height: 100,
            qty: 1,
            dimensionType: 'diameter',
            diameter: 50
        );
        $this->assertEquals(0.1963, $cylCbm);
    }

    public function test_chargeable_weight_uses_higher_of_actual_gross_or_volumetric(): void
    {
        // When actual weight (60 kg) is higher than volumetric (45 kg) -> 60 kg
        $chargeable1 = $this->service->calculateChargeableWeight(60.0, 45.0);
        $this->assertEquals(60.0, $chargeable1);

        // When volumetric (75 kg) is higher than actual (30 kg) -> 75 kg
        $chargeable2 = $this->service->calculateChargeableWeight(30.0, 75.0);
        $this->assertEquals(75.0, $chargeable2);
    }

    public function test_system_amount_calculation_with_rate_per_kg(): void
    {
        // Chargeable weight 50.5 kg, Rate per kg 4.20 USD -> 212.10 USD
        $amount = $this->service->calculateSystemAmount(50.5, 4.20);
        $this->assertEquals(212.10, $amount);
    }
}
