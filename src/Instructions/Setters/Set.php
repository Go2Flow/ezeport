<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Go2Flow\Ezport\Instructions\Setters\Api;
use Go2Flow\Ezport\Instructions\Setters\Schedule;
use Go2Flow\Ezport\Instructions\Setters\CsvImport;
use Go2Flow\Ezport\Instructions\Setters\FtpCleaner;
use Go2Flow\Ezport\Instructions\Setters\Job;
use Go2Flow\Ezport\Instructions\Setters\Jobs;
use Go2Flow\Ezport\Instructions\Setters\Project;
use Go2Flow\Ezport\Instructions\Setters\ShopCleaner;
use Go2Flow\Ezport\Instructions\Setters\ShopImport;
use Go2Flow\Ezport\Instructions\Setters\Transform;
use Go2Flow\Ezport\Instructions\Setters\Upload;
use Go2Flow\Ezport\Instructions\Setters\XmlImport;
use Go2Flow\Ezport\Instructions\Setters\Basic;
use Go2Flow\Ezport\Instructions\Setters\FtpFileImport;
use Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessor;
use Go2Flow\Ezport\Instructions\Setters\Special\PriceField;
use Illuminate\Support\Stringable;

/**
 * @method static Api Api(string $key, array|Collection $config = [])
 * @method static Connector Connector(string $key, array $config = [])
 * @method static CsvImport CsvImport(string $key)
 * @method static CsvImportStep CsvImportStep()
 * @method static FtpCleaner FtpCleaner(string $key)
 * @method static Jobs Jobs(string $key = '')
 * @method static Job Job(array|null $config = [])
 * @method static Schedule Schedule()
 * @method static Project Project(string $key, array $config = [])
 * @method static ShopCleaner ShopCleaner(string $key, ?Closure $ids = null , array $config = [])
 * @method static ShopImport ShopImport(string $key, array $config = [])
 * @method static Transform Transform(string $key, array $config = [])
 * @method static Upload Upload(string $key)
 * @method static UploadField UploadField(string|null $key = null)
 * @method static PriceField PriceField(string $key)
 * @method static UploadProcessor UploadProcessor(string|null $key = null)
 * @method static ArticleProcessor ArticleProcessor(string $key)
 * @method static XmlImport XmlImport(string $key, array $config = [])
 * @method static FtpFileImport FtpFileImport(string $key, array $config = [])
 * @method static Basic Basic(string $key, array $config = [])
 **/

class Set {

    public static function __callStatic(string|Stringable $name, ?array $arguments = []) : Base
    {
        foreach (['Go2Flow\Ezport\InstructioGo2Flow\Ezport\etters\\', 'Go2Flow\Ezport\Instructions\Setters\Special\\'] as $namespace)
        {
            if (class_exists($namespace . ucfirst($name)))
            {
                return new ($namespace . ucfirst($name))( ... $arguments);
            }
        }

        throw new \Exception('Class not found');
    }
}
