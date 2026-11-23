<?php

namespace Tests\Unit\Model;

use Clinical_records_model;
use InvalidArgumentException;
use ReflectionClass;
use Tests\TestCase;

class ClinicalRecordsModelTest extends TestCase
{
    private function model(): Clinical_records_model
    {
        require_once BASEPATH . 'core/Model.php';
        require_once APPPATH . 'core/EA_Model.php';
        require_once APPPATH . 'models/Clinical_records_model.php';

        // clean_content() não usa o banco nem a configuração.
        return (new ReflectionClass(Clinical_records_model::class))->newInstanceWithoutConstructor();
    }

    private function form(): array
    {
        $config = [];

        require APPPATH . 'config/clinical_forms.php';

        return $config['clinical_forms']['evolution'];
    }

    public function testCleanContentKeepsFieldsAndExtractsThePainScale(): void
    {
        [$content, $pain] = $this->model()->clean_content($this->form(), [
            'pain_scale' => '4',
            'procedures' => '  Mobilização lombar  ',
            'campo_desconhecido' => 'ignorado',
        ]);

        $this->assertSame(4, $pain);
        $this->assertSame('Mobilização lombar', $content['procedures']);
        $this->assertArrayNotHasKey('campo_desconhecido', $content);
        $this->assertSame('', $content['patient_response']);
    }

    public function testCleanContentRequiresTheMandatoryFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('clinical_record_required_fields cr_field_procedures.');

        $this->model()->clean_content($this->form(), ['pain_scale' => '2', 'procedures' => '   ']);
    }

    public function testPainScaleMustBeBetweenZeroAndTen(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->model()->clean_content($this->form(), ['pain_scale' => '11', 'procedures' => 'x']);
    }

    public function testPainScaleIsOptional(): void
    {
        [, $pain] = $this->model()->clean_content($this->form(), ['pain_scale' => '', 'procedures' => 'x']);

        $this->assertNull($pain);
    }

    public function testAssessmentRequiresComplaintDiagnosisAndPlan(): void
    {
        $config = [];

        require APPPATH . 'config/clinical_forms.php';

        try {
            $this->model()->clean_content($config['clinical_forms']['assessment'], ['chief_complaint' => 'Dor lombar']);
            $this->fail('Era esperado um erro de campos obrigatórios.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cr_field_physio_diagnosis', $e->getMessage());
            $this->assertStringContainsString('cr_field_treatment_plan', $e->getMessage());
            $this->assertStringNotContainsString('cr_field_chief_complaint', $e->getMessage());
        }
    }
}
