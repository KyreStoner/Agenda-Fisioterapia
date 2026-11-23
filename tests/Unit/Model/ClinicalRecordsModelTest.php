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

    private function catalog(): array
    {
        $config = [];

        require APPPATH . 'config/clinical_catalog.php';

        return $config['clinical_catalog'];
    }

    private function assessment(): array
    {
        $config = [];

        require APPPATH . 'config/clinical_forms.php';

        return $config['clinical_forms']['assessment'];
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
            $this->assertStringContainsString('cr_field_goals', $e->getMessage());
            $this->assertStringNotContainsString('cr_field_chief_complaint', $e->getMessage());
        }
    }

    public function testBodyMapKeepsOnlyKnownRegionsInCatalogOrder(): void
    {
        [$content] = $this->model()->clean_content(
            $this->form(),
            ['procedures' => 'x', 'pain_regions' => ['joelho_e', 'inexistente', 'ombro_d', 'ombro_d']],
            ['catalog' => $this->catalog()],
        );

        $this->assertSame(['ombro_d', 'joelho_e'], $content['pain_regions']);
    }

    public function testRegionBlocksJoinRegionsOfTheSameAreaAndSide(): void
    {
        $blocks = $this->model()->region_blocks(['braco_d', 'ombro_d', 'ombro_e', 'coluna_lombar', 'cabeca'], $this->catalog());

        $this->assertSame(['lombar', 'ombro_d', 'ombro_e'], array_keys($blocks));
        $this->assertSame('Ombro cr_side_right', $blocks['ombro_d']['label']);
    }

    public function testRegionTestsOnlyKeepTheMarkedRegionsAndValidResults(): void
    {
        $input = [
            'chief_complaint' => 'Dor no ombro',
            'physio_diagnosis' => 'CBDF fictício',
            'goals' => [['week' => '1', 'goal' => 'Elevar o braço sem dor']],
            'pain_regions' => ['ombro_d'],
            'region_tests' => [
                'ombro_d' => [
                    'tests' => ['neer' => 'positive', 'hawkins' => 'talvez', 'lachman' => 'positive', 'jobe' => ''],
                    'questionnaires' => ['spadi' => ' 45 ', 'koos' => '10'],
                ],
                'joelho_d' => ['tests' => ['lachman' => 'positive']],
            ],
        ];

        [$content] = $this->model()->clean_content($this->assessment(), $input, ['catalog' => $this->catalog()]);

        $this->assertSame(['ombro_d' => ['tests' => ['neer' => 'positive'], 'questionnaires' => ['spadi' => '45']]], $content['region_tests']);
    }

    public function testGoalsDropEmptyRowsSortByWeekAndGetIds(): void
    {
        $input = [
            'chief_complaint' => 'x',
            'physio_diagnosis' => 'y',
            'goals' => [
                ['week' => '3', 'goal' => 'Correr 5 minutos', 'plan' => 'Fortalecimento'],
                ['week' => '2', 'goal' => '   '],
                ['id' => 'abc123def', 'week' => '1', 'goal' => 'Andar sem dor'],
            ],
        ];

        [$content] = $this->model()->clean_content($this->assessment(), $input, ['catalog' => $this->catalog()]);

        $this->assertCount(2, $content['goals']);
        $this->assertSame('abc123def', $content['goals'][0]['id']);
        $this->assertSame(1, $content['goals'][0]['week']);
        $this->assertSame('Fortalecimento', $content['goals'][1]['plan']);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{6,20}$/', $content['goals'][1]['id']);
    }

    public function testGoalWeekMustBeValid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cr_goal_invalid_week');

        $this->model()->clean_content(
            $this->assessment(),
            ['chief_complaint' => 'x', 'physio_diagnosis' => 'y', 'goals' => [['week' => '200', 'goal' => 'z']]],
            ['catalog' => $this->catalog()],
        );
    }

    public function testGoalsCheckOnlyAcceptsGoalsOfTheAssessment(): void
    {
        [$content] = $this->model()->clean_content(
            $this->form(),
            ['procedures' => 'x', 'goals_achieved' => ['g1aaaa', 'inventada']],
            ['catalog' => $this->catalog(), 'goal_ids' => ['g1aaaa', 'g2bbbb']],
        );

        $this->assertSame(['g1aaaa'], $content['goals_achieved']);
    }

    public function testGoalsProgressUsesTheLatestAssessmentAndFirstAchievement(): void
    {
        $records = [
            ['type' => 'evolution', 'record_date' => '2026-12-10 10:00:00', 'content' => ['goals_achieved' => ['g1aaaa', 'g2bbbb']]],
            ['type' => 'evolution', 'record_date' => '2026-12-03 10:00:00', 'content' => ['goals_achieved' => ['g1aaaa']]],
            ['type' => 'assessment', 'record_date' => '2026-12-01 10:00:00', 'content' => ['goals' => [['id' => 'g1aaaa'], ['id' => 'g2bbbb'], ['id' => 'g3cccc']]]],
        ];

        $progress = $this->model()->goals_progress($records);

        $this->assertCount(3, $progress['goals']);
        $this->assertSame(['g1aaaa' => '2026-12-03 10:00:00', 'g2bbbb' => '2026-12-10 10:00:00'], $progress['achieved']);
    }
}
