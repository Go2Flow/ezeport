<?php

namespace Go2Flow\Ezport\Constants;

class Paths {

    CONST APP_PATH = 'app/Ezport/';
    CONST PROJECT_PATH = 'Go2Flow/Ezport/';

    public static function appHelpers() : string{

        return self::APP_PATH.'Helpers/';
    }

    public static function appCustomers() : string {

        return self::APP_PATH . 'Customers/';
    }

    public static function projectInstructions() : string {

        return self::PROJECT_PATH . 'Instructions/';
    }

    public static function projectCustomers() : string
    {

        return self::PROJECT_PATH . 'Customers/';
    }

}
