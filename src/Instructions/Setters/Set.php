<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessor;
use Go2Flow\Ezport\Instructions\Setters\Special\PriceField;
use Go2Flow\Ezport\Instructions\Setters\Types\Api;
use Go2Flow\Ezport\Instructions\Setters\Types\Base;
use Go2Flow\Ezport\Instructions\Setters\Types\Basic;
use Go2Flow\Ezport\Instructions\Setters\Types\Connector;
use Go2Flow\Ezport\Instructions\Setters\Types\CsvImport;
use Go2Flow\Ezport\Instructions\Setters\Types\CsvImportStep;
use Go2Flow\Ezport\Instructions\Setters\Types\CsvProcessor;
use Go2Flow\Ezport\Instructions\Setters\Types\FtpCleaner;
use Go2Flow\Ezport\Instructions\Setters\Types\FtpFileImport;
use Go2Flow\Ezport\Instructions\Setters\Types\Job;
use Go2Flow\Ezport\Instructions\Setters\Types\Jobs;
use Go2Flow\Ezport\Instructions\Setters\Types\Project;
use Go2Flow\Ezport\Instructions\Setters\Types\Schedule;
use Go2Flow\Ezport\Instructions\Setters\Types\ShopCleaner;
use Go2Flow\Ezport\Instructions\Setters\Types\ShopImport;
use Go2Flow\Ezport\Instructions\Setters\Types\Step;
use Go2Flow\Ezport\Instructions\Setters\Types\Transform;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Instructions\Setters\Types\XmlImport;
use Go2Flow\Ezport\Instructions\Setters\Types\RunTransformProcess;
use Go2Flow\Ezport\Instructions\Setters\Types\RunImportProcess;
use Go2Flow\Ezport\Instructions\Setters\Types\RunUploadProcess;
use Go2Flow\Ezport\Process\Errors\EzportSetterException;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

/**
 * @method static Api Api(string $key, array|Collection $config = [])
 * @method static Connector Connector(string $key, array $config = [])
 * @method static CsvImport CsvImport(string $key)
 * @method static CsvImportOld CsvImportOld(string $key)
 * @method static CsvImportStep CsvImportStep()
 * @method static FtpCleaner FtpCleaner(string $key)
 * @method static Jobs Jobs(string $key = '')
 * @method static Job Job(array|null $config = [])
 * @method static Schedule Schedule()
 * @method static Project Project(string $key, array $config = [])
 * @method static ShopCleaner ShopCleaner(string $key, ?\Closure $ids = null , array $config = [])
 * @method static ShopImport ShopImport(string $key, array $config = [])
 * @method static Transform Transform(string $key, array $config = [])
 * @method static Upload Upload(string $key)
 * @method static UploadField UploadField(string|null $key = null)
 * @method static PriceField PriceField(string $key)
 * @method static UploadProcessor UploadProcessor(string|null $key = null)
 * @method static CsvProcessor CsvProcessor(string|null $key = null)
 * @method static ArticleProcessor ArticleProcessor(string $key)
 * @method static XmlImport XmlImport(string $key, array $config = [])
 * @method static FtpFileImport FtpFileImport(string $key, array $config = [])
 * @method static Basic Basic(string $key, array $config = [])
 * @method static Step Step(string $key, array $config = [])
 * @method static RunUploadProcess RunUploadProcess(string $key, array $config = [])
 * @method static RunImportProcess RunImportProcess(string $key, array $config = [])
 * @method static RunTransformProcess RunTransformProcess(string $key, array $config = [])
 **/

class Set {

    public static function __callStatic(string|Stringable $name, ?array $arguments = []) : Base
    {
        foreach (['Go2Flow\Ezport\Instructions\Setters\Types\\', 'Go2Flow\Ezport\Instructions\Setters\Special\\'] as $namespace)
        {
            if (class_exists($namespace . ucfirst($name)))
            {
                return new ($namespace . ucfirst($name))( ... $arguments);
            }
        }

        throw new EzportSetterException('Class not found');
    }
}
