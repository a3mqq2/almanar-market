<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ShiftObserver extends SyncObserver
{
    protected function getPayload(Model $model): array
    {
        $data = $model->toArray();

        if ($model->relationLoaded('cashboxes')) {
            $data['cashboxes'] = $model->cashboxes->toArray();
        }

        return $data;
    }
}
