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
        require APPPATH . 'config/clinical_questionnaires.php';

        return $config['clinical_catalog'] + ['questionnaire_forms' => $config['clinical_questionnaires']];
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

    private function minimalAssessment(array $extra): array
    {
        return array_merge(['chief_complaint' => 'x', 'physio_diagnosis' => 'y', 'goals' => [['week' => '1', 'goal' => 'z']]], $extra);
    }

    public function testChoicesMeasuresAndBmi(): void
    {
        [$content] = $this->model()->clean_content($this->assessment(), $this->minimalAssessment([
            'smoking' => 'former',
            'sleep' => 'inventado',
            'temperature' => '36,5',
            'heart_rate' => '72',
            'weight' => '86',
            'height' => '1,78',
            'blood_pressure' => ['sys' => '128', 'dia' => '82'],
        ]), ['catalog' => $this->catalog()]);

        $this->assertSame('former', $content['smoking']);
        $this->assertSame('', $content['sleep']);
        $this->assertSame(36.5, $content['temperature']);
        $this->assertSame(72, $content['heart_rate']);
        $this->assertSame(['sys' => 128, 'dia' => 82], $content['blood_pressure']);
        $this->assertSame(27.1, $content['bmi']);
    }

    public function testMeasureOutOfRangeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cr_field_oxygen_saturation');

        $this->model()->clean_content($this->assessment(), $this->minimalAssessment(['oxygen_saturation' => '120']), ['catalog' => $this->catalog()]);
    }

    public function testBloodPressureNeedsBothValuesAndSystolicAbove(): void
    {
        foreach ([['sys' => '120', 'dia' => ''], ['sys' => '80', 'dia' => '120']] as $pressure) {
            try {
                $this->model()->clean_content($this->assessment(), $this->minimalAssessment(['blood_pressure' => $pressure]), ['catalog' => $this->catalog()]);
                $this->fail('Era esperado erro de pressão arterial.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('cr_invalid_blood_pressure', $e->getMessage());
            }
        }
    }

    public function testNeuroScreenKeepsOnlyLevelsOfTheMarkedQuadrant(): void
    {
        [$content] = $this->model()->clean_content($this->assessment(), $this->minimalAssessment([
            'pain_regions' => ['joelho_e'],
            'neuro_screen' => [
                'L4' => ['derm_d' => 'normal', 'derm_e' => 'decreased', 'myo_d' => '5', 'myo_e' => '4'],
                'L5' => ['derm_d' => 'talvez', 'myo_e' => '7'],
                'C5' => ['derm_d' => 'absent', 'myo_d' => '2'],
            ],
        ]), ['catalog' => $this->catalog()]);

        $this->assertSame(['L4' => ['derm_d' => 'normal', 'derm_e' => 'decreased', 'myo_d' => 5, 'myo_e' => 4]], $content['neuro_screen']);
    }

    public function testQuestionnaireAnswersCalculateTheScore(): void
    {
        [$content] = $this->model()->clean_content($this->assessment(), $this->minimalAssessment([
            'pain_regions' => ['ombro_d'],
            'region_tests' => ['ombro_d' => [
                'questionnaires' => ['exemplo_demo' => '99', 'spadi' => '40'],
                'answers' => ['exemplo_demo' => ['2', '1', '0', '2'], 'spadi' => ['1']],
            ]],
        ]), ['catalog' => $this->catalog()]);

        $block = $content['region_tests']['ombro_d'];
        $this->assertSame('5/8', $block['questionnaires']['exemplo_demo']);
        $this->assertSame([0 => 2, 1 => 1, 2 => 0, 3 => 2], $block['answers']['exemplo_demo']);
        $this->assertSame('40', $block['questionnaires']['spadi']);
        $this->assertArrayNotHasKey('spadi', $block['answers']);
    }

    public function testPartialAnswersKeepTheTypedScoreAndPercentScoring(): void
    {
        $form = ['scoring' => 'percent', 'questions' => [
            ['text' => 'a', 'options' => [['x', 0], ['y', 3]]],
            ['text' => 'b', 'options' => [['x', 0], ['y', 1]]],
        ]];

        $this->assertNull(Clinical_records_model::questionnaire_score($form, [0 => 1]));
        $this->assertSame('75%', Clinical_records_model::questionnaire_score($form, [0 => 1, 1 => 0]));
        $this->assertArrayHasKey('exemplo_demo', Clinical_records_model::area_questionnaires('joelho', $this->catalog()));
        $this->assertArrayHasKey('lysholm', Clinical_records_model::area_questionnaires('joelho', $this->catalog()));
    }
}
