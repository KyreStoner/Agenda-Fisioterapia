<?php

namespace Tests\Unit\Model;

use InvalidArgumentException;
use Provider_shifts_model;
use ReflectionClass;
use Tests\TestCase;

class ProviderShiftsModelTest extends TestCase
{
    private function model(): Provider_shifts_model
    {
        require_once BASEPATH . 'core/Model.php';
        require_once APPPATH . 'core/EA_Model.php';
        require_once APPPATH . 'models/Provider_shifts_model.php';

        // validate() e applies_on() não usam o banco enquanto o turno não tiver "id".
        return (new ReflectionClass(Provider_shifts_model::class))->newInstanceWithoutConstructor();
    }

    private function shift(array $overrides = []): array
    {
        return array_merge(
            [
                'id_users_provider' => 2,
                'weekday' => 'weekdays',
                'frequency' => 'weekly',
                'start_time' => '08:00',
                'end_time' => '18:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'duration' => 60,
                'valid_from' => null,
                'valid_until' => null,
                'notes' => null,
            ],
            $overrides,
        );
    }

    private function assertInvalid(array $shift, string $message): void
    {
        try {
            $this->model()->validate($shift);
            $this->fail('Era esperado um erro de validação: ' . $message);
        } catch (InvalidArgumentException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function testValidShiftPasses(): void
    {
        $this->model()->validate($this->shift());
        $this->addToAssertionCount(1);
    }

    public function testEndMustBeAfterStart(): void
    {
        $this->assertInvalid($this->shift(['end_time' => '08:00', 'break_start' => null, 'break_end' => null]), 'shift_end_after_start');
    }

    public function testBreakMustBeInsideTheShift(): void
    {
        $this->assertInvalid($this->shift(['break_start' => '07:00', 'break_end' => '09:00']), 'shift_break_inside');
        $this->assertInvalid($this->shift(['break_start' => '17:00', 'break_end' => '18:00']), 'shift_break_inside');
    }

    public function testBreakNeedsBothTimes(): void
    {
        $this->assertInvalid($this->shift(['break_end' => null]), 'shift_break_incomplete');
    }

    public function testDurationLimits(): void
    {
        $this->assertInvalid($this->shift(['duration' => 0]), 'shift_invalid_duration');
        $this->assertInvalid($this->shift(['duration' => 'abc']), 'shift_invalid_duration');
        $this->assertInvalid($this->shift(['duration' => 481]), 'shift_invalid_duration');
    }

    public function testValidityOrderAndBiweeklyStart(): void
    {
        $this->assertInvalid($this->shift(['valid_from' => '2026-12-10', 'valid_until' => '2026-12-01']), 'shift_validity_order');
        $this->assertInvalid($this->shift(['frequency' => 'biweekly']), 'shift_biweekly_needs_start');
        $this->assertInvalid($this->shift(['weekday' => 'feriado']), 'shift_weekday_required');
    }

    public function testNormalizeTurnsEmptyValuesIntoNullAndTrimsSeconds(): void
    {
        $shift = $this->model()->normalize($this->shift(['id' => '', 'break_start' => '', 'start_time' => '08:00:00']));

        $this->assertArrayNotHasKey('id', $shift);
        $this->assertNull($shift['break_start']);
        $this->assertSame('08:00', $shift['start_time']);
    }

    public function testAppliesOnWeekdaysAndSpecificDay(): void
    {
        $model = $this->model();

        $this->assertTrue($model->applies_on($this->shift(), '2026-11-23')); // segunda
        $this->assertFalse($model->applies_on($this->shift(), '2026-11-28')); // sábado
        $this->assertTrue($model->applies_on($this->shift(['weekday' => 'saturday']), '2026-11-28'));
        $this->assertTrue($model->applies_on($this->shift(['weekday' => 'all']), '2026-11-29')); // domingo
    }

    public function testAppliesOnRespectsValidity(): void
    {
        $shift = $this->shift(['valid_from' => '2026-11-24', 'valid_until' => '2026-11-26']);

        $this->assertFalse($this->model()->applies_on($shift, '2026-11-23'));
        $this->assertTrue($this->model()->applies_on($shift, '2026-11-24'));
        $this->assertTrue($this->model()->applies_on($shift, '2026-11-26'));
        $this->assertFalse($this->model()->applies_on($shift, '2026-11-27'));
    }

    public function testBiweeklyAlternatesWeeksFromTheStartDate(): void
    {
        // Começa num domingo: o domingo é o último dia da semana (segunda a domingo).
        $shift = $this->shift(['weekday' => 'sunday', 'frequency' => 'biweekly', 'valid_from' => '2026-11-29']);

        $this->assertTrue($this->model()->applies_on($shift, '2026-11-29'));
        $this->assertFalse($this->model()->applies_on($shift, '2026-12-06'));
        $this->assertTrue($this->model()->applies_on($shift, '2026-12-13'));

        $monday = $this->shift(['weekday' => 'monday', 'frequency' => 'biweekly', 'valid_from' => '2026-11-26']);

        $this->assertFalse($this->model()->applies_on($monday, '2026-11-23')); // antes da vigência
        $this->assertTrue($this->model()->applies_on($monday, '2026-11-30')); // primeira segunda depois do início
        $this->assertFalse($this->model()->applies_on($monday, '2026-12-07'));
        $this->assertTrue($this->model()->applies_on($monday, '2026-12-14'));

        // Dias úteis começando num sábado: a primeira semana de atendimento é a seguinte.
        $weekdays = $this->shift(['frequency' => 'biweekly', 'valid_from' => '2026-11-28']);

        $this->assertTrue($this->model()->applies_on($weekdays, '2026-11-30'));
        $this->assertFalse($this->model()->applies_on($weekdays, '2026-12-08'));
        $this->assertTrue($this->model()->applies_on($weekdays, '2026-12-17'));
    }
}
