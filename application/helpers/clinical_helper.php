<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Funções do prontuário (carregadas pelo controller Clinical_records).
 */

if (!function_exists('bmi_classes')) {
    /**
     * Faixas de IMC da OMS: [limite superior (exclusivo), classificação].
     */
    function bmi_classes(): array
    {
        return [
            [18.5, lang('cr_bmi_underweight')],
            [25, lang('cr_bmi_normal')],
            [30, lang('cr_bmi_overweight')],
            [35, lang('cr_bmi_obesity_1')],
            [40, lang('cr_bmi_obesity_2')],
            [999, lang('cr_bmi_obesity_3')],
        ];
    }
}

if (!function_exists('bmi_class')) {
    /**
     * Classificação do IMC (OMS).
     */
    function bmi_class(float $bmi): string
    {
        foreach (bmi_classes() as [$limit, $label]) {
            if ($bmi < $limit) {
                return $label;
            }
        }

        return lang('cr_bmi_obesity_3');
    }
}

if (!function_exists('format_decimal')) {
    /**
     * Número no formato brasileiro (vírgula decimal), sem zeros sobrando: 36.5 → "36,5", 72.0 → "72".
     */
    function format_decimal(float|int|string $value): string
    {
        $text = rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');

        return $text === '' || $text === '-' ? '0' : $text;
    }
}
