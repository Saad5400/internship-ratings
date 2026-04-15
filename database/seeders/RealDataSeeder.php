<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\Modality;
use App\Enums\Recommendation;
use App\Models\Company;
use App\Models\Rating;
use App\Support\Arabic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RealDataSeeder extends Seeder
{
    private const WEEKS_PER_MONTH = 4;

    private const DAYS_PER_MONTH = 30;

    private const BATCH_INSERT_SIZE = 500;

    private const ARABIC_UNICODE_PATTERN = '/[\x{0600}-\x{06FF}]/u';

    /** @var list<string> */
    private const INVALID_VALUES = [
        '',
        '.',
        '-',
        '—',
        '_',
        'n/a',
        'na',
        'null',
        'test',
        // noisy single-cell values observed in source files
        '2',
    ];

    /** @var array<string, string> */
    private const CITY_KEYWORDS = [
        'الرياض' => 'الرياض',
        'جدة' => 'جدة',
        'مكه' => 'مكة',
        'مكة' => 'مكة',
        'الدمام' => 'الدمام',
        'الخبر' => 'الخبر',
        'المدينة' => 'المدينة المنورة',
        'المدينة المنورة' => 'المدينة المنورة',
        'الظهران' => 'الظهران',
        'الطائف' => 'الطائف',
        'الجبيل' => 'الجبيل',
        'مكة المكرمة' => 'مكة',
    ];

    public function run(): void
    {
        $sourceFiles = glob(__DIR__.'/data/*.csv') ?: [];
        sort($sourceFiles);

        $companyRecords = [];
        $ratingRecords = [];

        foreach ($sourceFiles as $sourceFile) {
            $rows = $this->loadCsvRows($sourceFile);

            if ($rows === []) {
                continue;
            }

            foreach ($rows as $row) {
                if ($this->isCoopDirectoryRow($row)) {
                    $this->collectCompanyDirectoryRow($companyRecords, $sourceFile, $row);

                    continue;
                }

                $this->collectRatingRow($companyRecords, $ratingRecords, $sourceFile, $row);
            }
        }

        $companiesByNormalized = $this->persistCompanies($companyRecords);
        $this->persistRatings($ratingRecords, $companiesByNormalized);
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function loadCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        $firstLine = '';

        if ($handle !== false) {
            try {
                $firstLine = (string) fgets($handle);
            } finally {
                fclose($handle);
            }
        }

        $delimiter = substr_count($firstLine, "\t") > substr_count($firstLine, ',') ? "\t" : ',';

        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delimiter);

        $header = null;
        $rows = [];

        foreach ($file as $csvRow) {
            if (! is_array($csvRow) || $csvRow === [null]) {
                continue;
            }

            $csvRow = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $csvRow);

            if ($header === null) {
                $header = $this->normalizeHeaderRow($csvRow);

                continue;
            }

            $rows[] = $this->associateRow($header, $csvRow);
        }

        if ($header !== null && count($header) === 1 && str_contains((string) $header[0], "\t")) {
            return $this->loadSingleColumnTabbedCsv($path);
        }

        return $rows;
    }

    /**
     * @param  list<string|null>  $header
     * @param  list<string|null>  $row
     * @return array<string, string|null>
     */
    private function associateRow(array $header, array $row): array
    {
        $result = [];

        foreach ($header as $index => $key) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $result[$this->cleanHeader($key)] = $this->cleanValue($row[$index] ?? null);
        }

        return $result;
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function loadSingleColumnTabbedCsv(string $path): array
    {
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(',');

        $header = null;
        $rows = [];

        foreach ($file as $csvRow) {
            if (! is_array($csvRow) || $csvRow === [null]) {
                continue;
            }

            $line = trim((string) $csvRow[0]);

            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);

            if ($header === null) {
                $header = array_map(fn (string $key): string => $this->cleanHeader($key), $parts);

                continue;
            }

            if (count($parts) < 2) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $row[$key] = $this->cleanValue($parts[$index] ?? null);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<string|null>  $header
     * @return list<string|null>
     */
    private function normalizeHeaderRow(array $header): array
    {
        if ($header === []) {
            return [];
        }

        $header[0] = ltrim((string) $header[0], "\xEF\xBB\xBF");

        return $header;
    }

    private function cleanHeader(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\n", ' ', $value)) ?? '');
    }

    private function cleanValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', str_replace("\n", ' ', $value)) ?? '');
        $normalizedLower = Str::lower($normalized);

        if ($normalized === '' || in_array($normalizedLower, self::INVALID_VALUES, true)) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function isCoopDirectoryRow(array $row): bool
    {
        return isset($row['جهة التدريب']) && (isset($row['الموقع']) || isset($row['الإيميل']) || isset($row['LinkedIn']));
    }

    /**
     * @param  array<string, array<string, string|null>>  $companyRecords
     * @param  array<string, string|null>  $row
     */
    private function collectCompanyDirectoryRow(array &$companyRecords, string $sourceFile, array $row): void
    {
        $companyName = $this->extractCompanyName($row);

        if ($companyName === null) {
            return;
        }

        $descriptionParts = array_filter([
            $this->prefixed('الموقع', $this->extractFirstUrl([$row['الموقع'] ?? null])),
            $this->prefixed('الإيميل', $this->cleanValue($row['الإيميل'] ?? null)),
            $this->prefixed('LinkedIn', $this->cleanValue($row['LinkedIn'] ?? null)),
            $this->prefixed('المصدر', basename($sourceFile)),
        ]);

        $this->mergeCompanyRecord($companyRecords, [
            'name' => $companyName,
            'type' => null,
            'website' => $this->extractFirstUrl([$row['الموقع'] ?? null, $row['LinkedIn'] ?? null]),
            'description' => $this->joinParts($descriptionParts),
            'status' => 'approved',
        ]);
    }

    /**
     * @param  array<string, array<string, string|null>>  $companyRecords
     * @param  list<array<string, mixed>>  $ratingRecords
     * @param  array<string, string|null>  $row
     */
    private function collectRatingRow(array &$companyRecords, array &$ratingRecords, string $sourceFile, array $row): void
    {
        $companyName = $this->extractCompanyName($row);

        if ($companyName === null) {
            return;
        }

        $sourceTimestamp = $this->extractFirstValue($row, ['Timestamp', 'طابع زمني', 'Completion time']);
        $location = $this->extractFirstValue($row, ['مكان التدريب', 'المنطقه والحي', 'مقر التدريب؟', 'مقر الجهة']);
        $experienceText = $this->extractFirstValue($row, [
            'تكلم عن تجربتك في التدريب التعاوني',
            'تكلم/ي عن تجربتك مثلا (المسمى الوظيفي، وش المهام الي كانوا يكلفونك بها ، المشاريع الي اشتغلت عليها، مزايا التدريب وعيوبه)',
            'تكلم عن تجربتك مثلًا ( مسماك الوظيفي، القسم اللي تدربت فيه، التيم اللي معاك، خبرتهم، المشاريع اللي اشتغلت عليها إلخ ..)',
            'تكلم عن تجربتك مثلًا ( المهام الي قمتي فيها - المسمى الوظيفي، القسم اللي تدربت فيه، المشاريع اللي اشتغلت عليها، مزايا التدريب وعيوبه إلخ ..',
            'تكلم عن تجربتك مثلًا ( مسماك الوظيفي، القسم اللي تدربت فيه، المشاريع اللي اشتغلت عليها، مزايا التدريب وعيوبه إلخ ..)',
        ]);

        $notes = $this->extractFirstValue($row, ['ملاحظات :', 'ملاحظات']);
        $positives = $this->extractFirstValue($row, ['ما أبرز إيجابيات التدريب لدى هذه الجهة؟']);
        $tips = $this->extractFirstValue($row, ['نصيحة تقدمها لطالب سيبدأ مرحلة التدريب التعاوني قريبًا', 'كيف عملتي خطة التدريب؟ وايش نصايحك عشان اسويها']);
        $applicationMethod = $this->extractFirstValue($row, ['طريقة التقديم على التدريب', 'طريقه التقديم', 'طريقة تسجيلك بالجهه؟ (ايميل - حضوري ) يفضّل اضافة الايميل للتواصل']);
        $applicationGuide = $this->extractFirstValue($row, ['دليل التقديم']);
        $applicationRequirements = $this->extractFirstValue($row, ['ما الذي طلبته الجهة أثناء التقديم؟', 'متطلبات القبول ؟( cv - مقابلة - خطاب التدريب )', 'متطلبات القبول في الجهة', 'ما هي المستندات الي طلبوها']);
        $contact = $this->extractFirstValue($row, ['وسيلة تواصل (اختياري):', 'وسيلة تواصل معك (اختياري وسيظهر بالشيت)', 'حابه تساعدي شخص من الدفع الجاية ؟ حطي يوزرك تيليقرام']);
        $major = $this->extractFirstValue($row, ['تخصصك الجامعي', 'التخصص']);
        $domain = $this->extractFirstValue($row, ['مجال التدريب داخل الجهة']);
        $companyType = $this->extractFirstValue($row, ['حكومي ، قطاع خاص ، اخر)نوع الجهة )']);
        $stipendText = $this->extractFirstValue($row, ['هل الجهة تقدم مكافأة؟', 'هل يوجد مكافأة؟', 'هل توجد مكافئه؟ (حفل تكريم - شهادة - مبلغ مالي)', 'في حال كان هناك مكافئة/ راتب الرجاء ذكر المبلغ أو اكتب (لا يوجد)']);
        $recommendationText = $this->extractFirstValue($row, ['هل رح تنصح فيه ؟', 'ترشحي الجهه للتدريب فيها ؟', 'من ستنصح بالتدريب لدى الجهة المذكورة أعلاه؟']);
        $overallScoreText = $this->extractFirstValue($row, ['ما مدى استفادتك من التدريب التعاوني لدى هذه الجهة؟', 'مدى استفادتك من التدريب بشكل عام', 'مدى استفادتك من التدريب']);
        $mixedEnvText = $this->extractFirstValue($row, ['هل بيئة العمل مختلطه؟', 'هل البيئه مختلطه ؟', 'هل البيئة مختلطه؟', 'هل البيئة مختلطة؟']);
        $modalityText = $this->extractFirstValue($row, ['تدريبك نوعه ؟', 'هل قرار تدريبك (عن بعد) كان مناسب لك ؟ ولا ندمتي']);
        $durationText = $this->extractFirstValue($row, ['مدة التدريب', 'مدة التدريب ']);
        $jobOfferText = $this->extractFirstValue($row, ['التدريب منتهي بتوظيف؟', 'هل عرضوا عليك (وظيفة، Part time ، إلخ ..)']);

        $companyWebsite = $this->extractFirstUrl([
            $this->extractFirstValue($row, ['الموقع']),
            $applicationGuide,
        ]);

        $companyDescription = $this->joinParts(array_filter([
            $this->prefixed('الموقع', $location),
            $this->prefixed('طريقة التقديم', $applicationMethod),
            $this->prefixed('دليل التقديم', $applicationGuide),
            $this->prefixed('المتطلبات', $applicationRequirements),
            $this->prefixed('المصدر', basename($sourceFile)),
        ]));

        $this->mergeCompanyRecord($companyRecords, [
            'name' => $companyName,
            'type' => $this->mapCompanyType($companyType),
            'website' => $companyWebsite,
            'description' => $companyDescription,
            'status' => 'approved',
        ]);

        $overallScore = $this->parseOverallScore($overallScoreText, $recommendationText);
        $metricScore = (int) round($overallScore);
        $recommendation = $this->mapRecommendation($recommendationText, $overallScore);

        $reviewText = $this->joinParagraphs(array_filter([
            $experienceText,
            $this->prefixed('ملاحظات', $notes),
            $this->prefixed('نصيحة', $tips),
            $this->prefixed('نوع التدريب', $modalityText),
            $this->prefixed('المصدر الزمني', $sourceTimestamp),
        ]));

        $pros = $this->cleanValue($positives) ?? $this->extractProsFromExperience($experienceText);
        $cons = $this->extractConsFromExperience($experienceText, $notes);

        $ratingRecords[] = [
            'company_name' => $companyName,
            'role_title' => $domain,
            'department' => null,
            'city' => $this->extractCity($location),
            'duration_months' => $this->parseDurationMonths($durationText),
            'modality' => $this->mapModality($modalityText, $location, $experienceText),
            'stipend_sar' => $this->parseStipend($stipendText),
            'had_supervisor' => $this->parseHadSupervisor($experienceText),
            'mixed_env' => $this->parseBoolean($mixedEnvText),
            'job_offer' => $this->parseBoolean($jobOfferText),
            'rating_mentorship' => $metricScore,
            'rating_learning' => $metricScore,
            'rating_real_work' => $metricScore,
            'rating_team_environment' => $metricScore,
            'rating_organization' => $metricScore,
            'overall_rating' => $overallScore,
            'recommendation' => $recommendation,
            'review_text' => $reviewText,
            'pros' => $pros,
            'cons' => $cons,
            'reviewer_name' => null,
            'reviewer_university' => null,
            'reviewer_college' => null,
            'reviewer_major' => $major,
            'reviewer_degree' => null,
            'application_method' => $this->joinParts(array_filter([$applicationMethod, $applicationRequirements])),
            'willing_to_help' => $this->parseWillingToHelp($contact),
            'contact_method' => $contact,
        ];
    }

    private function extractCompanyName(array $row): ?string
    {
        $name = $this->extractFirstValue($row, ['اسم الجهة التدريبية', 'اسم الجهة المُدربه؟', 'اسم الجهه المُدربه؟', 'اسم الجهة', 'جهة التدريب :', 'جهة التدريب']);

        if ($name === null) {
            return null;
        }

        $normalized = Arabic::normalize($name);

        if ($normalized === '' || in_array($normalized, self::INVALID_VALUES, true)) {
            return null;
        }

        return $name;
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $keys
     */
    private function extractFirstValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = $this->cleanValue($row[$key]);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  list<?string>  $values
     */
    private function extractFirstUrl(array $values): ?string
    {
        foreach ($values as $value) {
            $cleaned = $this->cleanValue($value);

            if ($cleaned === null) {
                continue;
            }

            if (preg_match('/https?:\/\/[^\s]+/iu', $cleaned, $matches) === 1) {
                return rtrim($matches[0], ' ,;');
            }
        }

        return null;
    }

    private function mapCompanyType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Arabic::normalize($value);

        if (str_contains($normalized, 'حكومي')) {
            return CompanyType::Government->value;
        }

        if (str_contains($normalized, 'خاص')) {
            return CompanyType::Private->value;
        }

        if (str_contains($normalized, 'غير ربحي') || str_contains($normalized, 'غيرربحي') || str_contains($normalized, 'جمعيه')) {
            return CompanyType::NonProfit->value;
        }

        return CompanyType::Other->value;
    }

    private function parseOverallScore(?string $scoreValue, ?string $recommendationText): float
    {
        $numeric = $this->parseNumber($scoreValue);

        if ($numeric !== null) {
            // Some CSV sheets store "benefit score" out of 10, while ratings are 1..5.
            if ($numeric > 5 && $numeric <= 10) {
                $numeric /= 2;
            }

            return (float) $this->clamp(round($numeric, 1), 1, 5);
        }

        $recommendation = $this->mapRecommendation($recommendationText, null);

        return match ($recommendation) {
            Recommendation::Yes->value => 4.5,
            Recommendation::Maybe->value => 3.5,
            Recommendation::No->value => 2.0,
            default => 3.0,
        };
    }

    private function mapRecommendation(?string $value, ?float $overall): string
    {
        if ($value !== null) {
            $normalized = Arabic::normalize($value);

            if (str_contains($normalized, 'نعم') || str_contains($normalized, 'انصح') || str_contains($normalized, 'ارشح') || str_contains($normalized, 'كلاهما')) {
                return Recommendation::Yes->value;
            }

            if (str_contains($normalized, 'ربما')) {
                return Recommendation::Maybe->value;
            }

            if (str_contains($normalized, 'لا')) {
                return Recommendation::No->value;
            }
        }

        if ($overall !== null) {
            return match (true) {
                $overall >= 4.0 => Recommendation::Yes->value,
                $overall >= 3.0 => Recommendation::Maybe->value,
                default => Recommendation::No->value,
            };
        }

        return Recommendation::Maybe->value;
    }

    private function mapModality(?string $modalityText, ?string $location, ?string $experienceText): string
    {
        $haystack = Arabic::normalize($this->joinParts(array_filter([$modalityText, $location, $experienceText])));

        $isRemote = str_contains($haystack, 'عن بعد');
        $isOnsite = str_contains($haystack, 'حضوري') || str_contains($haystack, 'مقر') || str_contains($haystack, 'شركات') || str_contains($haystack, 'وزارات');

        if ($isRemote && $isOnsite) {
            return Modality::Hybrid->value;
        }

        if ($isRemote) {
            return Modality::Remote->value;
        }

        return Modality::Onsite->value;
    }

    private function parseDurationMonths(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = Arabic::normalize($this->replaceArabicDigits($value));

        if (str_contains($normalized, 'اكثر') || str_contains($normalized, 'حسب')) {
            return null;
        }

        $number = $this->parseNumber($normalized);

        if ($number === null) {
            if (str_contains($normalized, 'شهرين')) {
                return 2;
            }

            if (str_contains($normalized, 'شهر')) {
                return 1;
            }

            return null;
        }

        if (str_contains($normalized, 'اسبوع') || str_contains($normalized, 'week')) {
            return (int) $this->clamp((int) ceil(($number * 7) / self::DAYS_PER_MONTH), 1, 12);
        }

        if (str_contains($normalized, 'يوم') || str_contains($normalized, 'day')) {
            return (int) $this->clamp((int) ceil($number / self::DAYS_PER_MONTH), 1, 12);
        }

        return (int) $this->clamp((int) round($number), 1, 12);
    }

    private function parseStipend(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = Arabic::normalize($this->replaceArabicDigits($value));

        if (
            str_contains($normalized, 'لا يوجد')
            || str_contains($normalized, 'لايوجد')
            || str_contains($normalized, 'لاتوجد')
            || str_contains($normalized, 'لا')
            || str_contains($normalized, 'شهاده')
            || str_contains($normalized, 'تكريم')
            || str_contains($normalized, 'لم يتم')
        ) {
            return null;
        }

        $number = $this->parseNumber($normalized);

        if ($number === null) {
            return null;
        }

        return (int) round($number);
    }

    private function parseBoolean(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $normalized = Arabic::normalize($value);

        if (str_contains($normalized, 'نعم') || str_contains($normalized, 'ايوه') || str_contains($normalized, 'yes') || str_contains($normalized, 'true')) {
            return true;
        }

        if (str_contains($normalized, 'لا') || str_contains($normalized, 'no') || str_contains($normalized, 'false')) {
            return false;
        }

        return null;
    }

    private function parseHadSupervisor(?string $experienceText): ?bool
    {
        if ($experienceText === null) {
            return null;
        }

        $normalized = Arabic::normalize($experienceText);

        if (str_contains($normalized, 'مشرف') || str_contains($normalized, 'مدرب') || str_contains($normalized, 'توجيه')) {
            return true;
        }

        return null;
    }

    private function parseWillingToHelp(?string $contact): ?bool
    {
        if ($contact === null) {
            return null;
        }

        $normalized = Arabic::normalize($contact);

        if (str_contains($normalized, 'ما اتوقع') || str_contains($normalized, 'لا')) {
            return false;
        }

        return true;
    }

    private function extractProsFromExperience(?string $experienceText): ?string
    {
        if ($experienceText === null) {
            return null;
        }

        $normalized = Arabic::normalize($experienceText);

        if (str_contains($normalized, 'مميزات') || str_contains($normalized, 'مزايا') || str_contains($normalized, 'ممتع') || str_contains($normalized, 'متعاون')) {
            return Str::limit($experienceText, 255, '');
        }

        return null;
    }

    private function extractConsFromExperience(?string $experienceText, ?string $notes): ?string
    {
        $candidate = $notes ?? $experienceText;

        if ($candidate === null) {
            return null;
        }

        $normalized = Arabic::normalize($candidate);

        if (str_contains($normalized, 'عيوب') || str_contains($normalized, 'سيئ') || str_contains($normalized, 'ما انصح') || str_contains($normalized, 'مافي')) {
            return Str::limit($candidate, 255, '');
        }

        return null;
    }

    private function extractCity(?string $location): ?string
    {
        if ($location === null) {
            return null;
        }

        $normalized = Arabic::normalize($location);

        foreach (self::CITY_KEYWORDS as $keyword => $city) {
            if (str_contains($normalized, Arabic::normalize($keyword))) {
                return $city;
            }
        }

        return null;
    }

    private function parseNumber(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->replaceArabicDigits($value);

        if (preg_match('/(\d+(?:\.\d+)?)/', $normalized, $matches) !== 1) {
            return null;
        }

        return (float) $matches[1];
    }

    private function replaceArabicDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    /**
     * @param  array<string, array<string, string|null>>  $records
     * @param  array{name:string,type:?string,website:?string,description:?string,status:string}  $incoming
     */
    private function mergeCompanyRecord(array &$records, array $incoming): void
    {
        $normalizedName = Arabic::normalize($incoming['name']);

        if ($normalizedName === '') {
            return;
        }

        $existing = $records[$normalizedName] ?? null;

        if ($existing === null) {
            $records[$normalizedName] = $incoming;

            return;
        }

        $records[$normalizedName] = [
            'name' => $this->preferredCompanyName((string) $existing['name'], $incoming['name']),
            'type' => $this->mergeCompanyType($existing['type'] ?? null, $incoming['type']),
            'website' => $existing['website'] ?? $incoming['website'],
            'description' => $this->joinParts(array_filter([$existing['description'] ?? null, $incoming['description']])),
            'status' => 'approved',
        ];
    }

    private function mergeCompanyType(?string $existing, ?string $incoming): ?string
    {
        if ($existing === null) {
            return $incoming;
        }

        if ($existing === CompanyType::Other->value && $incoming !== null) {
            return $incoming;
        }

        return $existing;
    }

    /**
     * @param  array<string, array<string, string|null>>  $companyRecords
     * @return array<string, Company>
     */
    private function persistCompanies(array $companyRecords): array
    {
        $persisted = [];

        foreach ($companyRecords as $normalizedName => $companyData) {
            $company = Company::query()->firstOrNew(['name_normalized' => $normalizedName]);
            $company->name = (string) $companyData['name'];
            $company->type = $companyData['type'];
            $company->website = $companyData['website'];
            $company->description = $companyData['description'];
            $company->status = 'approved';
            $company->save();

            $persisted[$normalizedName] = $company;
        }

        return $persisted;
    }

    /**
     * @param  list<array<string, mixed>>  $ratingRecords
     * @param  array<string, Company>  $companiesByNormalized
     */
    private function persistRatings(array $ratingRecords, array $companiesByNormalized): void
    {
        $existingSignatures = [];

        Rating::query()
            ->with('company:id,name_normalized')
            ->lazyById()
            ->each(function (Rating $rating) use (&$existingSignatures): void {
                $companyNormalized = $rating->company?->name_normalized;

                if ($companyNormalized === null) {
                    return;
                }

                $existingSignatures[$this->buildRatingSignature($companyNormalized, $rating->toArray())] = true;
            });

        $now = now();
        $rowsToInsert = [];

        foreach ($ratingRecords as $ratingData) {
            $companyNormalized = Arabic::normalize((string) Arr::get($ratingData, 'company_name'));

            if (! isset($companiesByNormalized[$companyNormalized])) {
                continue;
            }

            $company = $companiesByNormalized[$companyNormalized];
            $payload = Arr::except($ratingData, ['company_name']);
            $payload['company_id'] = $company->id;

            $signature = $this->buildRatingSignature($companyNormalized, $payload);

            if (isset($existingSignatures[$signature])) {
                continue;
            }

            $existingSignatures[$signature] = true;
            $rowsToInsert[] = $payload + ['created_at' => $now, 'updated_at' => $now];

            if (count($rowsToInsert) >= self::BATCH_INSERT_SIZE) {
                Rating::query()->insert($rowsToInsert);
                $rowsToInsert = [];
            }
        }

        if ($rowsToInsert !== []) {
            Rating::query()->insert($rowsToInsert);
        }
    }

    private function preferredCompanyName(string $existing, string $incoming): string
    {
        $incomingArabicScore = preg_match_all(self::ARABIC_UNICODE_PATTERN, $incoming);
        $existingArabicScore = preg_match_all(self::ARABIC_UNICODE_PATTERN, $existing);
        $incomingArabicScore = $incomingArabicScore === false ? 0 : $incomingArabicScore;
        $existingArabicScore = $existingArabicScore === false ? 0 : $existingArabicScore;

        if ($incomingArabicScore > $existingArabicScore) {
            return $incoming;
        }

        if ($incomingArabicScore === $existingArabicScore && mb_strlen($incoming) > mb_strlen($existing)) {
            return $incoming;
        }

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildRatingSignature(string $companyNormalized, array $payload): string
    {
        $signatureParts = [
            $companyNormalized,
            Arabic::normalize((string) Arr::get($payload, 'role_title')),
            (string) Arr::get($payload, 'duration_months'),
            (string) Arr::get($payload, 'modality'),
            (string) Arr::get($payload, 'stipend_sar'),
            (string) Arr::get($payload, 'overall_rating'),
            (string) Arr::get($payload, 'recommendation'),
            Arabic::normalize((string) Arr::get($payload, 'review_text')),
            Arabic::normalize((string) Arr::get($payload, 'pros')),
            Arabic::normalize((string) Arr::get($payload, 'cons')),
            Arabic::normalize((string) Arr::get($payload, 'application_method')),
            Arabic::normalize((string) Arr::get($payload, 'contact_method')),
        ];

        return sha1(implode('|', $signatureParts));
    }

    /**
     * @param  list<string>  $parts
     */
    private function joinParts(array $parts): ?string
    {
        $unique = array_values(array_unique(array_filter(array_map(fn ($part) => $this->cleanValue($part), $parts))));

        if ($unique === []) {
            return null;
        }

        return implode(' | ', $unique);
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function joinParagraphs(array $paragraphs): ?string
    {
        $unique = array_values(array_unique(array_filter(array_map(fn ($part) => $this->cleanValue($part), $paragraphs))));

        if ($unique === []) {
            return null;
        }

        return implode("\n", $unique);
    }

    private function prefixed(string $label, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $label.': '.$value;
    }

    private function clamp(float|int $value, float|int $min, float|int $max): float|int
    {
        return max($min, min($max, $value));
    }
}
