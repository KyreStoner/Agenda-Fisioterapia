<?php

namespace Tests\Unit\Model;

use Goal_templates_model;
use InvalidArgumentException;
use ReflectionClass;
use Tests\TestCase;

class GoalTemplatesModelTest extends TestCase
{
    private function model(): Goal_templates_model
    {
        require_once BASEPATH . 'core/Model.php';
        require_once APPPATH . 'core/EA_Model.php';
        require_once APPPATH . 'models/Goal_templates_model.php';

        // save() valida antes de usar o banco.
        return (new ReflectionClass(Goal_templates_model::class))->newInstanceWithoutConstructor();
    }

    public function testGoalIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('goal_template_goal_required');

        $this->model()->save(['goal' => '   ', 'area' => 'ombro'], ['ombro']);
    }

    public function testAreaMustExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('goal_template_invalid_area');

        $this->model()->save(['goal' => 'Elevar o braço', 'area' => 'asa'], ['ombro']);
    }
}
