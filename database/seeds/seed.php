<?php

declare(strict_types=1);

$router = require dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Models\Prompt;
use App\Models\User;

$options = getopt('', ['email::', 'password::', 'name::']);
$email = (string) ($options['email'] ?? env('SEED_ADMIN_EMAIL', ''));
$password = (string) ($options['password'] ?? env('SEED_ADMIN_PASSWORD', ''));
$name = (string) ($options['name'] ?? 'Admin');

if ($email === '' || $password === '') {
    fwrite(STDERR, "Seed requires --email and --password, or SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD.\n");
    exit(1);
}

try {
    $admin = User::findByEmail($email);

    if (! $admin) {
        $admin = User::create($name, $email, $password, true);
        echo "Admin created: {$email}\n";
    } else {
        echo "Admin already exists: {$email}\n";
    }

    $samples = [
        [
            'title' => 'Window-lit editorial portrait',
            'category' => 'portrait',
            'prompt' => 'Create an editorial portrait of a thoughtful subject near a tall studio window, soft directional daylight, natural skin texture, dark green wardrobe, quiet confident expression, shallow depth of field, realistic color grade.',
            'negative_prompt' => 'over-smoothed skin, harsh flash, extra fingers, distorted face, watermark, text',
        ],
        [
            'title' => 'Ceramic skincare product still life',
            'category' => 'product',
            'prompt' => 'A refined product photograph of a ceramic skincare jar on brushed limestone, dew-like condensation, pale sage leaves, controlled softbox reflection, crisp label area, premium catalog composition.',
            'negative_prompt' => 'crooked label, noisy background, blown highlights, plastic texture, low resolution',
        ],
        [
            'title' => 'Streetwear lookbook in morning light',
            'category' => 'fashion',
            'prompt' => 'Full-body fashion lookbook image on a quiet city side street, relaxed oversized jacket, clean sneakers, early morning side light, neutral storefront reflections, editorial styling, realistic fabric folds.',
            'negative_prompt' => 'warped shoes, extra limbs, logo artifacts, muddy colors, motion blur',
        ],
    ];

    foreach ($samples as $sample) {
        if (Prompt::findPublic(Prompt::slugify($sample['title']))) {
            continue;
        }

        Prompt::create([
            'user_id' => (int) $admin['id'],
            'title' => $sample['title'],
            'prompt' => $sample['prompt'],
            'negative_prompt' => $sample['negative_prompt'],
            'generation_mode' => 'imported',
            'category' => $sample['category'],
            'status' => 'completed',
            'style_notes' => ['seeded' => true],
            'ai_provider' => 'seed',
            'ai_model' => 'manual',
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    echo "Sample prompts seeded.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Seed failed: " . $exception->getMessage() . "\n");
    exit(1);
}

