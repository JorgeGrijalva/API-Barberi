<?php
declare(strict_types=1);

namespace App\Repositories\InviteRepository;

use App\Models\Invitation;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class InviteRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Invitation::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('invitations', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with([
                'user.roles',
                'user' => fn($q) => $q->select('id', 'firstname', 'lastname', 'img'),
                'shop.translation' => function($q) use($locale) {
                    $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }));
                }
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Invitation $invitation
     * @return Invitation
     */
    public function show(Invitation $invitation): Invitation
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $invitation->loadMissing([
            'user.roles',
            'user' => fn($q) => $q->select('id', 'firstname', 'lastname', 'img'),
            'shop.translation' => function($q) use($locale) {
                $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }));
            }
        ]);
    }

}
