<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;

class UrlChecker
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => true,
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; SEO Analyzer Bot/1.0)'
            ]
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public function check(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $html = (string)$response->getBody();
            $statusCode = $response->getStatusCode();

            // Парсим HTML
            $parsedData = $this->parseHtml($html);

            return [
                'statusCode' => $statusCode,
                'h1' => $parsedData['h1'],
                'title' => $parsedData['title'],
                'description' => $parsedData['description'],
                'success' => $statusCode < 400
            ];
        } catch (ConnectException $e) {
            throw new Exception('Не удалось подключиться к серверу. ' . $e->getMessage());
        } catch (RequestException $e) {
            // Проверяем, есть ли ответ и он не null
            $response = $e->hasResponse() ? $e->getResponse() : null;

            if ($response instanceof ResponseInterface) {
                $statusCode = $response->getStatusCode();
                $h1 = null;
                $title = null;
                $description = null;

                try {
                    $html = (string)$response->getBody();
                    $parsedData = $this->parseHtml($html);
                    $h1 = $parsedData['h1'];
                    $title = $parsedData['title'];
                    $description = $parsedData['description'];
                } catch (Exception) {
                    // Игнорируем ошибки парсинга
                }

                return [
                    'statusCode' => $statusCode,
                    'h1' => $h1,
                    'title' => $title,
                    'description' => $description,
                    'success' => $statusCode < 400
                ];
            }

            // Если нет ответа
            throw new Exception('Ошибка при выполнении запроса. ' . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new Exception('Ошибка при выполнении запроса. ' . $e->getMessage());
        }
    }

    /**
     * Парсит HTML и извлекает SEO-элементы
     * @return array{
     *     h1: string|null,
     *     title: string|null,
     *     description: string|null
     * }
     */
    private function parseHtml(string $html): array
    {
        try {
            $crawler = new Crawler($html);

            // Извлечение H1 (берем первый)
            $h1 = null;
            $h1Nodes = $crawler->filter('h1');
            if ($h1Nodes->count() > 0) {
                $h1 = trim($h1Nodes->first()->text());
                $h1 = $h1 !== '' ? $h1 : null;
            }

            // Извлечение Title
            $title = null;
            $titleNodes = $crawler->filter('title');
            if ($titleNodes->count() > 0) {
                $title = trim($titleNodes->first()->text());
                $title = $title !== '' ? $title : null;
            }

            // Извлечение Description (meta[name="description"])
            $description = null;
            $descriptionNodes = $crawler->filter('meta[name="description"]');
            if ($descriptionNodes->count() > 0) {
                $description = trim($descriptionNodes->first()->attr('content') ?? '');
                $description = $description !== '' ? $description : null;
            }

            return [
                'h1' => $h1,
                'title' => $title,
                'description' => $description,
            ];
        } catch (Exception) {
            // В случае ошибки парсинга возвращаем пустые значения
            return [
                'h1' => null,
                'title' => null,
                'description' => null,
            ];
        }
    }
}