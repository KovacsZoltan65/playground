<?php

namespace App\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

class ActivityLogData extends Data
{
    public function __construct(
        public int $id,
        public string $log_name,
        public ?string $event,
        public string $description,
        public ?string $subject_type,
        public ?int $subject_id,
        public ?string $subject_label,
        public ?string $causer_type,
        public ?int $causer_id,
        public ?string $causer_label,
        public ?string $batch_uuid,
        public array $properties,
        public ?string $created_at,
        public ?string $updated_at,
    ) {
    }

    public static function fromModel(Activity $activity): self
    {
        $activity->loadMissing(['causer', 'subject']);

        return new self(
            id: $activity->id,
            log_name: (string) $activity->log_name,
            event: $activity->event,
            description: (string) $activity->description,
            subject_type: $activity->subject_type,
            subject_id: $activity->subject_id ? (int) $activity->subject_id : null,
            subject_label: self::resolveModelLabel($activity->subject),
            causer_type: $activity->causer_type,
            causer_id: $activity->causer_id ? (int) $activity->causer_id : null,
            causer_label: self::resolveModelLabel($activity->causer),
            batch_uuid: $activity->batch_uuid,
            properties: $activity->properties?->toArray() ?? [],
            created_at: $activity->created_at?->toDateTimeString(),
            updated_at: $activity->updated_at?->toDateTimeString(),
        );
    }

    private static function resolveModelLabel(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }

        foreach (['name', 'title', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($model::class).' #'.$model->getKey();
    }
}
