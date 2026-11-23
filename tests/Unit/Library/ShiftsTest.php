<?php

namespace Tests\Unit\Library;

use ReflectionClass;
use Shifts;
use Tests\TestCase;

class ShiftsTest extends TestCase
{
    private function shifts(): Shifts
    {
        require_once APPPATH . 'libraries/Shifts.php';

        // O construtor só carrega o model; combine() e generate_hours() não usam o banco.
        return (new ReflectionClass(Shifts::class))->newInstanceWithoutConstructor();
    }

    private function shift(string $start, string $end, ?string $break_start = null, ?string $break_end = null, int $duration = 60): array
    {
        return [
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'break_start' => $break_start,
            'break_end' => $break_end,
            'duration' => $duration,
        ];
    }

    public function testCombineUsesTheBreakOfASingleShift(): void
    {
        $plan = $this->shifts()->combine([$this->shift('08:00', '18:00', '12:00', '13:00')]);

        $this->assertSame(['start' => '08:00', 'end' => '18:00', 'breaks' => [['start' => '12:00', 'end' => '13:00']]], $plan);
    }

    public function testCombineTurnsTheGapBetweenShiftsIntoABreak(): void
    {
        $plan = $this->shifts()->combine([$this->shift('14:00', '18:00'), $this->shift('08:00', '11:30')]);

        $this->assertSame('08:00', $plan['start']);
        $this->assertSame('18:00', $plan['end']);
        $this->assertSame([['start' => '11:30', 'end' => '14:00']], $plan['breaks']);
    }

    public function testCombineMergesOverlappingShiftsAndReturnsNullWithoutShifts(): void
    {
        $plan = $this->shifts()->combine([$this->shift('08:00', '12:00'), $this->shift('10:00', '14:00')]);

        $this->assertSame(['start' => '08:00', 'end' => '14:00', 'breaks' => []], $plan);
        $this->assertNull($this->shifts()->combine([]));
    }

    public function testGenerateHoursFollowsTheShiftGrid(): void
    {
        $shifts = [$this->shift('08:00', '18:00', '12:00', '13:00')];

        // Livre o dia todo, menos o almoço e uma consulta das 09:00 às 09:50.
        $free = [
            ['start' => '08:00', 'end' => '09:00'],
            ['start' => '09:50', 'end' => '12:00'],
            ['start' => '13:00', 'end' => '18:00'],
        ];

        $hours = $this->shifts()->generate_hours('2026-11-25', ['duration' => 50], $shifts, $free);

        $this->assertSame(['08:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'], $hours);
    }

    public function testGenerateHoursRestartsTheGridAfterTheBreakAndNeedsTheWholeService(): void
    {
        $shifts = [$this->shift('08:00', '11:00', '09:00', '09:15', 30)];
        $free = [['start' => '08:00', 'end' => '09:00'], ['start' => '09:15', 'end' => '11:00']];

        $hours = $this->shifts()->generate_hours('2026-11-25', ['duration' => 60], $shifts, $free);

        // 08:00 cabe (até 09:00); 08:30 não; depois do intervalo a grade recomeça às 09:15.
        $this->assertSame(['08:00', '09:15', '09:45'], $hours);
    }
}
