<?php
declare(strict_types=1);

namespace App\Services\ProductService;

use App\Helpers\ResponseError;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductProperty;
use App\Models\Settings;
use App\Models\Tag;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Exception;
use Str;
use Throwable;

class ProductService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            if (
                !empty(data_get($data, 'category_id')) &&
                $this->checkIsParentCategory((int)data_get($data, 'category_id'))
            ) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => __('errors.'. ResponseError::CATEGORY_IS_PARENT, locale: $this->language)
                ];
            }

            $autoApprove = Settings::where('key', 'product_auto_approve')->first()?->value;

            if ($autoApprove) {
                $data['status'] = Product::PUBLISHED;
                $data['active'] = true;
            }

            /** @var Product $product */
            $product = $this->model()->create($data);

            $this->setTranslations($product, $data);

            if (data_get($data, 'meta')) {
                $product->setMetaTags($data);
            }

            if (data_get($data, 'images.0')) {
                $product->update(['img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                $product->uploads(data_get($data, 'images'));
            }

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $product->loadMissing([
                    'translations',
                    'metaTags',
                ])
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'code' => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function update(string $uuid, array $data): array
    {
        try {

            if (
                !empty(data_get($data, 'category_id')) &&
                $this->checkIsParentCategory((int)data_get($data, 'category_id'))
            ) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_502,
                    'message' => __('errors.'. ResponseError::CATEGORY_IS_PARENT, locale: $this->language)
                ];
            }

            $product = $this->model()->firstWhere('uuid', $uuid);

            if (empty($product)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            $data['status_note'] = null;

            /** @var Product $product */
            $product->update($data);

            $this->setTranslations($product, $data);

            if (data_get($data, 'meta')) {
                $product->setMetaTags($data);
            }

            if (data_get($data, 'images.0')) {
                $product->galleries()->delete();
                $product->update([ 'img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                $product->uploads(data_get($data, 'images'));
            }

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $product->loadMissing([
                    'translations',
                    'metaTags',
                ])
            ];
        } catch (Throwable $e) {
            return [
                'status'    => false,
                'code'      => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message'   => $e->getMessage()
            ];
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function parentSync(array $data): array
    {
        $errorIds = [];

        foreach (data_get($data, 'products') as $parentId) {

            try {
                $parentId = (int)$parentId;

                DB::transaction(function () use ($parentId, $data) {

                    /** @var Product $parent */

                    $parent = Product::with([
                        'translations',
                        'tags.translations',
                        'metaTags',
                        'galleries',
                        'properties',
                    ])->find($parentId);

                    if (!empty($parent->parent_id)) {
                        throw new Exception('product is child');
                    }

                    $clone = $parent->replicate();

                    $clone['parent_id']  = $parent->id;
                    $clone['shop_id']    = data_get($data, 'shop_id');
                    $clone['uuid']       = Str::uuid();

                    $clone = Product::updateOrCreate([
                        'parent_id' => $parent->id,
                        'shop_id'   => data_get($data, 'shop_id'),
                    ], $clone->getAttributes());

                    $translations   = $parent->translations;
                    $tags           = $parent->tags;
                    $metaTags       = $parent->metaTags;
                    $galleries      = $parent->galleries;
                    $properties     = $parent->properties;

                    foreach ($translations as $translation) {

                        $clone->translations()->updateOrCreate([
                            'locale'        => $translation->locale,
                            'product_id'    => $clone->id,
                        ], [
                            'locale'        => $translation->locale,
                            'product_id'    => $clone->id,
                            'title'         => $translation->title,
                            'description'   => $translation->description,
                        ]);

                    }

                    foreach ($metaTags as $metaTag) {

                        $clone->metaTags()->updateOrCreate([
                            'model_id'   => $clone->id,
                            'model_type' => get_class($clone)
                        ], [
                            'path'          => data_get($metaTag, 'path'),
                            'title'         => data_get($metaTag, 'title'),
                            'keywords'      => data_get($metaTag, 'keywords'),
                            'description'   => data_get($metaTag, 'description'),
                            'h1'            => data_get($metaTag, 'h1'),
                            'seo_text'      => data_get($metaTag, 'seo_text'),
                            'canonical'     => data_get($metaTag, 'canonical'),
                            'robots'        => data_get($metaTag, 'robots'),
                            'change_freq'   => data_get($metaTag, 'change_freq'),
                            'priority'      => data_get($metaTag, 'priority'),
                        ]);

                    }

                    $clone->galleries()->delete();

                    foreach ($galleries as $gallery) {

                        $newGallery = $gallery->toArray();
                        $newGallery['loadable_id'] = $clone->id;
                        $newGallery['loadable_type'] = get_class($clone);

                        $clone->galleries()->create($newGallery);
                    }

                    foreach ($tags as $tag) {

                        /** @var Tag $newTag */
                        $newTag = $clone->tags()->updateOrCreate([
                            'product_id' => $clone->id
                        ], [
                            'active'     => $tag->active,
                        ]);

                        foreach ($tag->translations as $translation) {
                            $newTag->translations()->updateOrCreate([
                                'locale'        => $translation->locale,
                                'title'         => $translation->title,
                                'description'   => $translation->description,
                            ]);
                        }

                    }

                    foreach ($properties as $property) {
                        ProductProperty::updateOrCreate([
                            'product_id'        => $clone->id,
                            'property_group_id' => $property->property_group_id,
                            'property_value_id' => $property->property_value_id,
                        ], [
                            'value'      => $property->value,
                        ]);
                    }

                });
            } catch (Throwable $e) {
                $errorIds[] = [
                    'id'        => $parentId,
                    'message'   => $e->getMessage()
                ];
            }

        }

        if (count($errorIds) > 0) {
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'data' => $errorIds];
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'message' => ResponseError::NO_ERROR];
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     * @return array
     */
    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $products = Product::whereIn('id', $ids)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        $errorIds = [];

        foreach ($products as $product) {
            try {
                /** @var Product $product */
                $product->delete();
            } catch (Throwable $e) {
                if (!empty($e->getMessage())) { // this if only for vercel test demo
                    $errorIds[] = $product->id;
                }
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return ['status' => false, 'code' => ResponseError::ERROR_505, 'message' => implode(', ', $errorIds)];
    }

    private function checkIsParentCategory(int|string $categoryId): bool
    {
        $parentCategory = Category::firstWhere('parent_id', $categoryId);

        return !!data_get($parentCategory, 'id');
    }

    public function setStatus(string $uuid, array $data): array
    {
        /** @var Product $product */
        $product = Product::with(['stocks'])->where('uuid', $uuid)->first();

        if (!$product) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $actual = $product->stocks?->where('quantity', '>', 0)?->first()?->id;

        if (!$actual) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_430,
                'message' => __('errors.' . ResponseError::ERROR_430, locale: $this->language)
            ];
        }

        $product->update([
            'status'      => data_get($data, 'status'),
            'status_note' => data_get($data, 'status_note', '')
        ]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $product
        ];

    }
}
