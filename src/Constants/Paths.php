<?php

namespace Go2Flow\Ezport\Constants;

class Paths {

    const APP_PATH = 'Ezport/';

    const APP_NAMESPACE = 'App\\Ezport\\Customers\\';

    const APP_FILEPATH = 'app/Ezport/Customers';

    const PROJECT_PATH = 'Go2Flow/Ezport/';
    const PROJECT_NAMESPACE = 'Go2Flow\Ezport\\';

    public static function appHelpers(): string
    {
        return app_path(self::APP_PATH.'Helpers/');
    }

    public static function projectCustomers(): string
    {
        return base_path(self::PROJECT_PATH.'Customers/');
    }

    public static function filePath(string ...$segments): string
    {

        $parts = array_merge([base_path(self::APP_FILEPATH)], $segments);
        return '/' . rtrim(implode(DIRECTORY_SEPARATOR, array_map(fn($s) => trim($s, '/\\'), $parts)), DIRECTORY_SEPARATOR);
    }

    public static function className(string ...$segments): string
    {
        $parts = array_merge([self::APP_NAMESPACE], array_map('ucfirst', $segments));
        return rtrim(implode('\\', array_map(fn($s) => trim($s, '/\\'), $parts)), '\\');
    }
    public static function projectName(string ...$segments): string
    {
        $parts = array_merge([self::PROJECT_NAMESPACE], array_map('ucfirst', $segments));
        return rtrim(implode('\\', array_map(fn($s) => trim($s, '/\\'), $parts)), '\\');
    }

    public static function projectPath(string ...$segments): string
    {
        $parts = array_merge([base_path(self::PROJECT_PATH)], $segments);
        return '/' . rtrim(implode(DIRECTORY_SEPARATOR, array_map(fn($s) => trim($s, '/\\'), $parts)), DIRECTORY_SEPARATOR);
    }
}

