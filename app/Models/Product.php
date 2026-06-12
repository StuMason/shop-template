<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property ProductStatus $status
 * @property bool $vat_zero_rated
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'description', 'status', 'vat_zero_rated', 'meta_title', 'meta_description', 'published_at'])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia, Searchable;

    /**
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /**
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->orderByDesc('is_default')->orderBy('position');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ProductStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->withResponsiveImages()
            ->nonQueued()
            ->fit(Fit::Crop, 640, 640)
            ->format('webp');

        $this->addMediaConversion('large')
            ->withResponsiveImages()
            ->nonQueued()
            ->fit(Fit::Max, 1280, 1280)
            ->format('webp');
    }

    /**
     * Image payload for the frontend <ProductImage> component. Conversion
     * URLs and srcsets are resolved server-side so SSR and hydration agree.
     *
     * @return array{src: string, srcset: string, alt: string}|null
     */
    public function imagePayload(string $conversion = 'thumb'): ?array
    {
        $media = $this->getFirstMedia('images');

        if ($media === null) {
            return null;
        }

        return [
            'src' => $media->getUrl($conversion),
            'srcset' => $media->getSrcset($conversion),
            'alt' => $this->name,
        ];
    }

    /**
     * All product images for the detail page gallery.
     *
     * @return array<int, array{id: int, src: string, srcset: string, alt: string}>
     */
    public function galleryPayload(string $conversion = 'large'): array
    {
        return $this->getMedia('images')
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'src' => $media->getUrl($conversion),
                'srcset' => $media->getSrcset($conversion),
                'alt' => $this->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'vat_zero_rated' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
