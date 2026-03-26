<?php

namespace App\Validators;

use Valitron\Validator;

class UrlValidator
{
    /**
     * @param string $url
     * @return array
     */
    public static function validate(string $url): array
    {
        $v = new Validator(['url' => $url]);

        $v->rule('required', 'url')->message('URL не должен быть пустым');
        $v->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');
        $v->rule('url', 'url')->message('Некорректный URL');

        $errors = [];

        if ($v->validate()) {
            return [];
        }

        foreach ($v->errors() as $fieldErrors) {
            $errors = array_merge($errors, $fieldErrors);
        }

        return $errors;
    }

    /**
     * @param string $url
     * @return string
     */
    public static function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);

        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';

        return strtolower($scheme . '://' . $host);
    }
}
