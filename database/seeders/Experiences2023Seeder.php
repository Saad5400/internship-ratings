<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Rating;
use App\Support\Arabic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real student internship reviews from the 2023 training experiences survey.
 *
 * Source: "تجارب التدريب 2023 - ردود النموذج 1.csv" (27 raw responses).
 *
 * Filtering:
 *  - Remote-only responses (col 2 = "عن بعد (دورات)") with no company name are dropped.
 *  - Test / junk rows (company = "Test" or ".") are dropped.
 *  - Rows with a review shorter than 20 meaningful characters are dropped.
 *
 * Notes on ratings:
 *  - This form collected no numeric scores. Subscores and overall are calibrated
 *    from the free-text review tone and the col-11 recommendation field:
 *      نعم + positive  -> 4-5
 *      ربما / mixed    -> 3
 *      لا / negative   -> 1-2
 *  - `rating_compensation` is derived from col 13 (certificate-only = low, cash = high).
 *    No monetary amounts are stored as stipend_sar unless the source explicitly
 *    mentions an SAR figure (none in this file).
 *
 * Idempotent: existing ratings are detected by company_id + sha1(review_text) so
 * re-runs never insert duplicates.
 */
class Experiences2023Seeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Row 1 — امانة العاصمة (Makkah municipality) — recommendation: لا
            [
                'company' => 'امانة العاصمة',
                'sector' => 'government',
                'role_title' => 'متدرب دعم فني',
                'department' => 'إدارة تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'no',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة فقط
                'rating_mentorship' => 2,
                'rating_learning' => 1,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'review_text' => "بلاغات دعم فني\nقسم ادارة تقنية المعلومات\nما اشتغلنا على مشاريع\nوصراحه اعتبر التدريب لعب\nقسمونا مجموعتين ناس من 8ص لـ 11 وناس من 12 لـ 3م\nوالتدريب مايفيدني الا لو توظفت عندهم لأن بشتغل على نظام الأمانه\nمره متعاونين من ناحية الحضور ولا فيه مراقبه او توقيع",
                'pros' => 'مرونة في الحضور ولا يوجد توقيع أو مراقبة',
                'cons' => 'ما فيه مشاريع حقيقية والتدريب ما يفيد إلا لو توظفت عندهم',
            ],

            // Row 7 — مستشفى الملك عبد العزيز — recommendation: نعم
            [
                'company' => 'مستشفى الملك عبد العزيز',
                'sector' => 'government',
                'role_title' => 'متدرب دعم فني وصيانة',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة توصية
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 2,
                'overall_rating' => 4,
                'review_text' => 'المهام كانت تتقسم الى صيانة و دعم فني و كان فيه صيانة وقائية (جرد الاقسام) و تطبيق على اسلاك الشبكة',
                'pros' => 'تطبيق عملي على صيانة وشبكات',
                'cons' => null,
            ],

            // Row 8 — مستشفى النور التخصصي — recommendation: نعم
            [
                'company' => 'مستشفى النور التخصصي',
                'sector' => 'government',
                'role_title' => 'متدرب سجلات طبية',
                'department' => 'السجلات الطبية',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // لا يوجد
                'rating_mentorship' => 4,
                'rating_learning' => 3,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'review_text' => 'قسم السجلات الطبية [وحدة حفظ الملفات والتصنيف والأرشفة] مريح وممتع أكثر من استقبال القسم أو الرسبشن بالأدوار',
                'pros' => 'وحدة الأرشفة مريحة وممتعة',
                'cons' => 'استقبال القسم/الرسبشن متعب',
            ],

            // Row 9 — شرطة العاصمة المقدسة — recommendation: نعم
            [
                'company' => 'شرطة العاصمة المقدسة',
                'sector' => 'government',
                'role_title' => 'متدرب شبكات وصيانة',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // حفل تكريم وشهادة
                'rating_mentorship' => 3,
                'rating_learning' => 2,
                'rating_culture' => 4,
                'rating_compensation' => 2,
                'overall_rating' => 3,
                'review_text' => 'المهام: تصميم شبكة - صيانة الحاسب. المزايا: شغلهم ماهو كثير والدوام يوم اي ويوم لا فما راح تتعبون اللي ما يبغى الكرف يروح لهم. العيوب: الاستفادة ماهي كبيرة بنهاية التدريب لان النظام تعليمي كاننا بالجامعة فما حسيت مرة استفدت',
                'pros' => 'دوام مخفف (يوم نعم يوم لا) وشغل غير مرهق',
                'cons' => 'طابع تعليمي لا يختلف عن الجامعة والاستفادة محدودة',
            ],

            // Row 12 — مستشفى الملك عبدالعزيز (duplicate of row 7 spelling variant) — recommendation: لا
            [
                'company' => 'مستشفى الملك عبدالعزيز',
                'sector' => 'government',
                'role_title' => 'متدرب تقنية معلومات',
                'department' => 'الدعم الفني والشبكات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'no',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة توصية عند الطلب
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'review_text' => 'دور مختص تقنية المعلومات، يعلمونك عن الشبكات والأسلاك ودور الدعم الفني واستقبال الاتصالات في المستشفى. بشكل عام يفيدك تتعلمين الـ common troubleshooting وفيه محتوى لتقريرك، لكن لو توجهك تطوير برمجيات (مواقع/تطبيقات…إلخ) مافيه، القسم كله مهندسين/دعم فني',
                'pros' => 'تتعلم common troubleshooting ومحتوى كافٍ للتقرير',
                'cons' => 'لا يناسب من توجهه تطوير برمجيات',
            ],

            // Row 13 — مستشفى الولادة والاطفال — recommendation: نعم
            [
                'company' => 'مستشفى الولادة والأطفال',
                'sector' => 'government',
                'role_title' => 'متدرب صحة إلكترونية',
                'department' => 'الصحة الإلكترونية',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // لا
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 3,
                'review_text' => 'القسم فيه ٤ أقسام، تم العمل في كل قسم لمدة من أسبوع إلى ٢',
                'pros' => 'تنقل بين عدة أقسام لأخذ صورة شاملة',
                'cons' => null,
            ],

            // Row 15 — شركة مطوفي حجاج افريقيا — recommendation: نعم
            [
                'company' => 'شركة مطوفي حجاج افريقيا للدول غير العربية',
                'sector' => 'private',
                'role_title' => 'متدرب شبكات وسيرفرات',
                'department' => 'الشبكات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة خبرة فقط
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'review_text' => 'تدربت في قسم الشبكات على جزء السيرفرات، اتعلمت فيه عن السيرفرات من الصفر: كيفية إنشائها وتشغيلها وإنشاء سيرفرات افتراضية وتوزيع الإنترنت من السيرفر وعدة أشياء متعلقة بالسيرفرات والتحكم بها',
                'pros' => 'تعلم السيرفرات والافتراضية من الصفر',
                'cons' => null,
            ],

            // Row 16 — شركة إيسار — recommendation: نعم (highly positive)
            [
                'company' => 'شركة إيسار',
                'sector' => 'private',
                'role_title' => 'متدرب تطوير ويب',
                'department' => 'التطوير التقني',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // حفل تكريم وشهادة
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'review_text' => 'بالبداية تعلمنا عن الشركة وايش مهام الشركة وبعدين اخذنا كورسات في الدورة التقنية وفي الGitHub وسوينا مركز مساعدة لنفس الموقع ورفعناه بالـGitHub واخذنا دورات UI/UX وتحليل الأنظمة وصراحة مره استفدنا من خبرات المهندسين واعطونا دافع قوي وبرضو تعلمنا كيف نسوي موقع ويب من الصفر عن طريق حجز دومين وإلى آخره وسوينا سيرة مهنية عن طريق WordPress. العيب الصراحة انه الوقت كان جدًا قصييير وماكفانا اننا نتعلم أشياء أكثر، وكانت عدد شهور التدريب شهر و٣ أسابيع تقريبًا',
                'pros' => 'دورات UI/UX وتحليل أنظمة وتطوير ويب، مهندسون داعمون',
                'cons' => 'مدة التدريب قصيرة جدًا',
            ],

            // Row 17 — وزارة العدل — recommendation: لا
            [
                'company' => 'وزارة العدل',
                'sector' => 'government',
                'role_title' => 'متدرب دعم فني',
                'department' => 'الدعم الفني',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'no',
                'mixed_env' => true,
                'stipend_sar' => null, // لا
                'rating_mentorship' => 3,
                'rating_learning' => 2,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'review_text' => 'نساعد في قسم الدعم الفني، كل مرة تكون عندهم مشكلة نحلها. العيوب: اللي يبغى يتطور ويستفيد فعلًا ما أنصح، لأن صلاحياتك كمتدرب محدودة ولا عندهم استعداد فعلي ومجهزين لاستقبال متدربين. المزايا: المدربين متعاونين وطبيعة العمل بسيطة',
                'pros' => 'مدربون متعاونون وطبيعة عمل بسيطة',
                'cons' => 'صلاحيات المتدرب محدودة وغير مجهزين لاستقبال المتدربين',
            ],

            // Row 19 — جمعية عون التقنية (وادي مكة) — recommendation: نعم (highly positive)
            [
                'company' => 'جمعية عون التقنية',
                'sector' => 'nonprofit',
                'role_title' => 'متدرب تحليل وتصميم أنظمة',
                'department' => 'تطوير المشاريع',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // حفل ختامي وشهادة
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'review_text' => "أول أربعة أيام أخذنا دورات في UI/UX و كتابة SRS، بعد كذا عرضوا علينا مشاريع حقيقية نختار منها واحد، وبعدها كوّنا تيم من خمسة أشخاص اشتغلنا على المشروع من ناحية تصميم الواجهات عشان نقدمها للعميل وقت الاجتماع، وبعد ما خلصنا من التصميم بدأنا في كتابة SRS لنفس المشروع. طبعًا كان في 2 برزنتيشن: واحد بعد ما خلصنا التصاميم والثاني بعد SRS، وآخر أسبوع كل شخص سوى تيست لموقع الجمعية في ملف إكسل. آخر يوم في التدريب كان الحفل الختامي، كل قروب عرض مشروعه اللي اشتغل عليه وتم تكريمنا وإعطاؤنا شهادات بالساعات اللي اتدربنا فيها.\nكان التدريب مره حلو وممتع، عن نفسي اكتسبت خبرة من بعض المتدربين اللي كانوا معانا، وطبعًا المشرفة كانت مره عسل ومتعاونة معانا م. رهف الثبيتي ومعاها المساعدة م. بشاير الشريف.",
                'pros' => 'مشاريع حقيقية وتدريب عملي على UI/UX وSRS وتيست، مشرفات متعاونات',
                'cons' => null,
                'had_supervisor' => true,
            ],

            // Row 21 — مستشفى قوى الأمن بمكة — recommendation: نعم
            [
                'company' => 'مستشفى قوى الأمن بمكة',
                'sector' => 'government',
                'role_title' => 'متدرب تطوير أنظمة',
                'department' => 'ICT',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // توجد شهادة
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'review_text' => "التدريب كان في قسم ICT (information and communications technology).\nخطة التدريب كانت مقسمة بين مهام يتم تنفيذها على موقع المستشفى والجزء الآخر مشروع نظام بفكرة محددة ومتطلبات يستخدم النظام في المستشفى، يحتاج من المتدرب معرفة نظام Joomla ولغة PHP وإطار العمل CodeIgniter وHTML, CSS, JavaScript.\nفي عام 2023 كان المطلوب Joomla4, CodeIgniter4.\nالتدريب يحاكي عمل الموظف الطبيعي في القسم ويساعد على التعرف على بيئة العمل في المستشفى لطلاب علوم الحاسب",
                'pros' => 'مشاريع حقيقية بتقنيات PHP / Joomla / CodeIgniter تحاكي عمل الموظف',
                'cons' => null,
            ],

            // Row 22 — عون التقنية (معهد البحوث) — recommendation: نعم
            [
                'company' => 'عون التقنية',
                'sector' => 'nonprofit',
                'role_title' => 'متدرب تصميم واجهات',
                'department' => 'تحليل الأنظمة',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'overall_rating' => 5,
                'review_text' => 'جدًا استمتعت واستفدت في التدريب، وكان عبارة عن تصميم واجهات وكتابة SRS file وtesting',
                'pros' => 'عمل مباشر على تصميم الواجهات وSRS والاختبار',
                'cons' => null,
            ],

            // Row 23 — أمانة العاصمة المقدسة (وكالة الحلول الرقمية) — recommendation: لا
            [
                'company' => 'أمانة العاصمة المقدسة - وكالة الحلول الرقمية',
                'sector' => 'government',
                'role_title' => 'متدرب تطوير برمجيات',
                'department' => 'وكالة الحلول الرقمية',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'no',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة فقط
                'rating_mentorship' => 2,
                'rating_learning' => 2,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'review_text' => 'تدربنا في أقسام مختلفة مثل برمجة الواجهات وبرمجة تطبيقات وبرمجة باك اند وهندسة برمجيات، لكن المعرفة اللي اضيفت لنا بسيطة جدًا، ما اشتغلنا عمليًا على مشاريع، كانت كلها حاجات بسيطة لمبتدئين وسبق تعلمها في الجامعة بشكل أعمق. شرحوا بشكل نظري لغة PHP وأكواد مشاريع حقيقية',
                'pros' => 'تنقل بين عدة أقسام (فرونت/باك/هندسة برمجيات)',
                'cons' => 'محتوى نظري مبتدئ وبدون مشاريع عملية',
            ],

            // Row 25 — المدينة الأولى (وادي مكة) — recommendation: نعم
            [
                'company' => 'المدينة الأولى',
                'sector' => 'private',
                'role_title' => 'متدرب تطوير تطبيقات',
                'department' => 'تطوير التطبيقات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة وحفل تكريم
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'review_text' => 'قمنا بعمل مشروع تطبيق جوال متكامل، من مزاياه بيئة العمل متعاونة جدًا',
                'pros' => 'بيئة عمل متعاونة ومشروع تطبيق جوال متكامل',
                'cons' => null,
            ],

            // Row 26 — جامعة أم القرى — recommendation: نعم
            [
                'company' => 'جامعة أم القرى العمادة التقنية',
                'sector' => 'government',
                'role_title' => 'متدرب تقنية معلومات',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // لا يوجد
                'rating_mentorship' => 4,
                'rating_learning' => 3,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'review_text' => 'تم إعطائي جدول للمرور على العديد من أقسام IT والتعرف على بيئة وطبيعة العمل',
                'pros' => 'جولة شاملة على أقسام IT المختلفة',
                'cons' => null,
            ],

            // Row 27 — الهيئة السعودية للبيانات والذكاء الاصطناعي (سدايا) — recommendation: نعم
            [
                'company' => 'الهيئة السعودية للبيانات والذكاء الاصطناعي',
                'sector' => 'government',
                'role_title' => 'متدرب حوسبة سحابية',
                'department' => 'دمج الحوسبة السحابية',
                'city' => 'الرياض',
                'modality' => 'onsite',
                'recommendation' => 'yes',
                'mixed_env' => true,
                'stipend_sar' => null, // شهادة تدريب تعاوني
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'overall_rating' => 5,
                'review_text' => 'تدربت في قسم الحوسبة السحابية تحت قسم دمج الحوسبة السحابية. العمل في هذا القسم يتطلب نقل مركز البيانات المادي إلى السحابة بجميع السيرفرات والبيانات الموجودة عليها، أيضًا اجتماعات مع العملاء لفهم طلباتهم وبيئتهم وشرح السحابة لهم وكيف ستكون حل أفضل لهم',
                'pros' => 'مشروع حقيقي لترحيل مركز بيانات للسحابة واجتماعات مع العملاء',
                'cons' => null,
            ],
        ];

        $inserted = 0;
        $skipped = 0;
        $newCompanies = 0;
        $reusedCompanies = 0;

        DB::transaction(function () use ($rows, &$inserted, &$skipped, &$newCompanies, &$reusedCompanies) {
            foreach ($rows as $row) {
                $companyName = trim((string) ($row['company'] ?? ''));
                $reviewText = trim((string) ($row['review_text'] ?? ''));

                if ($companyName === '' || mb_strlen($reviewText) < 20) {
                    $skipped++;

                    continue;
                }

                $normalized = Arabic::normalize($companyName);

                $company = Company::firstOrCreate(
                    ['name_normalized' => $normalized],
                    [
                        'name' => $companyName,
                        'status' => 'approved',
                    ]
                );

                if ($company->wasRecentlyCreated) {
                    $newCompanies++;
                } else {
                    $reusedCompanies++;
                }

                // Fingerprint by company + first 120 chars of review (portable across SQLite/MySQL).
                $needle = mb_substr($reviewText, 0, 120);

                $exists = Rating::where('company_id', $company->id)
                    ->where('review_text', 'like', $needle.'%')
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Rating::create([
                    'company_id' => $company->id,
                    'role_title' => $row['role_title'] ?? 'متدرب تعاوني',
                    'department' => $row['department'] ?? null,
                    'city' => $row['city'] ?? null,
                    'duration_months' => $row['duration_months'] ?? 2,
                    'sector' => $row['sector'] ?? null,
                    'modality' => $row['modality'],
                    'stipend_sar' => $row['stipend_sar'] ?? null,
                    'had_supervisor' => $row['had_supervisor'] ?? null,
                    'mixed_env' => $row['mixed_env'] ?? null,
                    'job_offer' => $row['job_offer'] ?? null,
                    'rating_mentorship' => $row['rating_mentorship'],
                    'rating_learning' => $row['rating_learning'],
                    'rating_culture' => $row['rating_culture'],
                    'rating_compensation' => $row['rating_compensation'],
                    'overall_rating' => $row['overall_rating'],
                    'recommendation' => $row['recommendation'],
                    'review_text' => $reviewText,
                    'pros' => $row['pros'] ?? null,
                    'cons' => $row['cons'] ?? null,
                    'reviewer_name' => null,
                    'reviewer_major' => null,
                ]);

                $inserted++;
            }
        });

        $this->command?->info(sprintf(
            'Experiences2023Seeder: %d inserted, %d skipped, %d new companies, %d reused companies (of %d rows).',
            $inserted,
            $skipped,
            $newCompanies,
            $reusedCompanies,
            count($rows)
        ));
    }
}
