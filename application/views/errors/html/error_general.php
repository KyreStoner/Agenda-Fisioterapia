<?php
/**
 * @var string $heading
 * @var string $message
 */
?>

<!doctype html>
<html lang="pt-br" style="
    height: 100%;
">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <link rel="icon" type="image/x-icon" href="<?= function_exists('asset_url')
        ? asset_url('assets/img/favicon.ico')
        : 'assets/img/favicon.ico' ?>">
    <title>Erro</title>
    <style>
        #error-container {
            background: #ffffff;
            width: calc(100% - 32px);
            box-sizing: border-box;
            max-width: 600px;
            margin: auto;
            border: 1px solid #D0D0D0;
            font: 13px/20px normal Helvetica, Arial, sans-serif;
            color: #4F5155;
        }

        #error-container a {
            color: #003399;
            background-color: transparent;
            font-weight: normal;
        }

        #error-container h1 {
            color: #444;
            background-color: transparent;
            border-bottom: 1px solid #D0D0D0;
            font-size: 19px;
            font-weight: normal;
            margin: 0 0 14px 0;
            padding: 20px;
        }

        #error-container code {
            font-family: Consolas, Monaco, Courier New, Courier, monospace;
            font-size: 12px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            color: #002166;
            display: block;
            margin: 14px 0 14px 0;
            padding: 20px;
        }

        #error-container p {
            margin: 20px;
        }
    </style>
</head>
<body style="
    height: 100%;
    padding: 0;
    margin: 0;
    display: flex;
    background: #f5f8fa;
">
<div id="error-container">
    <?php
    // Mensagens padrão do CodeIgniter traduzidas para o usuário.
    $translations = [
        '404 Page Not Found' => 'Página não encontrada',
        'The page you requested was not found.' => 'A página que você procurou não existe.',
        'An Error Was Encountered' => 'Ocorreu um erro',
        'A Database Error Occurred' => 'Erro no banco de dados',
        'Forbidden' => 'Você não tem permissão para acessar esta página.',
        'Not Found' => 'Página não encontrada.',
    ];
    $plain_message = trim(strip_tags((string) $message));
    ?>
    <h1>
        <?= $translations[trim(strip_tags((string) $heading))] ?? $heading ?>
    </h1>

    <?= isset($translations[$plain_message]) ? '<p>' . $translations[$plain_message] . '</p>' : $message ?>
    <p>
        <small>
            <a href="<?= function_exists('site_url') ? site_url() : '/' ?>">Voltar ao início</a>
        </small>
    </p>
</div>
</body>
</html>
