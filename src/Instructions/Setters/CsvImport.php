<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Closure;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Process\Jobs\AssignCsv;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CsvImport extends Basic implements JobInterface
{
    protected Collection $imports;
    protected ?Closure $prepare = null;
    protected ?Closure $process = null;
    protected string $class;
    protected $stages = 0;

    public function __construct(string $key)
    {
        $this->key = $this->processKey($key);
        $this->class = Str::of($key)->ucfirst()->singular()->toString();

        $this->imports = collect();

        $this->jobClass = AssignCsv::class;
    }

    /**
     * set the number of stages needed to import
     * each 'stage' will arrive as a seperate collection at the 'prepare' closure
     * stages start at 0 and count up.
     * stages should be set on the CsvImportStep object using the 'stage' method
     */

    public function stages(int $stages): self
    {
        $this->stages = $stages;
        $this->imports = collect();

        for ($stages; $stages > 0; $stages--) $this->imports->push(collect());

        return $this;
    }

    /**
     * import a single csv file. See the CsvImportStep class to see how this is done.
     * If you are using 'stages' the 'stage' variable needs to be set to indicate the stage this import belongs to
     */

    public function import(CsvImportStep $object): self
    {
        if (! $this->stages) {
            $this->imports->push($object);

        } else {

            $this->imports[$object->getStage()]->push($object);
        }

        return $this;
    }

    /**
     * Here the data that is prepared in the 'imports' is received. If you've got multiple stages
     * then the the data will be received as an collection of collections. If you've only got one stage
     * the data will be received as a single collection.
     */

    public function prepare(Closure $closure) : self {

        $this->prepare = $closure;

        return $this;
    }

    /**
     * The data is passed one item from the prepare closure to the process closure.
     */

    public function process(Closure $closure) : self {

        $this->process = $closure;

        return $this;
    }

    /**
     * This specifies the type that the Generic Object will have.
     */

    public function type(string $type) : self {

        $this->class = $type;

        return $this;
    }

    protected function setSpecificFields() : array
    {
        return [
            'type' => $this->key->toString(),
        ];
    }
}
