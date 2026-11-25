<?php

namespace Tests\Unit\Helper;

use Tests\TestCase;

/**
 * Situações dos atendimentos e cores da agenda (application/helpers/appointment_status_helper.php).
 */
class AppointmentStatusHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['ea_test_settings'] = [];
    }

    private function statuses(array $names, array $colors = []): void
    {
        setting([
            'appointment_status_options' => json_encode($names),
            'appointment_status_colors' => json_encode($colors),
        ]);
    }

    public function testOptionsKeepTheConfiguredOrderAndSkipBlankNames(): void
    {
        $this->statuses(['Marcado', ' ', 'Confirmado']);

        $this->assertSame(['Marcado', 'Confirmado'], appointment_status_options());
    }

    public function testSavedColorWins(): void
    {
        $this->statuses(['Marcado'], ['Marcado' => '#123456']);

        $this->assertSame(['Marcado' => '#123456'], appointment_status_colors());
    }

    public function testStatusWithoutSavedColorUsesTheDefaultOfTheSameName(): void
    {
        $this->statuses(['Executado', 'Situação nova']);

        $colors = appointment_status_colors();

        $this->assertSame('#abe9a4', $colors['Executado']);
        $this->assertSame('#7cbae8', $colors['Situação nova']); // Cor padrão de uma situação nova.
    }

    public function testColorsNeverLetMarkupThrough(): void
    {
        $this->statuses(['Marcado'], ['Marcado' => '"><script>']);

        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', appointment_status_colors()['Marcado']);
    }

    public function testItemsCarryTheHintOfTheDefaultList(): void
    {
        $this->statuses(['Aguardando']);

        $this->assertStringContainsString('recepção', appointment_status_items()[0]['hint']);
    }

    public function testAutomationOnlyUsesStatusesThatAreInTheList(): void
    {
        $this->statuses(['Marcado', 'Encaixado', 'Executado']);

        $this->assertSame('Encaixado', appointment_status_automation('overbooked'));
        $this->assertSame('Executado', appointment_status_automation('completed'));

        // A clínica renomeou "Encaixado": a mudança automática deixa de acontecer, sem erro.
        $this->statuses(['Marcado', 'Encaixe', 'Executado']);

        $this->assertNull(appointment_status_automation('overbooked'));
    }

    public function testCompletionKeepsNoShowAndCancelled(): void
    {
        $this->assertTrue(appointment_status_keeps_on_completion('Faltou'));
        $this->assertTrue(appointment_status_keeps_on_completion('Cancelado'));
        $this->assertFalse(appointment_status_keeps_on_completion('Aguardando'));
        $this->assertFalse(appointment_status_keeps_on_completion(null));
    }
}
