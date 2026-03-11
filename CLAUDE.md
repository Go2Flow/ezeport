# Ezport Package — CLAUDE.md

ETL framework for importing data into Shopware 6 (and 5). Consumer projects define instructions for each phase: Import, Transform, Upload.

## The Pipeline

Three phases, always in order:

1. **Import** — pull external data (CSV, XLSX, XML, API) into Generic content types
2. **Transform** — normalize data, build relationships between content types
3. **Upload** — push data to Shopware via API

## Content Types (the core abstraction)

Everything is a `Generic` — a schema-less wrapper around `GenericModel` (Eloquent). Data lives in two JSON columns: `content` (business data) and `shop` (Shopware IDs and state).

```php
Content::type('Article', $project)              // TypeGetter — query builder for Generics of this type
Content::type('Article', $project)->find('SKU') // Find by unique_id
```

### Generic API

```php
$item->properties('key')              // get from content JSON
$item->properties(['key' => 'value']) // merge into content JSON
$item->shop('key')                    // get from shop JSON (alias: shopware())
$item->shop(['key' => 'value'])       // merge into shop JSON
$item->relations('group')             // get related Generics by group_type
$item->relations(['group' $group])    // merge into relations collection. Note: This is not saved by 'updateAndCreate' but needs 'setRelations'
$item->parents()                      // get parent Generics via pivot
$item->unique_id                      // business key
$item->type                           // content type name
$item->updateOrCreate()               // persist
```

The getter/setter behavior is driven by `BaseModel::getOrSetData()` — same pattern for all three:
- `null`/no arg → returns the full collection
- `string` → gets value by key
- `array`/`Collection` → merges into the data

**Persistence difference**: `properties()` and `shop()` modify database-backed JSON columns saved by `updateOrCreate()`. `relations()` modifies the in-memory `modelRelations` property only — to persist, call `setRelations()` or `relationsAndSave()` which syncs to the `nested_relationships` pivot table.

## Instruction System

### Set vs Get

- **`Set::Upload('key')`** — defines a custom instruction inline with `->items()`, `->fields()`, `->processor()`
- **`Get::upload('key')`** — resolves a pre-built instruction by name via the Finder system
- **`Set::UploadProcessor('key')`** — defines a processor inline
- **`Get::processor('key')`** — resolves a processor by name via the Finder system

`Get` methods return a **GetProxy** — a lazy wrapper that defers resolution until the Project is available. You can chain methods on it (e.g. `Get::processor('Article')->config([...])`) and they'll be replayed after resolution.

`Set` uses `__callStatic` to instantiate classes from `Instructions/Setters/Types/` or `Instructions/Setters/Special/`.

### Resolution Priority (Finder system)

`Find::processor()`, `Find::upload()`, etc. are created dynamically via `Find::__callStatic`. Each Finder searches registered instruction classes and calls `->find($key)` which matches by `hasKey($key)`.

| Type | Resolution order |
|------|-----------------|
| **Processor** | 1. Project-specific `Instructions/Processors.php` 2. Config `processors` array |
| **Upload** | Config `uploads` array only |
| **Import** | Config `imports` array only |
| **Transform** | Config `transformers` array only |

**Only Processors have project-level overrides.** Everything else resolves from the config array in `app/Ezport/Customers/{Project}/config.php`.

Project-specific instructions live at: `app/Ezport/Customers/{ProjectIdentifier}/Instructions/`

### Upload Fields

Fields define how a Generic maps to a Shopware API payload. Several forms:

```php
// Simple key-value — closure receives the Generic item
['fieldName' => fn ($item) => $item->properties('x')]

// Spread field — returned array is merged into the payload
Set::uploadField()->field(fn ($item) => ['key1' => 'val1', 'key2' => 'val2'])

// Named field
Set::uploadField('fieldName')->field(fn ($item) => $value)

// Price helpers
Set::priceField('price')->price(fn ($item) => 100)->gross()
Set::PricesField('prices')->prices(fn ($item) => $collection)
```

Returning `null` from a field closure means "skip this field" — it won't appear in the payload.

### Upload Execution Flow

1. `Upload::pluck()` — collects item IDs (chunked, default 25)
2. `Upload::execute($config)` — for each chunk:
   - Loads Generic models from IDs
   - Sets structure (fields) on each item so `toShopArray()` works
   - Passes the collection to the processor's `run()` method
3. `UploadProcessor::run($items)` — calls the process closure with:
   - `$items` — Collection of Generics (with `toShopArray()` available)
   - `$api` — resolved API instance
   - `$config` — any config from `->config()`
   - `$components` — any child uploads from `->component()`

### toShopArray()

Called on a Generic item, this runs all the upload fields and returns a flat array ready for the Shopware API. The Upload instruction sets the structure (fields) on each item before the processor runs, so by the time the processor calls `$item->toShopArray()`, the fields are already attached.

## Job System

### Two-Phase Processing

1. **AssignInstruction** — calls `instruction->assignJobs()` to split work into chunks, dispatches child ProcessInstruction jobs
2. **ProcessInstruction** — calls `instruction->execute($config)` to process one chunk

### Job Definitions (in consumer projects)

Jobs are defined in `Instructions/Jobs.php` using `Set::Job()` with `.step([])` chains. Steps run sequentially; instructions within a step run in parallel.

### Schedule

Defined in `Instructions/Schedule.php`. Typically triggers the import → transform → upload sequence on a cron.

## Default Processors and Uploads

Package ships with defaults in `Helpers/Getters/`:
- `Processors/ShopwareSix/` — Article, ArticleMedia, Media, PropertyGroup, etc.
- `Uploads/ShopwareSix/` — Article, ArticleMedia, Categories, Customers, Media, etc.

These are what `Get::processor('name')` and `Get::upload('name')` resolve to (unless overridden at project level for processors).

## Ad-hoc Processing Outside Jobs

You can run an upload + processor on a single Generic without going through the job pipeline:

```php
$generic->setStructureByString('upload_name_here')->process('processor_name_here')
```

This sets the upload fields (so `toShopArray()` works) and then runs the named processor on the item. The processor must be resolvable — either a package default in `Helpers/Getters/Processors/` or a project-level override in `Processors.php`.

## Common Pitfalls

- **Processor override priority**: `Get::processor('X')` checks the project's `Processors.php` FIRST. If you're debugging a processor, check the project override before looking at the package default.
- **`->filter()` on payload arrays**: `collect($arrayOfArrays)->filter()` does NOT filter out arrays with null values inside them — `['productId' => null, 'mediaId' => '...']` is truthy. Use explicit callbacks: `->filter(fn ($entry) => !empty($entry['productId']))`.
- **Null IDs in API payloads**: When building payloads that reference other entities (e.g. product media referencing products), the referenced entity may not exist in Shopware yet. Shopware interprets `{"id": null}` as "create a new entity" and will fail validation on required fields. Always filter out null references before API calls.
- **shop() vs shopware()**: These are aliases. Both access the `shop` JSON column.
- **properties() dual behavior**: `$item->properties('key')` reads; `$item->properties(['key' => 'val'])` writes. Easy to confuse when passing variables.
