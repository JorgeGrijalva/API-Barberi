<?php
declare(strict_types=1);

namespace App\Repositories\EmailTemplateRepository;

use App\Models\EmailTemplate;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\Cache;

class EmailTemplateRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return EmailTemplate::class;
    }

    public function paginate(array $filter) {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }
        return $this->model()->paginate($filter['perPage'] ?? 10);
    }

    public function show(EmailTemplate $emailTemplate): EmailTemplate
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        return $emailTemplate->loadMissing(['emailSetting']);
    }
}
