<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Rating;
use App\Support\Arabic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real student responses from the College of Computing Summer 2022 (1443H)
 * training experiences form.
 *
 * Source CSV:
 *   جمع تجارب التدريب الصيفي لكلية الحاسب ونظم المعلومات 1443هـ (الردود) - ردود النموذج 1.csv
 *
 * Idempotency strategy:
 *   Before inserting a rating, we check for an existing row with the same
 *   company_id AND the same sha1 fingerprint over the first 120 chars of the
 *   normalized review_text. If found, the row is skipped. This means the seeder
 *   can be re-run safely without creating duplicates.
 */
class SummerTraining2022Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->rows() as $row) {
                $this->seedRow($row);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function seedRow(array $row): void
    {
        $normalizedName = Arabic::normalize($row['company_name']);

        $company = Company::query()
            ->where('name_normalized', $normalizedName)
            ->first();

        if (! $company) {
            $company = Company::create([
                'name' => $row['company_name'],
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ]);
        }

        // Idempotency: compute a PHP-side sha1 fingerprint over the first 120
        // characters of the review text and compare with any rating already
        // attached to this company. SQLite has no native sha1() so we compute
        // it in PHP — still O(ratings for this company), which is tiny.
        $fingerprint = sha1(mb_substr(trim($row['review_text']), 0, 120));

        $alreadyExists = Rating::query()
            ->where('company_id', $company->id)
            ->get(['review_text'])
            ->contains(function (Rating $rating) use ($fingerprint): bool {
                return sha1(mb_substr(trim((string) $rating->review_text), 0, 120)) === $fingerprint;
            });

        if ($alreadyExists) {
            return;
        }

        Rating::create([
            'company_id' => $company->id,
            'role_title' => $row['role_title'],
            'department' => $row['department'] ?? null,
            'city' => $row['city'],
            'duration_months' => $row['duration_months'],
            'sector' => $row['sector'] ?? null,
            'modality' => $row['modality'],
            'stipend_sar' => null, // Source only records yes/no, no amount disclosed.
            'had_supervisor' => $row['had_supervisor'] ?? null,
            'mixed_env' => $row['mixed_env'],
            'job_offer' => $row['job_offer'],
            'rating_mentorship' => $row['rating_mentorship'],
            'rating_learning' => $row['rating_learning'],
            'rating_culture' => $row['rating_culture'],
            'rating_compensation' => $row['rating_compensation'],
            'overall_rating' => $row['overall_rating'],
            'recommendation' => $this->recommendationFor((int) $row['overall_rating']),
            'review_text' => $row['review_text'],
            'pros' => $row['pros'] ?? null,
            'cons' => $row['cons'] ?? null,
            'reviewer_name' => null,
            'reviewer_major' => null,
        ]);
    }

    protected function recommendationFor(int $overall): string
    {
        return match (true) {
            $overall >= 4 => 'yes',
            $overall === 3 => 'maybe',
            default => 'no',
        };
    }

    /**
     * Cleaned, inlined responses from the source CSV.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rows(): array
    {
        return [
            // Row 2 — الاساليب الذكيه (مكه) — rating 2
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة',
                'role_title' => 'متدرب تعاوني',
                'department' => 'مسار إنترنت الأشياء',
                'duration_months' => 7,
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 2,
                'rating_mentorship' => 2,
                'rating_learning' => 2,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'review_text' => 'مسار انترنت الاشياء تجربه سيئه وم استفدت كثير اشتغلنا على روبوت مسارات متداخله منها هندسه وذكاء اصطناعي وغيره',
                'cons' => 'مسارات متداخلة ولم أستفد كثيراً',
            ],

            // Row 3 — غرفة مكة المكرمه — rating 5
            [
                'company_name' => 'غرفة مكة المكرمة',
                'city' => 'مكة المكرمة',
                'role_title' => 'مبرمجة ومطورة',
                'department' => 'التحول الرقمي',
                'duration_months' => 1, // ٤٠ يوم → ~1 شهر
                'sector' => 'nonprofit',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'review_text' => 'مبرمجه ومطوره - قسم التحول الرقمي - مشاريع ف تحليل المشاريع - تقارير من قواعد بيانات - ui&ux - سكيورتي',
                'pros' => 'تنوع المشاريع بين التحليل وقواعد البيانات وUI/UX والأمن',
            ],

            // Row 4 — الابتكارات العالمية — rating 5
            [
                'company_name' => 'الابتكارات العالمية',
                'city' => 'وادي مكة',
                'role_title' => 'مصممة واجهات',
                'department' => null,
                'duration_months' => 2,
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => true,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'review_text' => 'كنت مصممة واجهات صممت مشروع واجهات موقع الكتروني وبعد الانتهاء من التصميم قمت بتحليل المشروع SRS',
                'pros' => 'البيئة متعاونة ومحترمة',
                'cons' => null,
            ],

            // Row 5 — مستشفى الملك عبدالعزيز (مكة) — rating 5
            [
                'company_name' => 'مستشفى الملك عبدالعزيز',
                'city' => 'مكة',
                'role_title' => 'فنية دعم فني',
                'department' => 'قسم تقنية المعلومات',
                'duration_months' => 2,
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'فنية دعم فني، قسم تقنية المعلومات، برمجة اكسل وتصليح الاجهزة والطابعات وفرمته للأجهزة ، العيوب ما كان في كراسي تكفي كل البنات لاننا كنا تقريبا ٢٠ متدربة، المزايا الصراحة اتعلمت اشياء كتير في الحياة ممكن ما كان شي انا رح اتخصص فيه بس كعملومات للتعامل مع جهازي الشخصي استفدت مرا كتير، حتى كان في برمجة لموقع خاص بالقسم.',
                'pros' => 'تعلمت أشياء كثيرة مفيدة حتى خارج التخصص',
                'cons' => 'نقص الكراسي للعدد الكبير من المتدربات (~20)',
            ],

            // Row 6 — سدايا (الرياض) — rating 4
            [
                'company_name' => 'سدايا',
                'city' => 'الرياض',
                'role_title' => 'متدرب أمن سيبراني',
                'department' => 'الأمن السيبراني',
                'duration_months' => 2, // ٨ اسابيع
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'review_text' => 'عملت في ادارة تخص الأمن السيبراني وتعرفت على المجال عمليا ونظريا في أكثر من قسم، البيئة محفزة وتؤهلك لسوق العمل، الموظفين من الكفاءات السعودية الناجحة، عيب التدريب انه لم تكن ادارتي على علم بمجيئي مسبقا، ولكن أنصح بسدايا جدا.',
                'pros' => 'بيئة محفزة، كفاءات سعودية، تهيئة لسوق العمل',
                'cons' => 'الإدارة لم تكن على علم بقدومي مسبقاً',
            ],

            // Row 7 — الاساليب الذكية (مكة) — rating 2 (multi-line)
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة',
                'role_title' => 'متدربة إنترنت الأشياء',
                'department' => 'مسار إنترنت الأشياء',
                'duration_months' => 2, // ٨ اسابيع
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 2,
                'rating_mentorship' => 1,
                'rating_learning' => 2,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'review_text' => "كان فيه ٤ مسارات، وانا اخترت مسار انترنت الاشياء\nالمسار المدفوع تقدري تاخدي شهادات.\nكانت في مهام كثير في مساري بداية التدريب قالو سوي ٤ منها عشان تاخذي الشهادة بس بعدين قالو خلاص مهمتين تكفي، وسويت مهمة من مسار ثاني عشان اخذ شهادة.\nفي التدريب مو مهم تحضري او تكوني عن بعد\nصح انا كنت مع المدفوع بس ماكنت اروح حضوري، لأنو نفس الحضوري ناخذو عن بعد مره مايفرق، ما انصح تاخذو المدفوع لأنو عالفاضي\nكان عددنا كبير فمحد منتبه انتي ايش سويتي او انتي فاهمة ولالا",
                'cons' => 'عدد المتدربين كبير وضعف المتابعة، المسار المدفوع غير مجدٍ',
            ],

            // Row 13 (smart method, d, iot) — SKIP: review_text < 20 chars.
            // Row 14 (الاسلبي الذكية, review="لا") — SKIP: review_text < 20 chars.

            // Row 15 — الاساليب الذكية (مكة-جدة-الرياض) — rating 3
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة',
                'role_title' => 'متدربة إنترنت الأشياء',
                'department' => 'مسار إنترنت الأشياء',
                'duration_months' => 2, // ٨-٩ اسابيع
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => false,
                'job_offer' => false,
                'overall_rating' => 3,
                'rating_mentorship' => 2,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'review_text' => 'مسار انترنت الاشياء اشغلت على اربع تاسكات كل اثنين مع بعض التدريب بشكل عام كان سهل بس في البدايه ضياااااع لازم الواحد يسأل ويستفسر ويكلم المشرفين عن اي شي ماعرف له',
                'cons' => 'ضياع في بداية التدريب، تحتاج المبادرة للسؤال',
            ],

            // Row 16 — إيسار — rating 5 (hybrid with 1 day onsite → treat as onsite per schema, review mentions "عن بعد" mostly → remote)
            [
                'company_name' => 'إيسار',
                'city' => 'مكة المكرمة',
                'role_title' => 'مطورة ويب/تطبيقات',
                'department' => 'التطوير',
                'duration_months' => 2, // ٨ أسابيع
                'sector' => 'private',
                'modality' => 'remote', // review explicitly: "عن بعد، فقط يوم واحد في الأسبوع حضوري"
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => "تم تقسيمنا الى مسارات، مسار الويب، التطبيقات والتصميم. وأعطوا لكل مسار دورات، مثلًا الويب درسوا Laravel، التطبيقات Flutter، وهكذا.\nبعدها طلبوا مننا نبني نظام، حلّلنا النظام مع المهندس مبدئيًا و بدينا، طبعًا كنا نستشير المهندس بالأغلاط اللي نواجهها، الوقت قصير كان فنوعًا ما التجربة ناقصة رغم انه استفدنا كثير و خيرونا اذا نبغا نكمل أسبوعين كمان عشان نستفيد أكثر.\nأشوف انها كانت تجربة واقعية في بناء نظام مع فريق + المهندس كان خبير و استفدنا منه كثير",
                'pros' => 'مهندس خبير، تجربة واقعية لبناء نظام، دورات لكل مسار',
                'cons' => 'الوقت قصير',
            ],

            // Row 19 — الاساليب الذكيه (مكة) — rating 1
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة',
                'role_title' => 'متدربة إنترنت الأشياء',
                'department' => 'مسار إنترنت الأشياء ونظم البيانات',
                'duration_months' => 2,
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 1,
                'rating_mentorship' => 1,
                'rating_learning' => 1,
                'rating_culture' => 1,
                'rating_compensation' => 1,
                'review_text' => 'كنت في مسار انترنت الاشياء ونظم البيانات ما كان فيه اهتمام ولا شي والتدريب بكبره كانه شو بس',
                'cons' => 'غياب الاهتمام بالمتدربين',
            ],

            // Row 20 — الاساليب الذكية (مكة) — rating 5
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'duration_months' => 2, // ٩ اسابيع
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'اشتغلت على كل المجالات الهندسة الكهربائيه والهندسة الميكانيكية والذكاء الاصطناعي وانترنت الاشياء',
                'pros' => 'تغطية مجالات متعددة',
                'cons' => 'ما أنصح فيه كتدريب جامعي، الأفضل بعد التخرج',
            ],

            // Row 21 — مستشفى الملك عبدالعزيز (الزاهر) — rating 5
            [
                'company_name' => 'مستشفى الملك عبدالعزيز',
                'city' => 'مكة',
                'role_title' => 'متدربة دعم فني',
                'department' => 'تقنية المعلومات',
                'duration_months' => 2,
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'review_text' => 'كانت تجربه جدا ممتعه كل اسبوع تقريبا نتعلم شي جديد مثلا ( انظمة الحمايه ، استقبال الاتصالات وحل المشكلات ، تغيير الاجهزه ومعرفة الخلل ، نقاط الشبكات ، ادخال وتعديل بيانات الموظفين وغيرها ..) لكن بشكل عام كانت تجربه مفيده والمهندسين الموجودين جدا متعاونين',
                'pros' => 'تعلم شيء جديد كل أسبوع، مهندسون متعاونون',
            ],

            // Row 22 — مستشفى الملك عبدالعزيز (الزاهر) — rating 3
            [
                'company_name' => 'مستشفى الملك عبدالعزيز',
                'city' => 'مكة',
                'role_title' => 'متدربة دعم فني',
                'department' => 'الدعم الفني',
                'duration_months' => 2, // 45 يوم → ~1-2 شهر، نعتمد 2
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 3,
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'review_text' => 'دعم فني ، مسؤلين عن استقبال اتصالات اعطال الاجهزه و الشاشات و الطابعات في جميع المستشفى واصلاحها',
            ],

            // Row 23 — امانه العاصمه (الكعكية) — rating 2
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'city' => 'مكة',
                'role_title' => 'متدربة حلول رقمية',
                'department' => 'إدارة الحلول الرقمية',
                'duration_months' => 2, // 45 يوم
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 2,
                'rating_mentorship' => 1,
                'rating_learning' => 2,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'review_text' => "في ادارة الحلول الرقمية في خطة بس ما كان في احد يعطينا هي بسبب انهاء عقد الشركة المسؤوله الين غيرو الخطه\nاخذنا بس يومين في الاسبوع ناخذ مهمه بسيطة نسوي رسمات زي هندسه ٢\nمافي فايدة كثير لان اغلب الاشياء نعرفها بس حلو لو تبو شي خفيف ما فيه شغل كثير\nو اكبر مشكله هي اللخبطه تعيشك في قلق بس في النهايه يسير كل شي زي المطلوب",
                'cons' => 'لخبطة وقلق بسبب انتهاء عقد الشركة المسؤولة عن الخطة',
            ],

            // Row 27 — فندق دار التوحيد انتركونتننتال — rating 5
            [
                'company_name' => 'فندق دار التوحيد انتركونتيننتال',
                'city' => 'مكة',
                'role_title' => 'متدرب دعم فني',
                'department' => 'IT',
                'duration_months' => 2, // ٤٥ يوم
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'تدربت في قسم IT وكان مدربنا يعلمنا ويقولنا عن كل شيء يخص IT للفندق بما ان الجهة فندق ، فتعلمنا عن الانظمة السيرفرات اللي يستخدمونها وعن لمن توصلهم مشكلات من الاقسام الثانية او احد الضيوف يكون عنده مشكلة يبلغون قسم IT وهم يحلون مشكلاتهم التقنية وهكذا ، من مزايا التدريب انه تجربة حقيقة لشغلهم في القسم في الفندق جربنا كيف بيئة العمل في الفندق وكيف قسم IT مسؤول عن تشغيل كل الانظمة عندهم ومستعدين لحل اي مشكلة تظهر في اي قسم او لدى اي ضيف من ضيوف الفندق - عيوب التدريب موظفين القسم قليلين فكانوا ينشغلون اغلب الوقت بشغلهم فما يكون عندهم وقت كافي يدربونا ، يعطينا مهمة ويتركنا نسويها يعني طول اليوم لأنه ماعنده وقت يدربنا على اكثر من شيء في اليوم بحكم انه موظفين القسم في كل شفت كان قليل وضغط عليهم',
                'pros' => 'تجربة حقيقية لعمل قسم IT في فندق',
                'cons' => 'موظفو القسم قليلون ومنشغلون فوقت التدريب محدود',
            ],

            // Row 28 — شركة الابتكارات GIT — rating 5
            [
                'company_name' => 'شركة الابتكارات GIT',
                'city' => 'وادي مكة',
                'role_title' => 'مصممة UI/UX ومحللة متطلبات',
                'department' => 'UI/UX وتحليل المتطلبات',
                'duration_months' => 2,
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'review_text' => "تدربت بقسم ui-ux  وقسم كتابة المتطلبات وتحليلها, الفرصة كانت رهيبة وحلوة واللي دربوني بنات مره كانو لطيفات ومتعاونات\nاشتغلنا على مشروع هوا اساسا كان مطلوب لعميل والشركة سوته بس احنا حاكيناه وحللنا متطلباته وصممنا الواجهات",
                'pros' => 'مدربات لطيفات ومتعاونات، مشروع واقعي',
            ],

            // Row 30 — شركة رؤية الخبراء الاستشارية — rating 5
            [
                'company_name' => 'شركة رؤية الخبراء الاستشارية',
                'city' => 'وادي مكة',
                'role_title' => 'متدربة تحليل بيانات وذكاء أعمال',
                'department' => 'تحليل البيانات وذكاء الأعمال',
                'duration_months' => 2, // ٦ أسابيع
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'review_text' => "كان في ثلاث مسارات بس هم في الصيفي بدؤو مسارين :\n١- تحليل البيانات وذكاء الأعمال\n٢- الرؤية بالحاسب\nانا دخلت مسار تحليل البيانات وذكاء الأعمال وكان لمدة ٦ أسابيع مفيدة، التدريب حلو من كل النواحي الدوام ٧ ساعات من ١٠ الصباح ل ٥ العصر من الاحد للخميس،هم يدربوك ع مسارك و في نهاية التدريب يطلبو منك تسوي مشروع ويساعدوك عشان تطلعه بأفضل صورة وتعرضه في نهاية التدريب، المشروع قروبات مو فردي، بيئة العمل رائعه ومحفزه، ويسوو زي الدورات لمواضيع مختلفه طوال السته اسابيع، دورات مفيدة ومثيرة للاهتمام وبتقابل شخصيات رائعة وكلهم دوافير ماشاءالله ،ويسوو فعاليات تقريبا كل يوم، هذا شي حلو ومو حلو في نفس الوقت لانو احيان ما يخلينا نخلص مهامنا حق اليوم نضطر نرجع نكمل في البيت والشي الي مو حلو هو مكان الشركة مافي حوله شي نشفان ولو تبا اكل لازم تطلب،الشركة كانت توفر مويه وعصيرات وبسكويتات واحيان يجيبو غدا بس مو دايم، بشكل مجمل التدريب عندهم ممتاز، انا دخلت ومو عارفه ايش هي تحليل البيانات وذكاء الأعمال وطلعت من عندهم فاهمه وعارفه كيف أسويه، جهة عمل أنصح بها\nتخصصي علوم حاسب",
                'pros' => 'بيئة رائعة ومحفزة، دورات وفعاليات، يقدمون وجبات',
                'cons' => 'الفعاليات أحياناً تؤخر إنجاز المهام، موقع الشركة بعيد عن المطاعم',
            ],

            // Row 35 — مستشفى الملك عبدالعزيز (الزاهر) — rating 3
            [
                'company_name' => 'مستشفى الملك عبدالعزيز',
                'city' => 'مكة',
                'role_title' => 'متدربة تقنية معلومات',
                'department' => 'تقنية المعلومات',
                'duration_months' => 2, // 45 يوم
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 3,
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'متدربة فقسم تقنية المعلومات، مهتمين فالصيانة والشبكات اكثر من البرمجة، ماعندهم خطة واضحة، متعاونين ومتساهلين جدا',
                'pros' => 'متعاونون ومتساهلون',
                'cons' => 'لا توجد خطة واضحة، تركيز على الصيانة أكثر من البرمجة',
            ],

            // Row 36 — الاساليب الذكية — rating 4
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة',
                'role_title' => 'متدربة إنترنت الأشياء',
                'department' => 'إنترنت الأشياء',
                'duration_months' => 2, // شهرين ونص
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 3,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'عندهم اقسام متنوعه: ذكاء اصطناعي، انترنت الاشياء، كم قسم متعلق بالهاردوير، انا اخذت انترنت الاشياء، كان عباره عن برمجة واجهات المستخدم: يعني تبرمجي مواقع، داتابيس، وتطبيقات كمان. اهم شي تتجاوزي عدد معين من المهمات عشان تاخذي الشهاده',
                'pros' => 'أقسام متنوعة، تجربة برمجة واجهات ومواقع وتطبيقات',
            ],

            // Row 37 — شرطة العاصمة المقدسة — rating 4
            [
                'company_name' => 'شرطة العاصمة المقدسة',
                'city' => 'المسفلة، مكة',
                'role_title' => 'متدرب صيانة شبكات',
                'department' => 'الشبكات',
                'duration_months' => 2, // ٩ أسابيع
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'صيانة شبكات بالعموم. تدريب في قسم الشبكات لدى شرطة العاصمة المقدسة لمدة حوالي ٩ أسابيع.',
            ],

            // Row 39 — الشركة السعودية للكهرباء (العمرة) — rating 4
            [
                'company_name' => 'الشركة السعودية للكهرباء',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تقنية معلومات',
                'department' => 'تقنية المعلومات',
                'duration_months' => 1, // ٤٠ يوم
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => false,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'تدربت في مبنى تقنية المعلومات والبيئة كانت جيدة والموظفين متعاونين مع المتدربين، السلبية الوحيدة انه لم توجد خطة تدريبية مسبقة وكانت الخطة عشوائية.',
                'pros' => 'بيئة جيدة، موظفون متعاونون',
                'cons' => 'لا توجد خطة تدريبية مسبقة',
            ],

            // Row 40 — Digital solution provider (DSP) — rating 5
            [
                'company_name' => 'Digital Solution Provider (DSP)',
                'city' => 'مكة - برج الساعة',
                'role_title' => 'مهندس شبكات',
                'department' => 'الشبكات',
                'duration_months' => 1, // ٤٠ يوم
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => false,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => "المسمى مهندس شبكات\nقسم الشبكات\nفقط مهام تعطى من المدرب\nمن المزايا الاجهزه متوفره وتعليم احترافي\nالعيوب يجب على المتدرب طلب مهام من المدرب",
                'pros' => 'أجهزة متوفرة وتعليم احترافي',
                'cons' => 'يجب على المتدرب طلب المهام',
            ],

            // Row 45 — النقابة العامة للسيارات — rating 5
            [
                'company_name' => 'النقابة العامة للسيارات',
                'city' => 'مكة المكرمة - النزهة',
                'role_title' => 'مبرمج',
                'department' => 'إدارة تقنية المعلومات',
                'duration_months' => 2, // ٤٥ يوم عمل
                'sector' => 'nonprofit',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'review_text' => "التدريب في قسم إدارة تقنية المعلومات بمسمى مبرمج\nاشتغلنا على تطوير نظام النقابة\nاتعلمنا اكثر عن قواعد البيانات\nاتعلمنا عن ذكاء الاعمال واشتغلنا على مشروعين فيها\nالقسم كامل متعاون ومرحب بالمتدربين",
                'pros' => 'قسم متعاون، تعلم قواعد بيانات وذكاء أعمال',
                'cons' => 'لا يوجد مكان مخصص للمتدربات',
            ],

            // Row 51 — أمانة العاصمة - إدارة المدن الذكية — rating 1
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'city' => 'مكة - الكعكية',
                'role_title' => 'متدربة مدن ذكية',
                'department' => 'إدارة المدن الذكية',
                'duration_months' => 2, // 6 اسابيع
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 1,
                'rating_mentorship' => 1,
                'rating_learning' => 1,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'review_text' => 'في البداية قدمنا في مبنى تقنية المعلومات وقسمونا ل3 اقسام موزعين في المعابدة والكعكية ( دعم فني، مدن ذكية، حلول رقمية) .. من ناحية تدريب المدن الذكية كان اغلبه كلام نظري شرح لادارتهم وسوينا جولات على مبانيهم واخر شي طلبو مننا نقترح لهم مبادرات ذكية عشان ينفذونها وعلى اخر اسبوع صار فيه تغيير لمدير القسم وأرسلو لنا مهندس من قسم الحلول الرقمية شرح لنا على السريع دورة حياة البرمجيات والفرق بين مختبر البرمجيات ومحلل البرمجيات وطلب مننا نجيب فكرة ونرسم لها يوز كيس وورك فلو دايقرام وسوينا بروتوتايب وانتهى التدريب .. باختصار تحسهم متورطين بالمتدربين ومو فاضيين لهم والمدير الجديد بنفسه قال لنا انه فيه سوء تنظيم للمتدربين هذا العام ف ماادري عن باقي السنوات كيف ولكن تجربتي كانت مخيبة للامال',
                'cons' => 'سوء تنظيم للمتدربين، اغلب المحتوى نظري',
            ],

            // Row 52 — الأساليب الذكية — rating 3
            [
                'company_name' => 'الأساليب الذكية',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدربة IoT',
                'department' => 'إنترنت الأشياء',
                'duration_months' => 2, // ٨ أسابيع
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 3,
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'review_text' => 'قسم ال IoT تشغيل الدوائر الكهربائية عن طريق البرمجة. التدريب كان لمدة ٨ أسابيع في مسار إنترنت الأشياء.',
            ],

            // Row 53 — امانة العاصمة (دعم فني) — rating 2
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'city' => 'مكة',
                'role_title' => 'متدربة دعم فني',
                'department' => 'الدعم الفني',
                'duration_months' => 2, // 8 اسابيع
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 2,
                'rating_mentorship' => 2,
                'rating_learning' => 2,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'review_text' => 'دعم فني الوضع ملخبط ما كان في خطة تدريب (اول اسبوعين كانت اجازه الحج اعطونا حالة واقعة نحلها - اشتغلنا في الشبكات - الصيانة نشوف بلاغات ونحلها )',
                'cons' => 'لا توجد خطة تدريب واضحة',
            ],

            // Row 54 — الشركة السعودية للكهرباء (قسم البلاغات) — rating 4, job_offer yes
            [
                'company_name' => 'الشركة السعودية للكهرباء',
                'city' => 'مكة',
                'role_title' => 'متدرب دعم فني',
                'department' => 'قسم البلاغات',
                'duration_months' => 2, // 45 يوم
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => true,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'قسم البلاغات - حل المشاكل التقنية الي تواجه كمبيوترات الموظفين واستقبال بلاغاتهم. تدريب في مركز تقنية المعلومات بمكة.',
            ],

            // Row 55 — أمانة العاصمة (الدعم الفني، المعابدة) — rating 3
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'city' => 'المعابدة، مكة',
                'role_title' => 'متدربة دعم فني',
                'department' => 'إدارة الدعم الفني',
                'duration_months' => 2, // ٨ اسابيع
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 3,
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'review_text' => 'اول اسبوعين كانت عن بعد ارسلت لنا حاله واقعية وعملنا على تصميم طريقة لاتمتتها بكتابة الخطوات ورسم الفلو شارت الثلاث اسابيع التاليه عملنا على اعمال الدعم الفني مثل عمل فورمات للجهاز التعرف على برامجهم وطريقة تحميلها من السيرفر وتركيب سلك شبكة ولكن بصراحة كان فيه كثير وقت فراغ مانسوي فيه شيء الثلاث اسابيع الاخير وضحنا لهم استيائنا وتم تغيير المشرفه واعطائنا بنود عندهم لتنفيذ الاعمال وعلى اساسها قمنا بتحليلها و بتصميم نظام لمتابعة المشاريع لديهم كتبنا التقرير للنظام وعملنا رسم الفلوشارت له وصممنا الواجهات عن طريق فيقما الثلاث الاسابيع الاخيره هي اللي كانت مفيده وممتعه بالنسبه لنا من ناحية اوقات الحظور مايدققو ابدا حتى لو بتغيبي ترسلي للمشرفة عذرك وتحضرك والتقييم اعطونا كلنا كامل يعني اجتهادك حيكون لنفسك عشان تستفيدي في النهاية هم حيعطوك الدرجة كاملة',
                'pros' => 'الثلاث أسابيع الأخيرة كانت مفيدة، تحليل وتصميم نظام',
                'cons' => 'وقت فراغ كثير في البداية، عدم انتظام المهام',
            ],

            // Row 56 — شركة الاساليب الذكية — rating 4
            [
                'company_name' => 'شركة الأساليب الذكية',
                'city' => 'مكة - العابدية',
                'role_title' => 'متدربة روبوتات',
                'department' => null,
                'duration_months' => 2, // ٨-١٠ اسابيع
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => "تجربة ممتعه كطالبة علوم حاسب لانها تغطي و تشمل اكثر من تخصص، و بإمكان المتدرب المشاركة في جميع التخصصات.\nموضوع التدريب كان يتمحور حول الروبوتات و كيفية بناءها ميكانيكياً، اليكترونياً، هندسياً، و برمجياً.\nعيوب التدريب:\nلا يوجد شهادة للمتدربين في المسار المدفوع، فقط افادة حضور للاعتماد تدريبك من الجامعة",
                'pros' => 'تجربة ممتعة تغطي تخصصات متعددة',
                'cons' => 'لا توجد شهادة في المسار المدفوع',
            ],

            // Row 62 — شركة إيسار لتقنية المعلومات — rating 5
            [
                'company_name' => 'شركة إيسار لتقنية المعلومات',
                'city' => 'مكة المكرمة',
                'role_title' => 'مطورة ويب/تطبيقات',
                'department' => 'التطوير',
                'duration_months' => 7, // ٨ شهور — رقم كبير لكن من المصدر
                'sector' => 'private',
                'modality' => 'remote', // review explicitly: "كل الاسبوع عن بُعد"
                'mixed_env' => false,
                'job_offer' => false,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => "كنا ١٠ طالبات، تقسمنا لـ قسمين: قسم لتطوير لمجال الويب وقسم لمجال التطبيقات \" mobile \"، المشاريع اللي اشتغلت عليها كانت: تحليل نظام الشركة، رسم database، إنشاء تطبيق للشركة وربطه بالـ API\nمزايا التدريب وعيوبه:\nعيوب التدريب:\n- كل الاسبوع عن بُعد، ويوم واحد بس حضوري\n- بيئة العمل غير موفّرة للمتدربات\nالمزايا:\n- سرعة التواصل ومساعدتهم لنا\n- التاسكات الكثيرة اللي بيعطوها لنا واللي كانت جدًا مفيدة\n- تعلم شيء جديد",
                'pros' => 'سرعة التواصل، تاسكات مفيدة كثيرة، تعلم شيء جديد',
                'cons' => 'بيئة العمل غير موفرة للمتدربات، عن بُعد معظم الوقت',
            ],

            // Row 73 — مستشفى النور التخصصي — rating 4
            [
                'company_name' => 'مستشفى النور التخصصي',
                'city' => 'مكة',
                'role_title' => 'متدربة سجلات طبية',
                'department' => 'السجلات الطبية',
                'duration_months' => 2, // ٤٥ يوم
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => 'التدريب كان في قسم السجلات الطبية اشتغلت في كل الاقسام الموجودة في القسم ( الاستقبال، الجودة، الارشفة الالكترونية، كتبة جناح، الترميز الطبي)',
                'pros' => 'تعرض لكل الأقسام في السجلات الطبية',
            ],

            // Row 74 — حلول التقنية المتكاملة — rating 3
            [
                'company_name' => 'حلول التقنية المتكاملة',
                'city' => 'الزايدي',
                'role_title' => 'مبرمج أنظمة',
                'department' => null,
                'duration_months' => 2,
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => false,
                'job_offer' => false,
                'overall_rating' => 3,
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'review_text' => 'مبرمج أنظمة في شركة حلول التقنية المتكاملة لمدة شهرين، بيئة غير مختلطة.',
            ],

            // Row 75 — أمانة مكة (المدن الذكية) — rating 1
            [
                'company_name' => 'أمانة مكة',
                'city' => 'الكعكية، مكة',
                'role_title' => 'متدربة مدن ذكية',
                'department' => 'المدن الذكية',
                'duration_months' => 2, // ٨ اسابيع
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => false,
                'overall_rating' => 1,
                'rating_mentorship' => 1,
                'rating_learning' => 1,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'review_text' => 'تدربت في قسم المدن الذكية المكان مرتب لا يوجد اختلاط مباشر كنا بمكتب فقط متدربات ويجي المشرف احيانا لمناقشة المهام اغلب المهام كانت افكار ومبادرات طلبت منا ومهام ادراية غير متعلقة بقسم الحاسب',
                'cons' => 'أغلب المهام إدارية غير متعلقة بالحاسب',
            ],

            // Row 76 — سدايا (سحابة ديم) — rating 5, job_offer yes
            [
                'company_name' => 'سدايا',
                'city' => 'الرياض',
                'role_title' => 'متدربة أمن سحابي',
                'department' => 'سحابة ديم - الأمن السحابي',
                'duration_months' => 2,
                'sector' => 'government',
                'modality' => 'onsite',
                'mixed_env' => true,
                'job_offer' => true,
                'overall_rating' => 5,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'review_text' => "تدرّبت في سدايا تحديدًا سحابة ديم، و على وجه التحديد in cloud security tram .. كنت مجرّد trainee بالبدايه عطوني بريڤ واضح عن طبيعة العمل و المصطلحات و بعدها صرت اتابع مع مشاريعهم و مع الـ vendors بكل بروسس و بعدها صرت small manager على مشروع من مشاريع الإداره اللي أنا فيها من جميع نواحيها و أمسك تاسكات ثانيه\nبيئة عمل جدًا رهيبه، و جميع اللي بسحابة ديم كانوا أشخاص متعاونين و معطائيين لدرجه كبيره، حريصين على أنهم يوصّلون المعلومه بشكل أفضل و يتجاوبون معك كأنهم عائله واحده مو إدارات مختلفه، تجربه رهيبه حد انه بنظري مليانه مزايا و خاليه من العيوب",
                'pros' => 'بيئة عمل رهيبة، فريق متعاون كالعائلة، مسؤوليات حقيقية',
            ],

            // Row 79 — شركة حلول للتقنية — rating 4
            [
                'company_name' => 'شركة حلول للتقنية',
                'city' => 'الزايدي، مكة',
                'role_title' => 'متدربة هندسة برمجيات',
                'department' => 'هندسة البرمجيات',
                'duration_months' => 2,
                'sector' => 'private',
                'modality' => 'onsite',
                'mixed_env' => false,
                'job_offer' => false,
                'overall_rating' => 4,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'review_text' => "تقريبا هندسة برمجيات ولكن التاسكات م كانت مره مرتبه وفالبداية كان اغلبها ابحثي عن كذا وسوي برزنتيشن وهكذا -- الشركة مش مختلطة ولكن ف اجتماع نهاية كل اسبوع مع المهندس صاحب الشركة بيكون حضور وكذا ولازم برزنتيشن تقولي وش سويتي طول الاسبوع\nعبال م تخطينا مرحلة البحث وبدينا نشتغل بروتوتايب وفلاترو كذا كان مر وقت بدون انتاجيه كبيرة وف تشتت ف التاسكات -- مزايا التدريب الرجال والمهندس المشرف المباشر جدا محترم والشركة كلها تراعي حدود التعامل وان فلاتر كان شي جديد عليا ومتعاونين اذا اشتكيتي من شي تلقي يرسلولك حلول وكذا -- اشتغلت ع نقاط كثير ف البحث لكن وقت فلاتر الايرورز كانت تبعيقني ف المقابل زميلتي اختارت مسار التصميم ف انجازها كان اكبر ولكن برضو كان يطلبوا منها اشياء ثانيه جانبيه وتاسكات مشتته",
                'pros' => 'المهندس المشرف محترم، الشركة تراعي حدود التعامل، متعاونون',
                'cons' => 'تاسكات غير مرتبة وتشتت، مرحلة البحث طويلة',
            ],
        ];
    }
}
