<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Product;
use App\Models\Profit;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        Cart::truncate();
        CustomerCredit::truncate();
        SalesReturnItem::truncate();
        SalesReturn::truncate();
        CashierShift::truncate();
        StockMutation::truncate();
        ReceivablePayment::truncate();
        PayablePayment::truncate();
        Receivable::truncate();
        Payable::truncate();
        TransactionDetail::truncate();
        Profit::truncate();
        Transaction::truncate();
        Product::truncate();
        Category::truncate();
        Customer::truncate();
        Supplier::truncate();

        Schema::enableForeignKeyConstraints();

        // Ensure storage directories exist
        Storage::disk('public')->makeDirectory('category');
        Storage::disk('public')->makeDirectory('products');

        $this->command->info('Seeding customers (member panahan)...');
        $customers = $this->seedCustomers();

        $this->command->info('Seeding suppliers (supplier peralatan panahan)...');
        $suppliers = $this->seedSuppliers();

        $this->command->info('Seeding categories with images...');
        $categories = $this->seedCategories();

        $this->command->info('Seeding products (peralatan panahan) with images...');
        $products = $this->seedProducts($categories);

        $this->command->info('Seeding transactions...');
        $this->seedTransactions($customers, $products);

        $this->command->info('Seeding receivables...');
        $this->seedReceivables($customers);

        $this->command->info('Seeding payables...');
        $this->seedPayables($suppliers);

        $this->command->info('Sample data seeding completed!');
    }

    /**
     * Download image from URL and save to storage
     */
    private function downloadImage(string $url, string $folder, string $filename): ?string
    {
        try {
            $this->command->info("  Downloading: {$filename}...");

            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $extension = 'jpg';
                $fullFilename = $filename.'.'.$extension;

                Storage::disk('public')->put(
                    $folder.'/'.$fullFilename,
                    $response->body()
                );

                return $fullFilename;
            }
        } catch (\Exception $e) {
            $this->command->warn("  Failed to download {$filename}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Seed master customers (member/atlet panahan).
     */
    private function seedCustomers(): Collection
    {
        $customers = collect([
            ['name' => 'Raka Aditya', 'no_telp' => '081211111111', 'address' => 'Jl. Stadion No. 5, Bandung', 'password' => 'password'],
            ['name' => 'Sari Indah Lestari', 'no_telp' => '081312345678', 'address' => 'Jl. Senayan No. 12, Jakarta', 'password' => 'password'],
            ['name' => 'Bayu Pratama', 'no_telp' => '081512340000', 'address' => 'Jl. GOR Panahan No. 3, Surabaya', 'password' => 'password'],
            ['name' => 'Dian Permata', 'no_telp' => '085612349911', 'address' => 'Jl. Kridosono No. 8, Yogyakarta', 'password' => 'password'],
            ['name' => 'Fajar Nugroho', 'no_telp' => '087712348822', 'address' => 'Jl. Manahan No. 15, Solo'],
            ['name' => 'Anisa Rahma', 'no_telp' => '082213345566', 'address' => 'Jl. Jakabaring No. 22, Palembang', 'password' => 'password'],
            ['name' => 'Gilang Ramadhan', 'no_telp' => '081399887766', 'address' => 'Jl. Arcamanik No. 9, Bandung'],
            ['name' => 'Putri Wulandari', 'no_telp' => '085544332211', 'address' => 'Jl. Kenari No. 4, Denpasar'],
        ]);

        return $customers
            ->map(fn ($customer) => Customer::create($customer))
            ->keyBy('name');
    }

    /**
     * Seed master suppliers (supplier peralatan panahan).
     */
    private function seedSuppliers(): Collection
    {
        $suppliers = collect([
            ['name' => 'PT Hoyt Indonesia', 'phone' => '0215551001', 'email' => 'sales@hoytindonesia.test', 'address' => 'Jl. Industri Archery No. 10, Jakarta'],
            ['name' => 'CV Win&Win Archery Supply', 'phone' => '0225551002', 'email' => 'order@winwinarchery.test', 'address' => 'Jl. Soekarno Hatta No. 88, Bandung'],
            ['name' => 'PT Easton Arrow Indonesia', 'phone' => '0315551003', 'email' => 'hello@eastonarrow.test', 'address' => 'Jl. Raya Darmo No. 21, Surabaya'],
            ['name' => 'UD Panahan Nusantara', 'phone' => '0245551004', 'email' => 'admin@panahannusantara.test', 'address' => 'Jl. Pandanaran No. 45, Semarang'],
        ]);

        return $suppliers
            ->map(fn ($supplier) => Supplier::create($supplier))
            ->keyBy('name');
    }

    /**
     * Seed master categories with downloaded images.
     */
    private function seedCategories(): Collection
    {
        $categories = collect([
            [
                'name' => 'Busur (Bow)',
                'description' => 'Berbagai jenis busur panah: recurve, compound, dan tradisional',
                'image_url' => 'https://images.unsplash.com/photo-1515552324236-3d42e81a0e49?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Anak Panah (Arrow)',
                'description' => 'Arrow shaft, vane, nock, dan point untuk berbagai kebutuhan',
                'image_url' => 'https://images.unsplash.com/photo-1565711561500-49678a10a63f?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Sight & Stabilizer',
                'description' => 'Alat bidik dan stabilizer untuk meningkatkan akurasi',
                'image_url' => 'https://images.unsplash.com/photo-1568702846914-96b305d2aaeb?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Pelindung (Protection)',
                'description' => 'Arm guard, finger tab, chest guard, dan pelindung lainnya',
                'image_url' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Target & Face',
                'description' => 'Target butt, target face, dan perlengkapan target',
                'image_url' => 'https://images.unsplash.com/photo-1536500152956-3308b3a56d2f?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Tas & Case',
                'description' => 'Bow case, arrow tube, quiver, dan tas perlengkapan',
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'String & Aksesoris',
                'description' => 'Bow string, string wax, nocking point, dan aksesoris lainnya',
                'image_url' => 'https://images.unsplash.com/photo-1584285426746-5f3c0c6f0e7a?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Apparel & Merchandise',
                'description' => 'Jersey, kaos, topi, dan merchandise klub panahan',
                'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop',
            ],
        ]);

        return $categories->map(function ($category) {
            $slug = Str::slug($category['name']);
            $image = $this->downloadImage(
                $category['image_url'],
                'category',
                'cat-'.$slug
            );

            return Category::create([
                'name' => $category['name'],
                'description' => $category['description'],
                'image' => $image ?? 'default.jpg',
            ]);
        })->keyBy('name');
    }

    /**
     * Seed products mapped to categories with downloaded images.
     */
    private function seedProducts(Collection $categories): Collection
    {
        $products = collect([
            // Busur (Bow)
            ['category' => 'Busur (Bow)', 'barcode' => 'BOW-0001', 'title' => 'Recurve Bow IHC Pro 68"', 'description' => 'Busur recurve aluminium riser 25" dengan limb 68 inci untuk atlet kompetisi', 'buy_price' => 3500000, 'sell_price' => 4500000, 'stock' => 10, 'image_url' => 'https://images.unsplash.com/photo-1515552324236-3d42e81a0e49?w=300&h=300&fit=crop'],
            ['category' => 'Busur (Bow)', 'barcode' => 'BOW-0002', 'title' => 'Recurve Bow Pemula 54"', 'description' => 'Busur recurve fiberglass untuk pemula dan latihan dasar', 'buy_price' => 450000, 'sell_price' => 650000, 'stock' => 25, 'image_url' => 'https://images.unsplash.com/photo-1565711561500-49678a10a63f?w=300&h=300&fit=crop'],
            ['category' => 'Busur (Bow)', 'barcode' => 'BOW-0003', 'title' => 'Compound Bow Hunter 50lbs', 'description' => 'Compound bow adjustable 30-50 lbs untuk hunting dan target', 'buy_price' => 5200000, 'sell_price' => 6800000, 'stock' => 5, 'image_url' => 'https://images.unsplash.com/photo-1547483238-f400e65ccd56?w=300&h=300&fit=crop'],
            ['category' => 'Busur (Bow)', 'barcode' => 'BOW-0004', 'title' => 'Traditional Bow Horsebow', 'description' => 'Busur tradisional horsebow fiberglass-bamboo laminate', 'buy_price' => 800000, 'sell_price' => 1200000, 'stock' => 15, 'image_url' => 'https://images.unsplash.com/photo-1531746790095-e6e5e98317ad?w=300&h=300&fit=crop'],

            // Anak Panah (Arrow)
            ['category' => 'Anak Panah (Arrow)', 'barcode' => 'ARW-0001', 'title' => 'Easton X10 Arrow (6pcs)', 'description' => 'Arrow carbon premium untuk kompetisi internasional, spine 600', 'buy_price' => 2800000, 'sell_price' => 3600000, 'stock' => 20, 'image_url' => 'https://images.unsplash.com/photo-1565711561500-49678a10a63f?w=300&h=300&fit=crop'],
            ['category' => 'Anak Panah (Arrow)', 'barcode' => 'ARW-0002', 'title' => 'Aluminium Arrow 1816 (12pcs)', 'description' => 'Arrow aluminium untuk latihan dan pemula, spine 1816', 'buy_price' => 350000, 'sell_price' => 500000, 'stock' => 50, 'image_url' => 'https://images.unsplash.com/photo-1547483238-f400e65ccd56?w=300&h=300&fit=crop'],
            ['category' => 'Anak Panah (Arrow)', 'barcode' => 'ARW-0003', 'title' => 'Carbon Arrow Spine 500 (6pcs)', 'description' => 'Arrow full carbon untuk intermediate archer', 'buy_price' => 600000, 'sell_price' => 850000, 'stock' => 35, 'image_url' => 'https://images.unsplash.com/photo-1531746790095-e6e5e98317ad?w=300&h=300&fit=crop'],
            ['category' => 'Anak Panah (Arrow)', 'barcode' => 'ARW-0004', 'title' => 'Wooden Arrow Tradisional (6pcs)', 'description' => 'Arrow kayu cedar untuk busur tradisional', 'buy_price' => 180000, 'sell_price' => 280000, 'stock' => 40, 'image_url' => 'https://images.unsplash.com/photo-1515552324236-3d42e81a0e49?w=300&h=300&fit=crop'],

            // Sight & Stabilizer
            ['category' => 'Sight & Stabilizer', 'barcode' => 'SGT-0001', 'title' => 'Recurve Sight Shibuya Ultima', 'description' => 'Sight recurve premium micro-adjustable untuk kompetisi', 'buy_price' => 4200000, 'sell_price' => 5500000, 'stock' => 8, 'image_url' => 'https://images.unsplash.com/photo-1568702846914-96b305d2aaeb?w=300&h=300&fit=crop'],
            ['category' => 'Sight & Stabilizer', 'barcode' => 'SGT-0002', 'title' => 'Stabilizer Long Rod 30"', 'description' => 'Stabilizer carbon 30 inch dengan damper', 'buy_price' => 1500000, 'sell_price' => 2100000, 'stock' => 12, 'image_url' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?w=300&h=300&fit=crop'],
            ['category' => 'Sight & Stabilizer', 'barcode' => 'SGT-0003', 'title' => 'V-Bar & Side Rod Set', 'description' => 'V-bar connector dengan side rod 12 inch sepasang', 'buy_price' => 900000, 'sell_price' => 1350000, 'stock' => 15, 'image_url' => 'https://images.unsplash.com/photo-1536500152956-3308b3a56d2f?w=300&h=300&fit=crop'],

            // Pelindung (Protection)
            ['category' => 'Pelindung (Protection)', 'barcode' => 'PRT-0001', 'title' => 'Arm Guard Leather Pro', 'description' => 'Pelindung lengan kulit asli untuk archer profesional', 'buy_price' => 120000, 'sell_price' => 185000, 'stock' => 40, 'image_url' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?w=300&h=300&fit=crop'],
            ['category' => 'Pelindung (Protection)', 'barcode' => 'PRT-0002', 'title' => 'Finger Tab Cordovan', 'description' => 'Finger tab kulit cordovan dengan plate aluminium', 'buy_price' => 250000, 'sell_price' => 380000, 'stock' => 30, 'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=300&fit=crop'],
            ['category' => 'Pelindung (Protection)', 'barcode' => 'PRT-0003', 'title' => 'Chest Guard Mesh', 'description' => 'Pelindung dada mesh breathable untuk kenyamanan saat latihan', 'buy_price' => 85000, 'sell_price' => 135000, 'stock' => 35, 'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&h=300&fit=crop'],

            // Target & Face
            ['category' => 'Target & Face', 'barcode' => 'TGT-0001', 'title' => 'Target Butt Straw 90cm', 'description' => 'Target butt jerami diameter 90cm untuk latihan indoor/outdoor', 'buy_price' => 450000, 'sell_price' => 650000, 'stock' => 20, 'image_url' => 'https://images.unsplash.com/photo-1536500152956-3308b3a56d2f?w=300&h=300&fit=crop'],
            ['category' => 'Target & Face', 'barcode' => 'TGT-0002', 'title' => 'Target Face WA 122cm (10pcs)', 'description' => 'Target face resmi World Archery 122cm untuk jarak 70m', 'buy_price' => 150000, 'sell_price' => 220000, 'stock' => 60, 'image_url' => 'https://images.unsplash.com/photo-1536500152956-3308b3a56d2f?w=300&h=300&fit=crop'],
            ['category' => 'Target & Face', 'barcode' => 'TGT-0003', 'title' => 'Target Face Indoor 40cm (20pcs)', 'description' => 'Target face indoor 40cm triple spot untuk jarak 18m', 'buy_price' => 80000, 'sell_price' => 125000, 'stock' => 80, 'image_url' => 'https://images.unsplash.com/photo-1536500152956-3308b3a56d2f?w=300&h=300&fit=crop'],

            // Tas & Case
            ['category' => 'Tas & Case', 'barcode' => 'TAS-0001', 'title' => 'Bow Case Recurve Hard', 'description' => 'Hard case recurve bow dengan busa pelindung, muat riser + limb', 'buy_price' => 850000, 'sell_price' => 1200000, 'stock' => 12, 'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=300&fit=crop'],
            ['category' => 'Tas & Case', 'barcode' => 'TAS-0002', 'title' => 'Arrow Tube Carbon', 'description' => 'Tabung arrow carbon fiber muat 12 arrow', 'buy_price' => 280000, 'sell_price' => 420000, 'stock' => 20, 'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=300&fit=crop'],
            ['category' => 'Tas & Case', 'barcode' => 'TAS-0003', 'title' => 'Quiver Hip 3-Tube', 'description' => 'Quiver pinggang 3 tabung untuk field archery', 'buy_price' => 200000, 'sell_price' => 320000, 'stock' => 25, 'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=300&fit=crop'],

            // String & Aksesoris
            ['category' => 'String & Aksesoris', 'barcode' => 'STR-0001', 'title' => 'Bow String BCY 8125 68"', 'description' => 'String Dyneema BCY 8125 untuk recurve bow 68 inci', 'buy_price' => 180000, 'sell_price' => 280000, 'stock' => 30, 'image_url' => 'https://images.unsplash.com/photo-1584285426746-5f3c0c6f0e7a?w=300&h=300&fit=crop'],
            ['category' => 'String & Aksesoris', 'barcode' => 'STR-0002', 'title' => 'String Wax Bohning', 'description' => 'Wax perawatan string untuk memperpanjang umur string', 'buy_price' => 35000, 'sell_price' => 55000, 'stock' => 50, 'image_url' => 'https://images.unsplash.com/photo-1584285426746-5f3c0c6f0e7a?w=300&h=300&fit=crop'],
            ['category' => 'String & Aksesoris', 'barcode' => 'STR-0003', 'title' => 'Nocking Point Brass (10pcs)', 'description' => 'Nocking point kuningan untuk penanda posisi nock', 'buy_price' => 25000, 'sell_price' => 45000, 'stock' => 60, 'image_url' => 'https://images.unsplash.com/photo-1584285426746-5f3c0c6f0e7a?w=300&h=300&fit=crop'],
            ['category' => 'String & Aksesoris', 'barcode' => 'STR-0004', 'title' => 'Arrow Rest Magnetic', 'description' => 'Arrow rest magnetic click untuk recurve bow', 'buy_price' => 75000, 'sell_price' => 120000, 'stock' => 35, 'image_url' => 'https://images.unsplash.com/photo-1584285426746-5f3c0c6f0e7a?w=300&h=300&fit=crop'],

            // Apparel & Merchandise
            ['category' => 'Apparel & Merchandise', 'barcode' => 'APR-0001', 'title' => 'Jersey IHC Archery 2024', 'description' => 'Jersey resmi klub IHC Archery bahan dry-fit', 'buy_price' => 150000, 'sell_price' => 250000, 'stock' => 50, 'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&h=300&fit=crop'],
            ['category' => 'Apparel & Merchandise', 'barcode' => 'APR-0002', 'title' => 'Topi IHC Archery', 'description' => 'Topi baseball dengan logo IHC Archery bordir', 'buy_price' => 45000, 'sell_price' => 85000, 'stock' => 40, 'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&h=300&fit=crop'],
            ['category' => 'Apparel & Merchandise', 'barcode' => 'APR-0003', 'title' => 'Tumbler IHC Archery 500ml', 'description' => 'Tumbler stainless steel dengan logo IHC Archery', 'buy_price' => 65000, 'sell_price' => 120000, 'stock' => 30, 'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&h=300&fit=crop'],
        ]);

        return $products->map(function ($product) use ($categories) {
            $category = $categories->get($product['category']);

            $slug = Str::slug($product['title']);
            $image = $this->downloadImage(
                $product['image_url'],
                'products',
                'prod-'.$slug
            );

            return Product::create([
                'category_id' => $category?->id,
                'image' => $image ?? 'default.jpg',
                'barcode' => $product['barcode'],
                'title' => $product['title'],
                'description' => $product['description'],
                'buy_price' => $product['buy_price'],
                'sell_price' => $product['sell_price'],
                'stock' => $product['stock'],
            ]);
        })->keyBy('barcode');
    }

    /**
     * Seed historical transactions, transaction details, and profits.
     */
    private function seedTransactions(Collection $customers, Collection $products): void
    {
        $cashier = User::where('email', 'kasir@ihcarchery.com')->first() ?? User::first();

        if (! $cashier) {
            return;
        }

        $blueprints = [
            [
                'customer' => 'Raka Aditya',
                'discount' => 50000,
                'cash' => 5000000,
                'items' => [
                    ['barcode' => 'BOW-0002', 'qty' => 1],
                    ['barcode' => 'ARW-0002', 'qty' => 2],
                    ['barcode' => 'PRT-0001', 'qty' => 1],
                ],
            ],
            [
                'customer' => 'Sari Indah Lestari',
                'discount' => 0,
                'cash' => 5000000,
                'items' => [
                    ['barcode' => 'ARW-0001', 'qty' => 1],
                    ['barcode' => 'SGT-0002', 'qty' => 1],
                    ['barcode' => 'STR-0001', 'qty' => 1],
                ],
            ],
            [
                'customer' => 'Bayu Pratama',
                'discount' => 100000,
                'cash' => 10000000,
                'items' => [
                    ['barcode' => 'BOW-0001', 'qty' => 1],
                    ['barcode' => 'SGT-0001', 'qty' => 1],
                    ['barcode' => 'TAS-0001', 'qty' => 1],
                ],
            ],
            [
                'customer' => 'Dian Permata',
                'discount' => 0,
                'cash' => 2000000,
                'items' => [
                    ['barcode' => 'ARW-0003', 'qty' => 2],
                    ['barcode' => 'PRT-0002', 'qty' => 1],
                    ['barcode' => 'STR-0002', 'qty' => 2],
                ],
            ],
            [
                'customer' => 'Anisa Rahma',
                'discount' => 25000,
                'cash' => 2000000,
                'items' => [
                    ['barcode' => 'APR-0001', 'qty' => 2],
                    ['barcode' => 'APR-0002', 'qty' => 1],
                    ['barcode' => 'TGT-0002', 'qty' => 2],
                    ['barcode' => 'STR-0003', 'qty' => 3],
                ],
            ],
            [
                'customer' => null,
                'discount' => 0,
                'cash' => 1000000,
                'items' => [
                    ['barcode' => 'ARW-0004', 'qty' => 2],
                    ['barcode' => 'TGT-0003', 'qty' => 1],
                ],
            ],
        ];

        foreach ($blueprints as $blueprint) {
            $customer = $blueprint['customer']
                ? $customers->get($blueprint['customer'])
                : null;

            $items = collect($blueprint['items'])
                ->map(function ($item) use ($products) {
                    $product = $products->get($item['barcode']);

                    if (! $product) {
                        return null;
                    }

                    $lineTotal = $product->sell_price * $item['qty'];

                    return [
                        'product' => $product,
                        'qty' => $item['qty'],
                        'line_total' => $lineTotal,
                        'profit' => ($product->sell_price - $product->buy_price) * $item['qty'],
                    ];
                })
                ->filter();

            if ($items->isEmpty()) {
                continue;
            }

            $discount = max(0, $blueprint['discount']);
            $gross = $items->sum('line_total');
            $grandTotal = max(0, $gross - $discount);
            $cashPaid = max($grandTotal, $blueprint['cash']);
            $change = $cashPaid - $grandTotal;

            $transaction = Transaction::create([
                'cashier_id' => $cashier->id,
                'customer_id' => $customer?->id,
                'invoice' => 'IHC-'.Str::upper(Str::random(8)),
                'cash' => $cashPaid,
                'change' => $change,
                'discount' => $discount,
                'grand_total' => $grandTotal,
            ]);

            foreach ($items as $item) {
                $transaction->details()->create([
                    'product_id' => $item['product']->id,
                    'qty' => $item['qty'],
                    'price' => $item['line_total'],
                ]);

                $transaction->profits()->create([
                    'total' => $item['profit'],
                ]);

                $item['product']->decrement('stock', $item['qty']);
            }
        }
    }

    /**
     * Seed receivables and their payments.
     */
    private function seedReceivables(Collection $customers): void
    {
        $cashier = User::where('email', 'kasir@ihcarchery.com')->first() ?? User::first();

        $sourceTransactions = Transaction::with('customer')
            ->whereNotNull('customer_id')
            ->take(3)
            ->get();

        foreach ($sourceTransactions as $index => $transaction) {
            $paid = match ($index) {
                0 => (float) ($transaction->grand_total * 0.4),
                1 => (float) ($transaction->grand_total * 0.7),
                default => 0,
            };

            $status = $paid <= 0
                ? 'unpaid'
                : ($paid >= $transaction->grand_total ? 'paid' : 'partial');

            $receivable = Receivable::create([
                'customer_id' => $transaction->customer_id,
                'transaction_id' => $transaction->id,
                'invoice' => 'RCV-'.$transaction->invoice,
                'total' => $transaction->grand_total,
                'paid' => $paid,
                'due_date' => now()->addDays(($index + 1) * 7)->toDateString(),
                'status' => $status,
                'note' => 'Piutang dari pembelian peralatan panahan '.$transaction->invoice,
            ]);

            if ($paid > 0) {
                ReceivablePayment::create([
                    'receivable_id' => $receivable->id,
                    'paid_at' => now()->subDays(2 + $index)->toDateString(),
                    'amount' => $paid,
                    'method' => 'cash',
                    'user_id' => $cashier?->id,
                    'note' => 'Pembayaran awal piutang peralatan',
                ]);
            }

            $transaction->update([
                'payment_method' => 'credit',
                'payment_status' => $status === 'paid' ? 'paid' : 'unpaid',
                'cash' => (int) $paid,
                'change' => 0,
            ]);
        }

        $manualReceivables = [
            [
                'customer' => 'Gilang Ramadhan',
                'invoice' => 'RCV-MANUAL-001',
                'total' => 1850000,
                'paid' => 500000,
                'due_date' => now()->addDays(10)->toDateString(),
                'status' => 'partial',
                'note' => 'Piutang pembelian compound bow cicilan',
            ],
            [
                'customer' => 'Putri Wulandari',
                'invoice' => 'RCV-MANUAL-002',
                'total' => 2750000,
                'paid' => 0,
                'due_date' => now()->subDays(3)->toDateString(),
                'status' => 'overdue',
                'note' => 'Piutang pembelian set recurve bow yang sudah jatuh tempo',
            ],
        ];

        foreach ($manualReceivables as $item) {
            $customer = $customers->get($item['customer']);

            if (! $customer) {
                continue;
            }

            $receivable = Receivable::create([
                'customer_id' => $customer->id,
                'invoice' => $item['invoice'],
                'total' => $item['total'],
                'paid' => $item['paid'],
                'due_date' => $item['due_date'],
                'status' => $item['status'],
                'note' => $item['note'],
            ]);

            if ($item['paid'] > 0) {
                ReceivablePayment::create([
                    'receivable_id' => $receivable->id,
                    'paid_at' => now()->subDays(1)->toDateString(),
                    'amount' => $item['paid'],
                    'method' => 'bank_transfer',
                    'user_id' => $cashier?->id,
                    'note' => 'Pembayaran sebagian piutang peralatan panahan',
                ]);
            }
        }
    }

    /**
     * Seed payables and their payments.
     */
    private function seedPayables(Collection $suppliers): void
    {
        $cashier = User::where('email', 'kasir@ihcarchery.com')->first() ?? User::first();

        $blueprints = [
            [
                'supplier' => 'PT Hoyt Indonesia',
                'document_number' => 'PYB-0001',
                'total' => 15000000,
                'paid' => 5000000,
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'partial',
                'note' => 'Pengadaan stok recurve bow dan riser',
            ],
            [
                'supplier' => 'CV Win&Win Archery Supply',
                'document_number' => 'PYB-0002',
                'total' => 8500000,
                'paid' => 0,
                'due_date' => now()->addDays(21)->toDateString(),
                'status' => 'unpaid',
                'note' => 'Pengadaan limb dan stabilizer',
            ],
            [
                'supplier' => 'PT Easton Arrow Indonesia',
                'document_number' => 'PYB-0003',
                'total' => 6200000,
                'paid' => 6200000,
                'due_date' => now()->subDays(2)->toDateString(),
                'status' => 'paid',
                'note' => 'Pembelian arrow carbon dan aluminium',
            ],
            [
                'supplier' => 'UD Panahan Nusantara',
                'document_number' => 'PYB-0004',
                'total' => 3800000,
                'paid' => 1000000,
                'due_date' => now()->subDays(5)->toDateString(),
                'status' => 'overdue',
                'note' => 'Pengadaan aksesoris dan target face jatuh tempo',
            ],
        ];

        foreach ($blueprints as $item) {
            $supplier = $suppliers->get($item['supplier']);

            if (! $supplier) {
                continue;
            }

            $payable = Payable::create([
                'supplier_id' => $supplier->id,
                'document_number' => $item['document_number'],
                'total' => $item['total'],
                'paid' => $item['paid'],
                'due_date' => $item['due_date'],
                'status' => $item['status'],
                'note' => $item['note'],
            ]);

            if ($item['paid'] > 0) {
                PayablePayment::create([
                    'payable_id' => $payable->id,
                    'paid_at' => now()->subDays(3)->toDateString(),
                    'amount' => $item['paid'],
                    'method' => 'bank_transfer',
                    'user_id' => $cashier?->id,
                    'note' => 'Pembayaran hutang supplier peralatan panahan',
                ]);
            }
        }
    }
}
