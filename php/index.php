<?php
declare(strict_types=1);

// Production URL нашего webhook в n8n.
$n8nUrl = 'https://catbug.app.n8n.cloud/webhook/api/test.task/get.deals';

// Значения по умолчанию.
$deals = [];
$errors = [];
$pageError = null;

// Подготавливаем один GET-запрос к n8n.
$curl = curl_init($n8nUrl);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
    ],
]);

// Здесь выполняется единственный запрос за данными.
$responseBody = curl_exec($curl);

$statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$requestError = curl_error($curl);

curl_close($curl);

// Проверяем результат запроса.
if ($responseBody === false) {
    $pageError = 'Не удалось подключиться к n8n: ' . $requestError;
} elseif ($statusCode < 200 || $statusCode >= 300) {
    $pageError = 'n8n вернул HTTP-код ' . $statusCode;
} else {
    $payload = json_decode($responseBody, true);

    if (!is_array($payload)) {
        $pageError = 'n8n вернул некорректный JSON';
    } elseif (!isset($payload['data']) || !is_array($payload['data'])) {
        $pageError = 'В ответе n8n отсутствует массив data';
    } else {
        $deals = $payload['data'];
        $errors = isset($payload['errors']) && is_array($payload['errors'])
            ? $payload['errors']
            : [];
    }
}


function escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function formatAmount($value): string
{
    return is_numeric($value)
        ? number_format((float) $value, 0, ',', ' ')
        : '';
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Сделки</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">
<main class="container py-5">

    <h1 class="mb-4">Сделки</h1>

    <?php if ($pageError !== null): ?>

        <div class="alert alert-danger" role="alert">
            <?= escape($pageError) ?>
        </div>

    <?php else: ?>

        <?php if ($errors !== []): ?>

            <div class="alert alert-warning" role="alert">
                Часть сделок не загрузилась:
                <?= count($errors) ?>.
                Доступные сделки показаны ниже.
            </div>

        <?php endif; ?>

        <div class="table-responsive shadow-sm">

            <table class="table table-striped table-bordered table-hover mb-0 bg-white">

                <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Название</th>
                    <th scope="col">Сумма</th>
                    <th scope="col">Ответственный</th>
                    <th scope="col">Компания</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($deals === []): ?>

                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Сделок нет
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($deals as $deal): ?>

                        <tr>
                            <td><?= escape($deal['id'] ?? '') ?></td>

                            <td>
                                <?= escape($deal['title'] ?? '') ?>
                            </td>

                            <td>
                                <?= escape(formatAmount($deal['amount'] ?? null)) ?>
                            </td>

                            <td>
                                <?= escape($deal['responsible'] ?? '') ?>
                            </td>

                            <td>
                                <?= escape($deal['company'] ?? '') ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</main>
</body>
</html>