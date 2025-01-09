<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Instructions\Setters\Types\Base;
use Illuminate\Support\Str;

class Relation extends Base
{
    private ?string $typeName = null;
    private ?\Closure $id = null;

    private ?\Closure $collection = null;

    public function __construct(string $key = null)
    {
        $this->key = Str::of($key);
    }

    /** Here you can set up a closure that of Content Types that should be attached as a relationship.
     * The content type item that you're attaching to as well as the config will be passed in on execution.
     * if you use this method the other functionality will not be used.
     * For this to work, a simple collection with ContentTypes must be returned.
     * The key passed into the constructor will be used as the name for the relationship.
     */

    public function collection(\Closure $collection) : self {

        $this->collection = $collection;

        return $this;
    }

    /** the contentType type that it should look for. This will only be used if no closure has been set in the collection method. */

    public function typeName(string $typeName) : self
    {
        $this->typeName = $typeName;

        return $this;
    }


    /** set the unique_id of the content type you're trying to find. Expects a closure.
     * Upon execution the current contentType you're trying to create a relationship for and any config array will be passed in
     * Will only be called if the collection method has no closure set.
     */

    public function id(\Closure $id) : self
    {
        $this->id = $id;

        return $this;
    }

    /** will process the relation for you. Will be called automatically if used in a Set::transform()->relation() context
     * It will first look for the collection closure. If it does not find that it will try to create the relationship by itself.
     * In this case it will use the id closure to find the correct content type and then attach it using the object key and typeName*/
    public function process($item, ?array $config = null) : array {

        $key = $this->key->plural()->toString();

        if ($this->collection) return [$key => ($this->collection)($item, $config)];

        return [
            $key => collect([
                Content::type($this->typeName ?? $this->key->singular(), $this->project)
                    ->find(($this->id)($item, $config))
            ])
        ];

    }
}
