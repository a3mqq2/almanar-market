<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class SaleObserver extends SyncObserver
{
    protected function getPayload(Model $model): array
    {
        $data = $model->toArray();

        if ($model->relationLoaded('items')) {
            $data['items'] = $model->items->toArray();
        }

        if ($model->relationLoaded('payments')) {
            $data['payments'] = $model->payments->toArray();
        }

        return $data;
    }
}
