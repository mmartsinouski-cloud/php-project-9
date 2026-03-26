<?php

use App\Services\UrlChecker;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use App\Models\Url;
use App\Models\UrlCheck;
use App\Validators\UrlValidator;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

try {
    Database::initializeDatabase();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
}

session_start();

$app = AppFactory::create();

$renderer = new PhpRenderer(__DIR__ . '/../templates');
$renderer->setLayout('layout/app.phtml');

$app->addErrorMiddleware(true, true, true);

// Главная страница
$app->get('/', function ($request, $response) use ($renderer) {
    return $renderer->render($response, 'index.phtml');
});

// Список всех URL
$app->get('/urls', function ($request, $response) use ($renderer) {
    $urlModel = new Url();
    $urls = $urlModel->findAllWithLastCheck();

    return $renderer->render($response, 'urls/index.phtml', [
        'urls' => $urls
    ]);
});

// Просмотр конкретного URL
$app->get('/urls/{id}', function ($request, $response, $args) use ($renderer) {
    $id = (int) $args['id'];
    $urlModel = new Url();
    $url = $urlModel->findById($id);

    if (!$url) {
        return $response->withStatus(404);
    }

    $urlCheckModel = new UrlCheck();
    $checks = $urlCheckModel->findByUrlId($id);

    return $renderer->render($response, 'urls/show.phtml', [
        'url' => $url,
        'checks' => $checks
    ]);
});

// Создание нового URL
$app->post('/urls', function ($request, $response) use ($renderer) {
    $data = $request->getParsedBody();
    $rawUrl = $data['url'] ?? '';

    $normalizedUrl = UrlValidator::normalizeUrl($rawUrl);
    $errors = UrlValidator::validate($rawUrl);

    if (!empty($errors)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => implode(', ', $errors)
        ];

        $responseWithFlash = $renderer->render($response, 'index.phtml');
        return $responseWithFlash->withStatus(422);
    }

    $urlModel = new Url();
    $existingUrl = $urlModel->findByName($normalizedUrl);

    if ($existingUrl) {
        $_SESSION['flash'] = [
            'type' => 'info',
            'message' => 'Страница уже существует'
        ];

        return $response->withHeader('Location', "/urls/{$existingUrl['id']}")
            ->withStatus(302);
    }

    $id = $urlModel->create($normalizedUrl);

    if ($id) {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Страница успешно добавлена'
        ];

        return $response->withHeader('Location', "/urls/$id")
            ->withStatus(302);
    }

    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Произошла ошибка при добавлении страницы. '
    ];

    return $renderer->render($response, 'index.phtml');
});

// Создание проверки для URL
$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($renderer) {
    $urlId = (int) $args['url_id'];

    $urlModel = new Url();
    $url = $urlModel->findById($urlId);

    if (!$url) {
        return $response->withStatus(404);
    }

    $urlCheckModel = new UrlCheck();
    $urlChecker = new UrlChecker();

    try {
        // Выполняем проверку сайта
        $checkData = $urlChecker->check($url['name']);

        // Сохраняем результаты в базу данных
        $success = $urlCheckModel->create($urlId, $checkData);

        if ($success) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Страница успешно проверена'
            ];
        } else {
            throw new Exception('Не удалось сохранить результаты проверки. ');
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Произошла ошибка при проверке. ' . $e->getMessage()
        ];
    }

    return $response->withHeader('Location', "/urls/$urlId")->withStatus(302);
});

$app->run();
