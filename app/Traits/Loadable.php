<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * @property Collection|Gallery[] $galleries
 * @property Gallery|null $gallery
 * @property int $galleries_count
 */
trait Loadable
{
    public function uploads($files): void
    {
        foreach ($files as $key => $file) {

            $file   = str_replace(config('app.img_host'), '', $file);

            $title  = Str::of($file)->after('/');
            $keys = explode('/', $file);

            $type = collect(array_intersect($keys, Gallery::TYPES))->first() ?? 'other';

            $image          = new Gallery();
            $image->title   = $title;
            $image->path    = config('app.img_host') . $file;
            $image->type    = $type;
            $image->size    = data_get($file, 'size');
            $image->mime    = data_get($file, 'mimeType');
            $image->preview = request("previews.$key");

            $this->galleries()->save($image);
        }
    }

    public function galleries(): MorphMany
    {
        return $this->morphMany(Gallery::class, 'loadable');
    }

    public function gallery(): MorphOne
    {
        return $this->morphOne(Gallery::class, 'loadable');
    }
}

