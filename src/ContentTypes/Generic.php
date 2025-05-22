<?php

namespace Go2Flow\Ezport\ContentTypes;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\ContentTypes\Helpers\Log;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Finders\Processor;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportContentTypeException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
* @property int $id
* @property int $project_id
* @property string $unique_id
* @property string $type
* @property bool $updated
* @property bool $touched
* @property ?Collection $content
* @property ?Collection $shop
*/

class Generic
{
    protected ?Upload $setUpload = null;
    protected ?Project $project;
    private ?GenericModel $contentData;

    public function __construct($data)
    {
        $this->contentData = $data instanceof GenericModel
            ? $data
            : (new GenericModel)->findorCreateModel($data);
    }

    /**
     * returns the content type
     */

    public function getType(): string
    {
        return $this->contentData->type;
    }

    /**
     * a combination of the content and relations that sets both in one go
     * generic models are set as relations
     * content is set as properties
     * in both cases, the key is the name of the property
     */

    public function setContentAndRelations($data): self
    {
        if (isset($data['unique_id'])) {
            $data = collect($data);
            $data->forget('unique_id');
        }

        $this->contentData->setContentAndRelations($data);

        return $this;
    }

    /**
     * deletes the underlying GenericModel
     */

    public function delete() : void
    {
        (new Log($this))->delete();

        $this->contentData->parents()->detach();
        $this->contentData->children()->detach();

        $this->contentData->delete();
    }

    /**
     * get the parent relations
     */

    public function parents(?string $groupType = null): Collection
    {
        $query = $this->contentData->parents();

        if ($groupType) {
            $query->where('group_type', $groupType);
        }

        return $query->get()->toContentType();
    }

    /**
     * searches for properties that have '_id' in the key.
     * if it finds any, it will look in the GenericModels with the type as before the '_id' and the unique_ids as the values
     * If it finds items they will be connected as relations under the pluralized key before '_id'.
     */

    public function processRelations(): self
    {
        $this->contentData->processRelations();
        return $this;
    }

    public function setRelations(?string $type = null): self
    {
        collect(($type ? [$type => $this->relations($type)] : $this->relations()))
            ?->each(
                function ($group, $key) {

                    $current = $this->contentData->children()
                        ->wherePivot('group_type', $key)
                        ->pluck('child_id');

                    $new = $group->map(fn ($item) => $item->getModel()->id);

                    if (($detach = $current->diff($new))->count() > 0) {
                        $this->contentData->children()->detach($detach);
                    }

                    if (($attach = $new->diff($current))->count() > 0) {


                        foreach (GenericModel::whereIn('id', $attach)->with('children')->get() as $child) {
                            $this->contentData->assertNoCircularRelation($child);
                        }


                        $this->contentData->children()->attach(
                            $attach,
                            ['group_type' => $key]
                        );
                    }
                }
            );

        return $this;
    }

    public function toShopArray(array $config = []): array
    {
       $this->getSetUploadIfNoneSet();

        if (count($config) > 0) {
            $this->setUpload = $this->setUpload->config($config);
        }

        return $this->setUpload->toShopArray($this);
    }

    public function toShopCollection(array $config = []): Collection
    {

        return collect($this->toShopArray($config));
    }

    public function process(string|UploadProcessor|null $processor = null, array $array = []) : self {

        if (count($array) == null) $array = $this->toShopArray();

        $this->getProcessor($processor)
            ->run(collect([$array]));

        return $this;
    }

    public function getProcessor(string|UploadProcessor|null $processor) : UploadProcessor|Processor {

        if ($processor instanceof UploadProcessor) return $processor;

        return Get::processor($processor ?? $this->type)($this->project());

    }

    public function setStructure(Upload $upload): self
    {
        $this->setUpload = $upload;

        return $this;
    }

    public function setStructureByString(string $type): self
    {
        $structure = Find::instruction($this->project(), 'Upload')->find($type);

        if (!$structure) {
            throw new EzportContentTypeException('No structure found for ' . $type);
        }

        $this->setUpload = $structure;

        return $this;
    }

    public function setStructureByType(): self
    {
        return $this->setStructureByString($this->getType());
    }

    public function getStructure(): ?Upload
    {
        $this->getSetUploadIfNoneSet();

        return $this->setUpload;
    }

    /**
     * pass in a string to get the value of that property
     * pass in an array to set the value of that property
     * use the singular naming of a property to get the first instance of that property if it is a array or collection
     */

    public function properties(string|array|Collection $input = null): mixed
    {
        return $this->contentData->getOrSetData($input, 'content');
    }

    /**
     * pass in a string to get the value of that property
     * pass in an array to set the value of that property
     * use the singular naming of a property to get the first instance of that property if it is a array or collection
     */

    public function relations(string|array|Collection $input = null): mixed
    {
        return $this->contentData->getOrSetData($input, 'modelRelations');
    }

    /**
     * pass in a string to get the value of that property
     * pass in an array to set the value of that property
     * use the singular naming of a property to get the first instance of that property if it is a array or collection
     */

    public function shop($input = null)
    {
        return $this->contentData->getOrSetData($input, 'shop');
    }

    /**
     * @deprecated
     **/

    public function shopware($input = null)
    {
        return $this->contentData->getOrSetData($input, 'shop');
    }

    /**
     * remove a key from the properties attribute
     */

    public function propertiesForget($input = null) : self
    {
        $this->forget('content', $input);

        return $this;
    }

    /**
     * remove a key from the relations attribute
     */

    public function relationsForget(string $input = null) : self
    {
        $query = $this->contentData->children();

        if ($input) {
            $query->wherePivot('group_type', $input);
        }

        $query->detach();

        $this->forget('modelRelations', $input);

        return $this;
    }
    /**
     * remove a key from the shopware attribute
     */

    public function shopForget($input = null) : self
    {
        $this->forget('shop', $input);

        return $this;
    }

    /**
     * alternative to 'shopware' method
     */



    /**
     * will update or create the model
     * set 'updated' to false if you don't want the 'updated' field to be changed to true
     */

    public function updateOrCreate(bool|string $updated = true): self
    {
        (new Log($this))->change(... $this->contentData->updateOrCreateModel($updated));

        return $this;
    }

    public function setUpdated(bool $value = true): self
    {
        $this->contentData->update(['updated' => $value ]);

        return $this;
    }
    public function setTouched(bool $value = true): self
    {
        $this->contentData->update(['touched' => $value ]);

        return $this;
    }

    public function exists() : bool
    {
        return $this->contentData->exists;
    }

    public function relationsAndSave($setTouched = false): self
    {
        return $this->processRelations()
            ->updateOrCreate($setTouched)
            ->setRelations();
    }

    public function getModel(): GenericModel
    {
        return $this->contentData;
    }

    public function refresh(): Generic
    {
        return Content::type($this->getType(), $this->project())->find($this->unique_id);
    }

    public function project(): Project
    {
        return $this->project = $this->project ?? Project::find($this->contentData->project_id);
    }

    public function __get($name) :?string
    {
        return $this->contentData->$name;
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) return $this->$method(...$arguments);

        if (Str::startsWith($method, 'set')) {

            if (!property_exists($this->contentData, Str::after($method, 'set'))) {

                throw new EzportContentTypeException('Property ' . Str::after($method, 'set') . ' does not exist');
            }

            $this->contentData->{Str::after($method, 'set')} = $arguments[0];
        }

        throw new EzportContentTypeException("Method {$method} does not exist on this object");
    }

    private function forget($field, $input = null)
    {
        return $this->contentData->$field->forget($input);
    }

    private function getSetUploadIfNoneSet() : void
    {
        if (!$this->setUpload) {
            $this->setStructureByType();

            if (!$this->setUpload) {
                throw new EzportContentTypeException('No structure found for ' . $this->getType() . '. You might need to set it manually');
            }
        }
    }
}
