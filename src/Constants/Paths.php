<?php

namespace Go2Flow\Ezport\Constants;

class Paths {

    const APP_PATH = 'Ezport/';
    const PROJECT_PATH = 'Go2Flow/Ezport/';

    public static function appHelpers(): string
    {
        return app_path(self::APP_PATH.'Helpers/');
    }

    public static function appCustomers(): string
    {
        return app_path(self::APP_PATH.'Customers/');
    }

    public static function projectInstructions(): string
    {
        return base_path(self::PROJECT_PATH.'Instructions/');
    }

    public static function projectCustomers(): string
    {
        return base_path(self::PROJECT_PATH.'Customers/');
    }
}
