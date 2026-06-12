<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Demo shop content for a fresh clone. Replace with real catalogue data when
 * branding the template. Placeholder images are generated locally with GD so
 * seeding needs no network access and the repo carries no binaries.
 */
class DemoCatalogueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        $categories = collect([
            ['name' => 'Apparel', 'description' => 'Wearables for every day.'],
            ['name' => 'Homeware', 'description' => 'Things for the home.'],
            ['name' => 'Accessories', 'description' => 'The finishing touches.'],
            ['name' => 'Gifts', 'description' => 'Easy wins for any occasion.'],
        ])->map(fn (array $data, int $index): Category => Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'],
            'position' => $index,
            'is_active' => true,
        ]));

        $this->seedSimpleProducts($categories);
        $this->seedVariantProducts($categories);
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    protected function seedSimpleProducts($categories): void
    {
        $products = [
            ['Enamel Mug', 'Homeware', 1450, 'A sturdy enamel mug for camp or kitchen. Dishwasher safe, near indestructible.'],
            ['Oak Chopping Board', 'Homeware', 3900, 'Solid oak board, oiled and ready for bread, cheese, or chopping.'],
            ['Linen Tea Towel', 'Homeware', 1200, 'Heavyweight linen tea towel that gets softer with every wash.'],
            ['Canvas Tote', 'Accessories', 1800, 'A 12oz canvas tote with reinforced handles. Carries the big shop.'],
            ['Leather Keyring', 'Accessories', 950, 'Vegetable-tanned leather keyring, brass hardware, stamped by hand.'],
            ['Soy Candle', 'Gifts', 2200, 'Hand-poured soy candle, 40-hour burn, cedar and bergamot.'],
            ['Notebook Set', 'Gifts', 1500, 'Three pocket notebooks with dotted pages and a lay-flat spine.'],
        ];

        foreach ($products as $index => [$name, $categoryName, $price, $description]) {
            $product = $this->createProduct($name, $description, $categories, $categoryName, $index);

            $product->variants()->create([
                'sku' => $this->sku($name),
                'price' => $price,
                'stock' => random_int(8, 40),
                'is_default' => true,
            ]);
        }
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    protected function seedVariantProducts($categories): void
    {
        $shirts = [
            ['Organic Cotton Tee', 'Apparel', 2500, 'A boxy-fit organic cotton tee, combed and ring-spun. Pre-shrunk.'],
            ['Heavyweight Hoodie', 'Apparel', 6500, 'A 450gsm brushed-back hoodie that holds its shape.'],
            ['Merino Beanie', 'Apparel', 2800, 'Extra-fine merino beanie, knitted in a single seamless piece.'],
        ];

        foreach ($shirts as $index => [$name, $categoryName, $basePrice, $description]) {
            $product = $this->createProduct($name, $description, $categories, $categoryName, $index + 10);

            $sizeOption = $product->options()->create(['name' => 'Size', 'position' => 0]);
            $sizes = collect(['S', 'M', 'L', 'XL'])->map(
                fn (string $size, int $position) => $sizeOption->values()->create([
                    'value' => $size,
                    'position' => $position,
                ]),
            );

            $colourOption = $product->options()->create(['name' => 'Colour', 'position' => 1]);
            $colours = collect(['Black', 'Ecru'])->map(
                fn (string $colour, int $position) => $colourOption->values()->create([
                    'value' => $colour,
                    'position' => $position,
                ]),
            );

            $position = 0;
            foreach ($sizes as $size) {
                foreach ($colours as $colour) {
                    $variant = $product->variants()->create([
                        'sku' => $this->sku("{$name} {$size->value} {$colour->value}"),
                        'price' => $basePrice + ($size->value === 'XL' ? 200 : 0),
                        'stock' => random_int(0, 25),
                        'is_default' => $position === 0,
                        'position' => $position++,
                    ]);

                    $variant->optionValues()->attach([$size->id, $colour->id]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    protected function createProduct(string $name, string $description, $categories, string $categoryName, int $seed): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description."\n\nThis is demo content seeded by the shop template — replace it with your own catalogue.",
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays($seed + 1),
        ]);

        $category = $categories->firstWhere('name', $categoryName);

        if ($category !== null) {
            $product->categories()->attach($category);
        }

        $imagePath = $this->generatePlaceholderImage($product->slug, $seed);

        $product->addMedia($imagePath)
            ->usingFileName("{$product->slug}.webp")
            ->toMediaCollection('images');

        return $product;
    }

    /**
     * Generate a calm two-tone placeholder image with GD.
     */
    protected function generatePlaceholderImage(string $slug, int $seed): string
    {
        $size = 1280;
        $image = imagecreatetruecolor($size, $size);

        $hues = [
            [226, 232, 240], [254, 243, 199], [220, 252, 231], [224, 231, 255],
            [255, 228, 230], [240, 253, 250], [254, 226, 226], [241, 245, 249],
            [236, 253, 245], [255, 237, 213],
        ];

        [$r, $g, $b] = $hues[$seed % count($hues)];
        $background = imagecolorallocate($image, $r, $g, $b);
        $accent = imagecolorallocate($image, max(0, $r - 60), max(0, $g - 60), max(0, $b - 60));

        if ($background === false || $accent === false) {
            throw new RuntimeException('GD could not allocate placeholder colours.');
        }

        imagefill($image, 0, 0, $background);
        imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.6), (int) ($size * 0.6), $accent);

        $path = storage_path("app/seed-{$slug}.webp");
        imagewebp($image, $path, 80);
        imagedestroy($image);

        return $path;
    }

    /**
     * Deterministic-ish SKU from a name.
     */
    protected function sku(string $name): string
    {
        return Str::of($name)->slug()->upper()->replace('-', '')->limit(12, '')->append('-', (string) random_int(100, 999))->toString();
    }
}
