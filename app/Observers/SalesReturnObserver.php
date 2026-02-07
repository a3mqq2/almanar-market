<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class SalesReturnObserver extends SyncObserver
{
    protected function getPayload(Model $model): array
    {
        $data = $model->toArray();

        if ($model->relationLoaded('items')) {
            $data['items'] = $model->items->toArray();
        }

        return $data;
    }
}
