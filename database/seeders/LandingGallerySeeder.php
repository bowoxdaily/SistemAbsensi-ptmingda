<?php

namespace Database\Seeders;

use App\Models\LandingGallery;
use Illuminate\Database\Seeder;

class LandingGallerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $samples = [
            [
                'title' => 'Lini Produksi Cutting & Pola Presisi',
                'category' => 'production',
                'image_path' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=800&q=80',
                'description' => 'Mesin pemotong bahan presisi tinggi untuk komponen upper sepatu berkualitas ekspor.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Lini Perakitan & Jahit (Stitching)',
                'category' => 'production',
                'image_path' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80',
                'description' => 'Operator terampil merakit setiap panel sepatu dengan standar ketahanan internasional.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Laboratorium Quality Control (QC)',
                'category' => 'quality_control',
                'image_path' => 'https://images.unsplash.com/photo-1581092335397-9583fe92d232?auto=format&fit=crop&w=800&q=80',
                'description' => 'Pengujian daya rekat sol, flexibilitas material, dan uji ketahanan cuaca secara ketat.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Gudang Logistik & Finished Goods',
                'category' => 'warehouse',
                'image_path' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&q=80',
                'description' => 'Pusat distribusi modern berkapasitas besar siap melayani pengiriman domestik & global.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Sistem Otomasi & Keselamatan Kerja K3',
                'category' => 'facility',
                'image_path' => 'https://images.unsplash.com/photo-1504917599217-d4dc5ebe6122?auto=format&fit=crop&w=800&q=80',
                'description' => 'Lingkungan kerja higienis, sirkulasi udara teratur, dan standar K3 pabrik terlindungi.',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Pusat Operasional & Manajemen Pabrik',
                'category' => 'office',
                'image_path' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80',
                'description' => 'Ruang koordinasi lintas divisi dan pemantauan data kehadiran serta performa real-time.',
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($samples as $item) {
            LandingGallery::firstOrCreate(
                ['title' => $item['title']],
                $item
            );
        }
    }
}
