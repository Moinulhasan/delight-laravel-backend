<?php

namespace Database\Seeders;

use App\Models\ContactDetail;
use App\Models\HeroSlide;
use App\Models\Leader;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Video;
use Illuminate\Database\Seeder;

/**
 * The website's current content (delwar_next_js/content). Uses firstOrCreate so re-running
 * never overwrites edits made later in the admin. The example draft project is left out.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->heroSlides();
        $this->projects();
        $this->videos();
        $this->leaders();
        $this->contactDetails();
        $this->settings();
    }

    private function settings(): void
    {
        if (filled(Setting::current()->lead_recipient)) {
            return;
        }

        // Mail stays off until the SMTP username and password are entered in Settings.
        Setting::current()->fill([
            'lead_recipient' => 'info@delightre.com',
            'mail_from_address' => 'website@delightre.com',
            'mail_from_name' => 'Delight Real Estate website',
            'smtp_host' => 'mail.delightre.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ])->save();
    }

    private function heroSlides(): void
    {
        $slides = [
            'delwar-garden-tower-street-view-bashundhara' => [
                'Delwar Garden by Delight Real Estate, a tower with green balconies on a tree-lined street in Bashundhara (architectural rendering)',
                'বসুন্ধরার গাছঘেরা রাস্তায় সবুজ বারান্দাসহ ডিলাইট রিয়েল এস্টেটের দেলোয়ার গার্ডেন ভবন (স্থাপত্য রেন্ডারিং)',
            ],
            'delwar-garden-front-elevation-street-bashundhara' => [
                'Front of Delwar Garden with planted balconies and the street entrance (architectural rendering)',
                'গাছে ভরা বারান্দা ও রাস্তার দিকের প্রবেশপথসহ দেলোয়ার গার্ডেনের সম্মুখভাগ (স্থাপত্য রেন্ডারিং)',
            ],
            'delight-rooftop-terrace-planted-wall' => [
                'Rooftop terrace with a climbing-plant wall, planters and a steel stair (architectural rendering)',
                'লতানো গাছের দেয়াল, টব ও ইস্পাতের সিঁড়িসহ ছাদের টেরেস (স্থাপত্য রেন্ডারিং)',
            ],
            'delight-apartment-building-side-elevation' => [
                'Side elevation of a Delight Real Estate apartment building among trees (architectural rendering)',
                'গাছপালার মাঝে ডিলাইট রিয়েল এস্টেটের একটি অ্যাপার্টমেন্ট ভবনের পাশের দিক (স্থাপত্য রেন্ডারিং)',
            ],
        ];

        $order = 0;

        foreach ($slides as $name => [$altEn, $altBn]) {
            HeroSlide::firstOrCreate(['alt_en' => $altEn], [
                'alt_bn' => $altBn,
                'desktop_images' => [
                    'src' => "/uploads/hero/{$name}-1920.webp",
                    'srcset' => "/uploads/hero/{$name}-1280.webp 1280w, /uploads/hero/{$name}-1920.webp 1920w, /uploads/hero/{$name}-2560.webp 2560w",
                ],
                'mobile_images' => [
                    'src' => "/uploads/hero/{$name}-m1080.webp",
                    'srcset' => "/uploads/hero/{$name}-m720.webp 720w, /uploads/hero/{$name}-m1080.webp 1080w",
                ],
                'sort_order' => ++$order,
            ]);
        }
    }

    private function projects(): void
    {
        Project::firstOrCreate(['slug' => 'delwar-garden'], [
            'name_en' => 'Delwar Garden',
            'name_bn' => 'দেলোয়ার গার্ডেন',
            'tagline_en' => 'one home on every floor',
            'tagline_bn' => 'প্রতি তলায় একটি ফ্ল্যাট',
            'status' => 'ongoing',
            'type' => 'residential',
            'handover' => '2029',
            'area_slug' => 'bashundhara',
            'area_name_en' => 'Bashundhara R/A',
            'area_name_bn' => 'বসুন্ধরা আবাসিক এলাকা',
            'address_en' => 'Plot 1510, Road 24, Block I, Bashundhara R/A',
            'address_bn' => 'প্লট 1510, রোড 24, ব্লক-আই, বসুন্ধরা আবাসিক এলাকা',
            'cover_image' => [
                'src' => '/images/projects/delwar-garden/delwar-garden-tower-rendering-bashundhara.webp',
                'w' => 1200,
                'h' => 1422,
            ],
            'cover_alt_en' => 'Delwar Garden, a slim residential tower with planted balconies on every floor, Block I, Bashundhara R/A (architectural rendering)',
            'cover_alt_bn' => 'দেলোয়ার গার্ডেন, প্রতি তলায় গাছে সাজানো বারান্দাসহ একটি সরু আবাসিক টাওয়ার, ব্লক-আই, বসুন্ধরা আবাসিক এলাকা (স্থাপত্য রেন্ডারিং)',
            'card_stats' => [
                ['value' => ['en' => '4', 'bn' => '4'], 'label' => ['en' => 'Katha', 'bn' => 'কাঠা']],
                ['value' => ['en' => 'G+8', 'bn' => 'জি+8'], 'label' => ['en' => 'Floors', 'bn' => 'তলা']],
                ['value' => ['en' => '8', 'bn' => '8'], 'label' => ['en' => 'Homes', 'bn' => 'ফ্ল্যাট']],
            ],
            'is_featured' => true,
            'sort_order' => 1,
        ]);
    }

    private function videos(): void
    {
        Video::firstOrCreate(['youtube_id' => 'mJVuZiK9a6I'], [
            'title_en' => 'Cinematic property film',
            'title_bn' => 'সিনেমাটিক প্রপার্টি ফিল্ম',
            'poster_image' => ['src' => '/uploads/videos/cinematic-property-film.webp', 'srcset' => '/uploads/videos/cinematic-property-film.webp 1280w', 'w' => 1280, 'h' => 720],
            'sort_order' => 1,
        ]);

        Video::firstOrCreate(['youtube_id' => 'OjbC3NT_Xx4'], [
            'title_en' => 'XYZ House',
            'title_bn' => 'XYZ হাউস',
            'poster_image' => ['src' => '/uploads/videos/xyz-house-film.webp', 'srcset' => '/uploads/videos/xyz-house-film.webp 1280w', 'w' => 1280, 'h' => 720],
            'sort_order' => 2,
        ]);
    }

    private function leaders(): void
    {
        Leader::firstOrCreate(['name' => 'Shimu Akter'], [
            'initials' => 'SA',
            'role_en' => 'Chairman',
            'role_bn' => 'চেয়ারম্যান',
            'quote_en' => 'A home is not just a structure of bricks and concrete. It is where memories are made, families grow and dreams take shape.',
            'quote_bn' => 'বাড়ি শুধু ইট আর কংক্রিটের কাঠামো নয়। এখানেই তৈরি হয় স্মৃতি, বেড়ে ওঠে পরিবার, আর রূপ পায় স্বপ্ন।',
            'photo' => ['src' => '/images/management/shimu-akter-chairman-delight-real-estate.webp', 'w' => 800, 'h' => 1000],
            'photo_alt_en' => 'Shimu Akter, Chairman of Delight Real Estate',
            'photo_alt_bn' => 'শিমু আক্তার, চেয়ারম্যান, ডিলাইট রিয়েল এস্টেট',
            'sort_order' => 1,
        ]);

        Leader::firstOrCreate(['name' => 'Md. Helal Uddin'], [
            'initials' => 'HU',
            'role_en' => 'Managing Director',
            'role_bn' => 'ব্যবস্থাপনা পরিচালক',
            'quote_en' => 'We see our clients as lifelong partners. Every project carries our promise of a smooth experience from first meeting to handover.',
            'quote_bn' => 'আমাদের গ্রাহকদের আমরা আজীবনের সঙ্গী মনে করি। প্রথম সাক্ষাৎ থেকে হস্তান্তর পর্যন্ত নির্বিঘ্ন অভিজ্ঞতার অঙ্গীকার থাকে আমাদের প্রতিটি প্রকল্পে।',
            'photo' => ['src' => '/images/management/md-helal-uddin-managing-director-delight-real-estate.webp', 'w' => 800, 'h' => 1000],
            'photo_alt_en' => 'Md. Helal Uddin, Managing Director of Delight Real Estate',
            'photo_alt_bn' => 'মো. হেলাল উদ্দিন, ব্যবস্থাপনা পরিচালক, ডিলাইট রিয়েল এস্টেট',
            'sort_order' => 2,
        ]);
    }

    private function contactDetails(): void
    {
        ContactDetail::firstOrCreate([], [
            'hotline_display' => '+880 1713-903025',
            'hotline_e164' => '+8801713903025',
            'whatsapp' => '+8801713903025',
            'email' => 'info@delightre.com',
            'street' => '604/B, Rupayan Shopping Square, Block G',
            // Floor and hours are not confirmed by the client yet, so both stay hidden.
            'floor' => '5th Floor',
            'show_floor' => false,
            'locality' => 'Bashundhara R/A',
            'city' => 'Dhaka',
            'postal_code' => '1229',
            'country' => 'BD',
            'opening_days' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
            'opens' => '10:00',
            'closes' => '19:00',
            'show_hours' => false,
        ]);
    }
}
