# Upgrade Guide

## Upgrading from v12 to v13

### Breaking Changes

#### Job Consolidation

9 specific job classes have been replaced by 2 generic ones: `AssignInstruction` and `ProcessInstruction`.

**Removed job classes:**

| Old Class | Replacement |
|-----------|-------------|
| `Process\Jobs\AssignProcess` | `Process\Jobs\AssignInstruction` |
| `Process\Jobs\AssignTransform` | `Process\Jobs\AssignInstruction` |
| `Process\Jobs\AssignFtpFileImport` | `Process\Jobs\AssignInstruction` |
| `Process\Jobs\RunProcess` | `Process\Jobs\ProcessInstruction` |
| `Process\Jobs\Transform` | `Process\Jobs\ProcessInstruction` |
| `Process\Jobs\CsvImport` | `Process\Jobs\ProcessInstruction` |
| `Process\Jobs\FtpFileImport` | `Process\Jobs\ProcessInstruction` |
| `Process\Jobs\CleanWithInstruction` | `Process\Jobs\ProcessInstruction` |
| `Process\Jobs\UploadWithInstruction` | `Process\Jobs\ProcessInstruction` |

If your project references any of these job classes directly, update them to use `AssignInstruction` or `ProcessInstruction`.

#### Property Rename in BaseTransformInstructions

`BaseTransformInstructions::$importInstructionType` has been renamed to `$instructionType`.

If your project's transform instruction class overrides this property, you **must** rename it:

```php
// Before
class TransformInstructions extends BaseTransformInstructions
{
    protected ?string $importInstructionType = 'transform';
}

// After
class TransformInstructions extends BaseTransformInstructions
{
    protected ?string $instructionType = 'transform';
}
```

> **Note:** If you don't override this property, no change is needed — the base class already sets it correctly. But if you do override it with the old name, `instructionType` will silently resolve to `null` and jobs will fail with: `Argument #2 ($string) must be of type string, null given`.

#### New Interfaces: Assignable and Executable

Setter types now implement the new `Assignable` and/or `Executable` interfaces for the two-phase job processing pattern:

- `Assignable::assignJobs(): Collection` — splits work into child `ProcessInstruction` jobs
- `Executable::execute(array $config): void` — processes a single chunk of work

**Affected setter types:**

| Setter Type | Implements |
|-------------|-----------|
| `ApiImport` | `Assignable`, `Executable` |
| `Clean` | `Assignable`, `Executable` |
| `CsvImport` | `Assignable`, `Executable` |
| `Transform` | `Assignable`, `Executable` |
| `Upload` | `Executable` |
| `FtpFileImport` | `Assignable`, `Executable` |

#### Renamed Methods in Setter Types

| Setter Type | Old Method | New Method |
|-------------|-----------|------------|
| `ApiImport` | `getJobs()` | `assignJobs()` |
| `Clean` | `prepareJobs(Project)` | `assignJobs()` |
| `CsvImport` | `getJobs()` | `assignJobs()` |
| `Transform` | `getJobs()` | `assignJobs()` |

If your project overrides any of these methods, rename them and update the signature to match the `Assignable` interface.

### Upgrading Queued Jobs

After upgrading, clear any pending jobs from the queue before restarting workers. Old serialized jobs from v12 will fail because the removed job classes no longer exist.

```bash
php artisan horizon:clear
php artisan queue:flush
php artisan horizon:terminate
```