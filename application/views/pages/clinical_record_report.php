<?php
/**
 * Relatório A4 do prontuário (imprimir ou salvar em PDF pelo navegador).
 *
 * Variáveis (html_vars): customer, records (ordem cronológica), single, forms, catalog, goals_progress, goal_labels,
 * issued_by, company_name, company_email, company_link, company_logo.
 */
$customer = vars('customer');
$records = vars('records');
$forms = vars('forms');
$catalog = vars('catalog');
$progress = vars('goals_progress');
$patient_name = trim($customer['first_name'] . ' ' . $customer['last_name']);

$datetime = fn(?string $value) => $value ? date('d/m/Y H:i', strtotime($value)) : '';

$address = implode(', ', array_filter([
    trim((string) ($customer['address'] ?? '')),
    trim((string) ($customer['city'] ?? '')),
    trim((string) ($customer['zip_code'] ?? '')),
]));

$authors = array_values(array_unique(array_column($records, 'author_name')));

if (vars('single')) {
    $title = $records[0]['type_name'] ?: lang($records[0]['type'] === 'assessment' ? 'cr_report_assessment' : 'cr_report_evolution');
} else {
    $title = lang('cr_report_title');
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($patient_name) ?></title>
    <link rel="stylesheet" type="text/css"
          href="<?= asset_url('assets/css/themes/' . html_escape(setting('theme', 'default')) . '.css') ?>">
    <style>
        @page { size: A4; margin: 14mm 14mm 16mm; }
        :root { color-scheme: light; }
        body { background: #e9ecef; color: #212529; font-size: 10.5pt; line-height: 1.4; }
        .toolbar { position: sticky; top: 0; z-index: 10; background: #fff; border-bottom: 1px solid #dee2e6; padding: 8px 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: center; }
        .sheet { background: #fff; width: 210mm; min-height: 297mm; margin: 12px auto 24px; padding: 14mm; box-shadow: 0 2px 12px rgba(0, 0, 0, .15); }
        .report-header { border-bottom: 2px solid var(--bs-primary); padding-bottom: 8px; margin-bottom: 12px; }
        .report-header img { max-height: 48px; max-width: 120px; object-fit: contain; }
        .patient-box { border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 10px; margin-bottom: 12px; }
        .record { border-top: 1px solid #dee2e6; padding-top: 8px; margin-top: 14px; }
        .record h2 { font-size: 12pt; margin: 0 0 4px; }
        .patient-box, .goals-box, .signature, dd { break-inside: avoid; }
        .record h2, .record h2 + div, .record h6, .record dt { break-after: avoid; }
        .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .record h6 { line-height: 1.6; padding-top: 2px; }
        .body-map .row { max-width: 280px; }
        .body-map .bm-figure { max-width: 46px; }
        .signature { margin-top: 22mm; display: flex; flex-wrap: wrap; gap: 10mm; }
        .signature div { flex: 1 1 60mm; border-top: 1px solid #212529; text-align: center; padding-top: 4px; font-size: 9pt; }
        .legal { margin-top: 10mm; font-size: 8pt; color: #6c757d; }
        @media screen and (max-width: 820px) {
            .sheet { width: auto; min-height: 0; margin: 0; padding: 16px; box-shadow: none; }
        }
        @media print {
            body { background: #fff; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i><?= lang('cr_report_print') ?>
    </button>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('clinical_records/patient/' . (int) $customer['id']) ?>">
        <?= lang('cr_back_to_record') ?>
    </a>
    <span class="small text-muted w-100 text-center"><?= lang('cr_report_pdf_hint') ?></span>
</div>

<main class="sheet">
    <header class="report-header d-flex justify-content-between align-items-start gap-3">
        <div class="d-flex align-items-center gap-2">
            <?php if (vars('company_logo')): ?>
                <img src="<?= e(vars('company_logo')) ?>" alt="">
            <?php endif; ?>
            <div>
                <div class="fw-bold fs-6"><?= e(vars('company_name')) ?></div>
                <div class="small text-muted">
                    <?= e(vars('company_email')) ?>
                    <?php if (vars('company_link')): ?> · <?= e(vars('company_link')) ?><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-bold"><?= e($title) ?></div>
            <div class="small text-muted"><?= lang('cr_report_issued_at') ?> <?= date('d/m/Y H:i') ?></div>
        </div>
    </header>

    <section class="patient-box">
        <div class="small text-muted"><?= lang('customer') ?></div>
        <div class="fw-bold"><?= e($patient_name) ?></div>
        <div class="small">
            <?php if ($customer['phone_number']): ?><?= lang('phone_number') ?>: <?= e($customer['phone_number']) ?><?php endif; ?>
            <?php if ($customer['email']): ?> · <?= lang('email') ?>: <?= e($customer['email']) ?><?php endif; ?>
            <?php if ($address): ?><br><?= lang('address') ?>: <?= e($address) ?><?php endif; ?>
        </div>
    </section>

    <?php if (!vars('single') && $progress['goals']): ?>
        <?php
        $total = count($progress['goals']);
        $done = count(array_filter($progress['goals'], fn($goal) => isset($progress['achieved'][$goal['id']])));
        ?>
        <section class="goals-box patient-box">
            <div class="small text-muted"><?= lang('cr_goals_progress') ?></div>
            <div class="fw-semibold mb-1"><?= $done ?> <?= lang('cr_goals_of') ?> <?= $total ?></div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($progress['goals'] as $goal): ?>
                    <li>
                        <?php if ($goal['week']): ?><?= lang('cr_goal_week') ?> <?= (int) $goal['week'] ?>: <?php endif; ?>
                        <?= e($goal['goal']) ?>
                        <?php if (isset($progress['achieved'][$goal['id']])): ?>
                            <strong>(<?= lang('cr_goal_achieved_on') ?> <?= date('d/m/Y', strtotime($progress['achieved'][$goal['id']])) ?>)</strong>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!$records): ?>
        <p class="text-muted"><?= lang('cr_no_records') ?></p>
    <?php endif; ?>

    <?php foreach ($records as $record): ?>
        <article class="record record-<?= $record['type'] ?>">
            <h2>
                <?= e($record['type_name'] ?: lang('cr_' . $record['type'])) ?> · <?= $datetime($record['record_date']) ?>
                <?php if ($record['pain_scale'] !== null): ?>
                    <span class="badge text-bg-light border fw-normal"><?= lang('cr_pain') ?> <?= (int) $record['pain_scale'] ?>/10</span>
                <?php endif; ?>
            </h2>
            <div class="small text-muted"><?= lang('provider') ?>: <?= e($record['author_name']) ?></div>

            <?php component('clinical/record_content', [
                'rc_form' => $forms[$record['type']],
                'rc_content' => $record['content'],
                'rc_catalog' => $catalog,
                'rc_goal_labels' => vars('goal_labels'),
            ]); ?>
        </article>
    <?php endforeach; ?>

    <section class="signature">
        <?php foreach ($authors ?: [''] as $author): ?>
            <div>
                <?= e($author) ?><br>
                <?= lang('cr_report_signature') ?>
            </div>
        <?php endforeach; ?>
    </section>

    <p class="legal">
        <?= lang('cr_report_generated') ?> <?= e(app_name()) ?> · <?= date('d/m/Y H:i') ?> · <?= e(vars('issued_by')) ?>.
        <?= lang('cr_report_lgpd') ?>
    </p>
</main>

<script src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
</body>
</html>
