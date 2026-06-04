<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\LoadsUpload;
use App\Models\PayslipUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ActivityChangeLogger
{
    private const HIDDEN_FIELDS = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function created(Model $model): void
    {
        self::record($model, 'Inserted', 'created a new');
    }

    public static function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except(self::HIDDEN_FIELDS)
            ->keys()
            ->values()
            ->all();

        if (empty($changes)) {
            return;
        }

        if (self::isScannedUploadStatusUpdate($model, $changes)) {
            return;
        }

        self::record($model, 'Updated', 'updated', $changes);
    }

    public static function deleted(Model $model): void
    {
        self::record($model, 'Deleted', 'deleted');
    }

    public static function scannedFile(Model $model, int $processedRows = 0, ?string $label = null): void
    {
        $actor = request()->user();
        if (!$actor) {
            return;
        }

        $modelName = $label ?: self::modelLabel($model);
        $subjectName = self::subjectName($model);
        $rowsText = $processedRows > 0 ? ' Processed rows: '.number_format($processedRows).'.' : '';

        self::record(
            $model,
            'Scanned',
            'scanned',
            [],
            'Scanned '.$modelName,
            self::actorName($actor).' scanned '.$modelName.' '.$subjectName.'.'.$rowsText
        );
    }

    public static function downloadedFile(Model $model, ?string $label = null): void
    {
        $actor = request()->user();
        if (!$actor) {
            return;
        }

        $modelName = $label ?: self::modelLabel($model);
        $subjectName = self::subjectName($model);

        self::record(
            $model,
            'Downloaded',
            'downloaded',
            [],
            'Downloaded '.$modelName,
            self::actorName($actor).' downloaded '.$modelName.' '.$subjectName.'.'
        );
    }

    private static function record(
        Model $model,
        string $event,
        string $verb,
        array $changedFields = [],
        ?string $action = null,
        ?string $description = null
    ): void
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        $request = request();
        if (!$request instanceof Request) {
            return;
        }

        $actor = $request->user();
        if (!$actor) {
            return;
        }

        $role = strtolower(trim((string) ($actor->role ?? '')));
        if (!in_array($role, ['admin', 'administrator', 'employee'], true)) {
            return;
        }

        $modelName = self::modelLabel($model);
        $subjectName = self::subjectName($model);
        $routeName = (string) optional($request->route())->getName();
        $path = '/'.ltrim($request->path(), '/');
        $fieldsText = empty($changedFields) ? '' : ' Fields changed: '.implode(', ', array_map([self::class, 'fieldLabel'], $changedFields)).'.';
        $actorName = self::actorName($actor);

        ActivityLog::query()->create([
            'user_id' => $actor->id,
            'user_name' => $actorName,
            'user_email' => $actor->email,
            'user_role' => self::actorRole($actor),
            'method' => $event,
            'route_name' => $routeName !== '' ? $routeName : null,
            'path' => $path,
            'action' => $action ?: $event.' '.$modelName,
            'description' => $description ?: $actorName.' '.$verb.' '.$modelName.' '.$subjectName.'.'.$fieldsText,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    private static function actorName(object $user): string
    {
        $name = trim(implode(' ', array_filter([
            trim((string) ($user->first_name ?? '')),
            trim((string) ($user->middle_name ?? '')),
            trim((string) ($user->last_name ?? '')),
        ])));

        return $name !== '' ? $name : (string) ($user->email ?? 'Unknown user');
    }

    private static function actorRole(object $user): string
    {
        $role = trim((string) ($user->role ?? ''));
        $departmentHeadStatus = strtolower(trim((string) ($user->department_head ?? '')));

        if (strtolower($role) === 'employee' && $departmentHeadStatus === 'approved') {
            return 'Department Head';
        }

        return $role !== '' ? $role : 'User';
    }

    private static function modelLabel(Model $model): string
    {
        return Str::of(class_basename($model))
            ->snake(' ')
            ->headline()
            ->toString();
    }

    private static function fieldLabel(string $field): string
    {
        return Str::of($field)
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    private static function subjectName(Model $model): string
    {
        $attributes = $model->getAttributes();
        $value = Arr::first([
            $attributes['employee_id'] ?? null,
            $attributes['original_name'] ?? null,
            $attributes['filename'] ?? null,
            trim(implode(' ', array_filter([
                $attributes['first_name'] ?? null,
                $attributes['middle_name'] ?? null,
                $attributes['last_name'] ?? null,
            ]))),
            $attributes['name'] ?? null,
            $attributes['title'] ?? null,
            $attributes['email'] ?? null,
        ], fn ($item) => trim((string) $item) !== '');

        if ($value) {
            return '"'.trim((string) $value).'"';
        }

        return '#'.$model->getKey();
    }

    private static function isScannedUploadStatusUpdate(Model $model, array $changes): bool
    {
        if (!($model instanceof PayslipUpload || $model instanceof LoadsUpload)) {
            return false;
        }

        return empty(array_diff($changes, ['status', 'processed_rows']));
    }
}
