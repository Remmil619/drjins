<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class RecordObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', array_keys($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', array_keys($model->getChanges()));
    }

    private function record(Model $model, string $action, array $fields): void
    {
        $fields = array_values(array_diff($fields, ['updated_at', 'created_at', 'synced_at', 'sync_pending', 'request_key']));
        if (! $fields) {
            return;
        }
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'record_type' => class_basename($model),
            'record_id' => $model->getKey(),
            'changed_fields' => $fields,
            'created_at' => now(),
        ]);
    }
}
