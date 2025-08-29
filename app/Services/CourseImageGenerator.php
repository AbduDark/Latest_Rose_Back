<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Facades\Image;

/**
 * CourseImageGenerator
 *
 * Generates course images by selecting a random template and adding text/logo
 * based on predefined coordinates for each template.
 */
class CourseImageGenerator
{
    private array $templates;
    private string $defaultFont;

    public function __construct()
    {
        $this->templates = [
            [
                'id' => 'template1',
                'file' => public_path('templates/template1.jpg'),
                'positions' => [
                    'title' => [
                        'x' => 790,
                        'y' => 201,
                        'width' => 435,
                        'height' => 85,
                        'size' => 60,
                        'color' => '#ffffff',
                        'align' => 'right',
                        'stroke' => [
                            'size' => 2,
                            'color' => '#ffffff'
                        ],
                        'outline' => [
                            'color' => '#000000',
                            'opacity' => 18,
                            'size' => 13,
                            'range' => 50
                        ]
                    ],
                    'grade' => [
                        'x' => 876,
                        'y' => 307,
                        'width' => 260,
                        'height' => 79,
                        'size' => 60,
                        'color' => '#ffffff',
                        'align' => 'right',
                        'stroke' => [
                            'size' => 2,
                            'color' => '#ffffff'
                        ],
                        'outline' => [
                            'color' => '#000000',
                            'opacity' => 18,
                            'size' => 13,
                            'range' => 50
                        ]
                    ]
                ]
            ],
            [
                'id' => 'template2',
                'file' => public_path('templates/template2.jpg'),
                'positions' => [
                    'title' => [
                        'x' => 773,
                        'y' => 148,
                        'width' => 356,
                        'height' => 85,
                        'size' => 60,
                        'color' => '#ffffff',
                        'align' => 'right',
                        'stroke' => [
                            'size' => 2,
                            'color' => '#ffffff'
                        ],
                        'outline' => [
                            'color' => '#000000',
                            'opacity' => 18,
                            'size' => 13,
                            'range' => 50
                        ]
                    ],
                    'grade' => [
                        'x' => 829,
                        'y' => 235,
                        'width' => 244,
                        'height' => 85,
                        'size' => 60,
                        'color' => '#ffffff',
                        'align' => 'right',
                        'stroke' => [
                            'size' => 2,
                            'color' => '#ffffff'
                        ],
                        'outline' => [
                            'color' => '#000000',
                            'opacity' => 18,
                            'size' => 13,
                            'range' => 50
                        ]
                    ]
                ]
            ],
            [
                'id' => 'template3',
                'file' => public_path('templates/template3.jpg'),
                'positions' => [
                    'title' => [
                        'x' => 760,
                        'y' => 176,
                        'width' => 365,
                        'height' => 85,
                        'size' => 60,
                        'color' => '#ffffff',
                        'align' => 'right',
                        'stroke' => [
                            'size' => 2,
                            'color' => '#ffffff'
                        ],
                        'outline' => [
                            'color' => '#000000',
                            'opacity' => 18,
                            'size' => 13,
                            'range' => 50
                        ]
                    ],
                    'grade' => [
                        'x' => 805,
                        'y' => 277,
                        'width' => 258,
                        'height' => 85,
                        'size' => 60,
                        'color' => '#ffffff',
                        'align' => 'right',
                        'stroke' => [
                            'size' => 2,
                            'color' => '#ffffff'
                        ],
                        'outline' => [
                            'color' => '#000000',
                            'opacity' => 18,
                            'size' => 13,
                            'range' => 50
                        ]
                    ]
                ]
            ]
        ];

        // استخدام خط عربي مناسب
        $this->defaultFont = public_path('fonts/NotoSansArabic-Bold.ttf');
    }

    /**
     * Generate course image with provided data
     *
     * @param array $data Required keys: title, grade. Optional: price, description, instructor, logo_path, currency
     * @return string Relative path to the generated image
     * @throws \InvalidArgumentException
     */
    public function generateCourseImage(array $data): string
    {
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Title is required for course image generation');
        }

        // Select random template
        $tpl = $this->templates[array_rand($this->templates)];

        Log::info("Selected template: {$tpl['id']} for course: {$data['title']}");

        if (!file_exists($tpl['file'])) {
            Log::warning("Template file not found: {$tpl['file']}");
            return $this->createFallbackImage($data);
        }

        try {
            $img = Image::make($tpl['file']);
            $fontPath = file_exists($this->defaultFont) ? $this->defaultFont : null;

            Log::info("Using font: " . ($fontPath ? $fontPath : 'system default'));

            // إضافة العنوان والصف فقط
            $this->addTextElements($img, $tpl, $data, $fontPath);

            $imagePath = $this->saveImage($img);
            Log::info("Course image generated successfully: {$imagePath}");

            return $imagePath;
        } catch (\Exception $e) {
            Log::error('Course image generation failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return $this->createFallbackImage($data);
        }
    }

    private function addTextElements($img, array $tpl, array $data, ?string $fontPath): void
    {
        // إضافة العنوان
        if (!empty($data['title']) && !empty($tpl['positions']['title'])) {
            $pos = $tpl['positions']['title'];
            $this->drawTextBox(
                $img,
                $this->processArabicText($data['title']),
                $pos['x'],
                $pos['y'],
                $pos['width'],
                $fontPath,
                $pos['size'],
                $pos['color'],
                $pos['align'],
                $pos
            );
        }

        // إضافة الصف
        if (!empty($data['grade']) && !empty($tpl['positions']['grade'])) {
            $pos = $tpl['positions']['grade'];
            $this->drawTextBox(
                $img,
                $this->processArabicText($data['grade']),
                $pos['x'],
                $pos['y'],
                $pos['width'],
                $fontPath,
                $pos['size'],
                $pos['color'],
                $pos['align'],
                $pos
            );
        }
    }

    private function saveImage($img): string
    {
        $filename = Str::slug(uniqid('course_')) . '.jpg';
        $relativePath = 'uploads/courses/' . $filename;
        $fullPath = public_path($relativePath);

        $directory = dirname($fullPath);
        if (!file_exists($directory) && !mkdir($directory, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: {$directory}");
        }

        $img->save($fullPath, 88);
        return $relativePath;
    }

    private function drawTextBox($img, string $text, int $x, int $y, int $boxWidth, ?string $fontPath, int $fontSize, string $hexColor, string $align = 'right', array $position = []): void
    {
        try {
            $lines = $this->wrapTextToLines($text, $fontPath, $fontSize, $boxWidth);
            $lineHeight = (int)($fontSize * 1.4); // زيادة المسافة بين الأسطر للعربية

            // الحصول على خيارات التصميم الإضافية
            $strokeSize = $position['stroke']['size'] ?? 0;
            $strokeColor = $position['stroke']['color'] ?? '#ffffff';
            $outlineColor = $position['outline']['color'] ?? '#000000';
            $outlineOpacity = $position['outline']['opacity'] ?? 18;
            $outlineSize = $position['outline']['size'] ?? 13;

            Log::info("Drawing Arabic text: {$text} at position ({$x}, {$y}) with {$fontSize}px font");

            foreach ($lines as $i => $line) {
                $lineY = $y + ($i * $lineHeight);
                $startX = $this->computeX($img->width(), $x, $align, $this->getTextWidth($line, $fontPath, $fontSize));

                // رسم التأثير الخارجي (outer glow)
                if ($outlineSize > 0) {
                    for ($ox = -$outlineSize; $ox <= $outlineSize; $ox++) {
                        for ($oy = -$outlineSize; $oy <= $outlineSize; $oy++) {
                            if ($ox != 0 || $oy != 0) {
                                $img->text($line, $startX + $ox, $lineY + $oy, function ($font) use ($fontPath, $fontSize, $outlineColor, $align, $outlineOpacity) {
                                    if ($fontPath && file_exists($fontPath)) {
                                        $font->file($fontPath);
                                    }
                                    $font->size($fontSize);
                                    $font->color($outlineColor);
                                    $font->align($align);
                                    $font->valign('top');
                                });
                            }
                        }
                    }
                }

                // رسم الحدود (stroke)
                if ($strokeSize > 0) {
                    $offsets = [
                        [$strokeSize, 0], [-$strokeSize, 0], [0, $strokeSize], [0, -$strokeSize],
                        [$strokeSize, $strokeSize], [-$strokeSize, -$strokeSize], [$strokeSize, -$strokeSize], [-$strokeSize, $strokeSize]
                    ];

                    foreach ($offsets as $offset) {
                        $img->text($line, $startX + $offset[0], $lineY + $offset[1], function ($font) use ($fontPath, $fontSize, $strokeColor, $align) {
                            if ($fontPath && file_exists($fontPath)) {
                                $font->file($fontPath);
                            }
                            $font->size($fontSize);
                            $font->color($strokeColor);
                            $font->align($align);
                            $font->valign('top');
                        });
                    }
                }

                // رسم النص الأساسي
                $img->text($line, $startX, $lineY, function ($font) use ($fontPath, $fontSize, $hexColor, $align) {
                    if ($fontPath && file_exists($fontPath)) {
                        $font->file($fontPath);
                    }
                    $font->size($fontSize);
                    $font->color($hexColor);
                    $font->align($align);
                    $font->valign('top');
                });
            }
        } catch (\Exception $e) {
            Log::warning("Failed to draw text '{$text}': " . $e->getMessage());
            // رسم احتياطي بدون تأثيرات
            $img->text($text, $x, $y, function ($font) use ($fontSize, $hexColor, $align) {
                $font->size($fontSize);
                $font->color($hexColor);
                $font->align($align);
                $font->valign('top');
            });
        }
    }

    private function wrapTextToLines(string $text, ?string $fontFile, int $fontSize, int $maxWidth): array
    {
        $text = $this->processArabicText($text);

        if (!$fontFile || !file_exists($fontFile)) {
            // تقدير تقريبي للعرض بدون خط
            $charWidth = $fontSize * 0.7; // تعديل للنص العربي
            $maxChars = max(3, (int)($maxWidth / $charWidth));
            $words = explode(' ', trim($text));
            $lines = [];
            $current = '';

            foreach ($words as $word) {
                $testLine = $current === '' ? $word : $current . ' ' . $word;
                if (mb_strlen($testLine, 'UTF-8') <= $maxChars) {
                    $current = $testLine;
                } else {
                    if ($current !== '') $lines[] = $current;
                    $current = $word;
                }
            }
            if ($current !== '') $lines[] = $current;
            return $lines;
        }

        $words = explode(' ', trim($text));
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $try = $current === '' ? $word : $current . ' ' . $word;
            $width = $this->getTextWidth($try, $fontFile, $fontSize);

            if ($width <= $maxWidth) {
                $current = $try;
            } else {
                if ($current !== '') $lines[] = $current;

                $wwidth = $this->getTextWidth($word, $fontFile, $fontSize);

                if ($wwidth <= $maxWidth) {
                    $current = $word;
                } else {
                    // تقسيم الكلمة الطويلة
                    $chars = mb_str_split($word, 1, 'UTF-8');
                    $piece = '';

                    foreach ($chars as $ch) {
                        $tryPiece = $piece . $ch;
                        $pw = $this->getTextWidth($tryPiece, $fontFile, $fontSize);

                        if ($pw <= $maxWidth) {
                            $piece = $tryPiece;
                        } else {
                            if ($piece !== '') $lines[] = $piece;
                            $piece = $ch;
                        }
                    }
                    $current = $piece !== '' ? $piece : '';
                }
            }
        }

        if ($current !== '') $lines[] = $current;
        return $lines;
    }

    private function getTextWidth(string $text, ?string $fontFile, int $fontSize): int
    {
        if (!$fontFile || !file_exists($fontFile) || !function_exists('imagettfbbox')) {
            // تقدير تقريبي
            return mb_strlen($text, 'UTF-8') * ($fontSize * 0.7);
        }

        $box = imagettfbbox($fontSize, 0, $fontFile, $text);
        return abs($box[2] - $box[0]);
    }

    private function processArabicText(string $text): string
    {
        // تنظيف النص وإزالة المسافات الزائدة
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);

        // إعادة النص كما هو - الخط العربي سيتولى التشكيل الصحيح
        return $text;
    }

    private function computeX(int $imgWidth, int $x, string $align = 'right', ?int $textWidth = null): int
    {
        $align = strtolower($align);

        if ($align === 'center') {
            if ($textWidth) {
                return max(0, $x - ($textWidth / 2));
            }
            return $x;
        }

        if ($align === 'left') {
            return $x;
        }

        // للمحاذاة اليمينية (افتراضي للعربية)
        if ($textWidth) {
            return max(0, $x - $textWidth);
        }
        return $x;
    }

    private function createFallbackImage(array $data): string
    {
        $img = Image::canvas(1200, 800, '#2d3748');
        $fontPath = file_exists($this->defaultFont) ? $this->defaultFont : null;

        $title = $data['title'] ?? 'كورس';
        $img->text($title, 600, 300, function ($font) use ($fontPath) {
            if ($fontPath) $font->file($fontPath);
            $font->size(48);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('center');
        });

        if (!empty($data['grade'])) {
            $img->text($data['grade'], 600, 400, function ($font) use ($fontPath) {
                if ($fontPath) $font->file($fontPath);
                $font->size(36);
                $font->color('#ffffff');
                $font->align('center');
                $font->valign('center');
            });
        }

        return $this->saveImage($img);
    }
}
