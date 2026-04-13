<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Rating;
use App\Support\Arabic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real training-place reviews from the batch-1445/1444 Google Form
 * (submitted February – April 2026).
 *
 * Column mapping (form → DB field):
 *   اسم الجهة التدريبية        → company_name
 *   مكان التدريب               → city
 *   ما مدى استفادتك (1-10)    → overall_rating  scaled via ceil(n/2) → 1-5
 *   ما أبرز إيجابيات التدريب  → pros  (semicolon-separated list kept as-is)
 *   هل الجهة تقدم مكافأة؟     → stipend_sar (null — amount never disclosed)
 *   تكلم عن تجربتك            → review_text  (primary)
 *   نصيحة تقدمها              → review_text  (fallback when تجربة is empty)
 *   تخصصك الجامعي             → reviewer_major
 *   مجال التدريب داخل الجهة   → department / role_title
 *
 * Cleaning rules applied:
 *   - Row 1  (company = ".")  — junk, skipped.
 *   - Row 26 (company = "——-") — junk, skipped.
 *   - Rows 6, 11, 14, 19 — both تجربة and نصيحة are empty/too short, skipped.
 *   - rating  subscores calibrated from the pros list in the same ballpark as
 *     overall_rating.
 *   - modality defaults to 'onsite' (all respondents supplied a physical city).
 *   - duration_months defaults to 4 (standard co-op semester).
 *   - sector: government ministries/public bodies → 'government', otherwise
 *     'private'.
 *
 * Idempotent: checks company_id + first 120 chars of review_text before
 * inserting. Re-runs are safe.
 *
 *     php artisan db:seed --class=Batch45_2026Seeder
 */
class Batch45_2026Seeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Row 3 — النيابة العامة (10/10, مكة, web dev + data analysis)
            [
                'company_name'     => 'النيابة العامة',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => 'تطوير الويب وتحليل البيانات',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 1,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => 'لا تفقد الامل لف و دور الى ان تلقى جهة تدربك',
                'pros'             => 'إضافة قوية للسيرة الذاتية، التعامل مع فريق متعاون، فرصة تعلّم تقنيات جديدة',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 4 — Lucidya (7/10, الرياض, AI / data)
            [
                'company_name'     => 'Lucidya',
                'sector'           => 'private',
                'city'             => 'الرياض',
                'role_title'       => 'متدرب تعاوني',
                'department'       => 'الذكاء الاصطناعي',
                'modality'         => 'onsite',
                'mixed_env'        => null,
                'rating_mentorship'  => 3,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'أبدأوا في البحث عن الجهات بوقت مبكر, وحاولوا تتواصلون مع الموارد البشرية على ايميلهم أو لنكدإن',
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون',
                'cons'             => null,
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 5 — أمانة جدة (3/10, جدة, web dev) — 1444 cohort
            [
                'company_name'     => 'أمانة جدة',
                'sector'           => 'government',
                'city'             => 'جدة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => 'تطوير الويب',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 2,
                'rating_learning'    => 2,
                'rating_culture'     => 2,
                'rating_compensation' => 1,
                'overall_rating'   => 2,
                'recommendation'   => 'maybe',
                'review_text'      => 'ادخلو قروبات الفرص وتابعوها اول بأول',
                'pros'             => 'إشراف وتوجيه جيد، التعامل مع فريق متعاون',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 7 — Knowledgex (5/10, مكة, front-end) — 1444 cohort
            [
                'company_name'     => 'Knowledgex',
                'sector'           => 'private',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'مطور/ة Front-end',
                'department'       => 'تطوير الواجهات',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 2,
                'rating_learning'    => 3,
                'rating_culture'     => 3,
                'rating_compensation' => 1,
                'overall_rating'   => 3,
                'recommendation'   => 'maybe',
                'review_text'      => 'التدريب يعتمد على التعلم الذاتي بشكل كبير جدا',
                'pros'             => 'مهام عملية ومفيدة، فرصة تعلّم تقنيات جديدة',
                'cons'             => 'يعتمد على التعلم الذاتي بشكل كبير، غياب التوجيه',
                'reviewer_major'   => 'تفاعل الإنسان مع الحاسب',
            ],

            // Row 8 — ركايا للإستشارات الإدارية (7/10, مكة, DevOps)
            [
                'company_name'     => 'ركايا للإستشارات الإدارية',
                'sector'           => 'private',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'مهندس/ة DevOps',
                'department'       => 'DevOps',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 4,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'رح للقطاع الخاص، الحكومي اغلبه تساهل وماراح تستفيد إلا مارحم ربي',
                'pros'             => 'مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية، بيئة عمل محترفة',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 9 — المركز الوطني للأرصاد (8/10, جدة, AI) — 1444 cohort
            [
                'company_name'     => 'المركز الوطني للأرصاد',
                'sector'           => 'government',
                'city'             => 'جدة',
                'role_title'       => 'متدرب ذكاء اصطناعي',
                'department'       => 'الذكاء الاصطناعي',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 4,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'اعطونا 3 مشاريع تقريبا كل مشروع نشتغل عليه لفترة معينة وكل نسوي برزنتيشن نوريهم الشغل ويعطونا فيد باك عليه',
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'الذكاء الاصطناعي',
            ],

            // Row 10 — شركة عِلم (10/10, جدة, project management / Nusuk)
            [
                'company_name'     => 'شركة عِلم',
                'sector'           => 'private',
                'city'             => 'جدة',
                'role_title'       => 'متدرب إدارة مشاريع',
                'department'       => 'ادارة المشاريع التقنية',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 3,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => "تجربة جدا جميلة ومثرية في شركة مثل شركة عِلم، البيئة متعاونه،\nدخلت بمسمى متدرب، الشركة كانت ماسكه مركز معلومات الحج والعمرة بالتعاون مع وزارة الحج فكانت الشركة ماسكه تطبيق نسك والموقع حق نسك وكل شي يخص نسك، في البداية علموني تقسم الادارة والمشروع بما ان عملي كان في ادارة المشروع وكانوا مهتمين في المقابلة بخصوص ادارة المشاريع التقنية",
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 12 — iSolution اختراع الحلول للتقنية (7/10, جدة, API)
            [
                'company_name'     => 'iSolution اختراع الحلول للتقنية',
                'sector'           => 'private',
                'city'             => 'جدة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => 'تطوير API',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 3,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'المهام اننا نسوي سياسات في ال API مافي شي تاني الى الان',
                'pros'             => 'مهام عملية ومفيدة، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => 'المهام تقتصر على سياسات API فقط حتى الآن',
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 13 — Site (10/10, الرياض, cloud operations)
            [
                'company_name'     => 'Site',
                'sector'           => 'private',
                'city'             => 'الرياض',
                'role_title'       => 'متدرب Cloud Operations',
                'department'       => 'Cloud Operations',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 3,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => 'ممتازه حتى الأن؛ قدم من بدري',
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 15 — وزارة الحج والعمرة (7/10, مكة, data viz / dashboard)
            [
                'company_name'     => 'وزارة الحج والعمرة',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'محلل/ة بيانات',
                'department'       => 'تحليل بيانات الحج والعمرة',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 2,
                'rating_learning'    => 4,
                'rating_culture'     => 3,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'maybe',
                'review_text'      => "طبيعه المهام بيانات عن الحج والعمره واسوي لها تصوير بياني واعرض مقارنات بين الأعوام الماضيه.\nمن العيوب المشرف الاكاديمي لازم اروح اكلمه عشين يعطيني مهام ومرات مايحضر ولا يبلغني. والعيب الثاني مستحيل يوجهني او يدلني على الشيء صحيح يعني فقط إذا ماعجبه شيء يقول عدل دون توضيح. العيب الثالث انه مايرد على الرسايل. وطبعا هذا كله على حسب مشرفك الميداني",
                'pros'             => 'مهام عملية ومفيدة',
                'cons'             => 'المشرف الأكاديمي لا يرشد ولا يرد على الرسائل',
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 16 — ادارة التعليم (8/10, مكة, administration)
            [
                'company_name'     => 'إدارة التعليم',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => 'الإدارة',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 3,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'تدوين المهام يومياً لذكرها فالتقرير الشهري',
                'pros'             => 'مهام عملية ومفيدة، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 17 — مستشفى الليث العام (7/10, الليث, data analysis) — 1444 cohort
            [
                'company_name'     => 'مستشفى الليث العام',
                'sector'           => 'government',
                'city'             => 'الليث',
                'role_title'       => 'محلل/ة بيانات',
                'department'       => 'تحليل البيانات',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 4,
                'rating_learning'    => 3,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'أغلبه كان اكسل شيت وتشارتس بس البيئة بصراحة فرقت معايا متعاونة ولطيفين',
                'pros'             => 'بيئة عمل محترفة، التعامل مع فريق متعاون، إشراف وتوجيه جيد',
                'cons'             => 'المهام تقتصر على Excel وCharts',
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 18 — المركز الوطني للأرصاد (9/10, جدة, data processing) — second reviewer
            [
                'company_name'     => 'المركز الوطني للأرصاد',
                'sector'           => 'government',
                'city'             => 'جدة',
                'role_title'       => 'متدرب معالجة بيانات',
                'department'       => 'معالجة البيانات',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 1,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => "التدريب مقسم على عدة مراحل, إما تكون في قسم واحد أو في عدة أقسام حسب اتفاقك مع مسؤول التدريب\n\nالخطة بتكون مقسمة على 3-4 مشاريع مرتبطة بالمركز, بتشتغل عليها من الصفر وهم يتابعون معاك بشكل دوري",
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 20 — وزارة الحج والعمرة (7/10, مكة, data analysis) — data science student
            [
                'company_name'     => 'وزارة الحج والعمرة',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'محلل/ة بيانات',
                'department'       => 'تحليل البيانات',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 4,
                'rating_learning'    => 4,
                'rating_culture'     => 3,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'maybe',
                'review_text'      => 'الى الآن المهام اللي اخذها نفس اللي درسته بس ب بيانات اكبر ومعقدة اكثر ولازم تكون مفهومة ، الشغل مو معقد حلو ومفيد واتعلمت على اشياء الجامعة تسحب عليها',
                'pros'             => 'مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة',
                'cons'             => 'كثرة أوقات الفراغ',
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 21 — هيئة العناية بشؤون الحرمين (7/10, مكة, general)
            [
                'company_name'     => 'هيئة العناية بشؤون الحرمين',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => null,
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 4,
                'rating_learning'    => 3,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'كل شي تمام مهمات بسيطة وعمل مريح متعاونين جدا معانا مافي مكاتب مخصصة لنا بس نقعد بغرفة الاجتماعات اغلب الوقت',
                'pros'             => 'إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => 'لا مكاتب مخصصة للمتدربين',
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 22 — وزارة الحج والعمرة (10/10, مكة, Power BI / data analysis)
            [
                'company_name'     => 'وزارة الحج والعمرة',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'محلل/ة بيانات',
                'department'       => 'تحليل البيانات',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 1,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => 'المهام نسبة كبيرة منها Power BI ، وتحليلات ، العيوب فيه بعض المهام ماتكون مجهزه لها ملف اكسل فااجمع بيانات واسوي ملف اكسل وبعدها احلل ب Power BI',
                'pros'             => 'مهام عملية ومفيدة، بيئة عمل محترفة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => 'بعض المهام بدون بيانات جاهزة — يلزم تجميعها يدوياً أولاً',
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 23 — تكوين (10/10, مكة, front-end) — detailed review
            [
                'company_name'     => 'تكوين',
                'sector'           => 'private',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'مطور/ة Front-end',
                'department'       => 'تطوير الواجهات الأمامية',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 2,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => "حاليًا بتم شهري الثاني في تكوين للامانه بيئة عمل متعاونه جدًا ورائعة قدرت أتعلم اشياء كثيره في غصون شهر تحسنت عندي مهارات كثيره تقنية وغير تقنية.\nالنظام في تكوين يعتمد على التعليم ذاتي وطبعًا عندكم مشرف يشرف عليكم ويعطيكم سشنات وتاسكات،\nبتكلم عن الفرونت، عندنا مشرف كل أسبوع يجي يعطينا سشن وبعدها تاسك نطبق الي تعلمناه وبعد ماخلصنا تاسكين شاف اكوادنا واعطانا ملاحظات نحسن على شغلنا.\nذا غير اننا كل يوم عندنا ستاند اب صباحي ومسائي، الصباحي نتكلم عن ايش بنشتغل عليه لليوم والمسائي نقول ايش انجزنا ايش المشاكل الي واجهتنا، الموضوع اشبه بجلسة سوالف، وبرضو كل نهاية أسبوع نعرض ايش اشتغلنا عليه خلال الأسبوع.\nجدًا مبسوطة بأختياري لأني جالسه اتعلم كل يوم شيء جديد وجالسه احس نفسي قريبه من سوق العمل فعلاً، القاب الكبير الي كان بين دراستي وبين سوق العمل بديت احسه يتلاشى.\nجدًا مهتمين بتعليمنا وتطويرنا",
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 24 — وزارة الحج و العمرة (7/10, مكة, Excel/Power BI) — data science
            [
                'company_name'     => 'وزارة الحج والعمرة',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'محلل/ة بيانات',
                'department'       => 'تحليل البيانات',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 3,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'تجربة جديدة و جميلة لكن شغلنا كله يقتصر حول الاكسل و POWER BI فقط وجمع و تنظيف البيانات، لاحظت ان اغلب الجهات الحكومية الكبيرة لازات نوعا ما متأخرة من ناحية الحلول التقنية او استخدام التقنيات بشكل عام',
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، التعامل مع فريق متعاون',
                'cons'             => 'العمل يقتصر على Excel وPower BI، تأخر في تبني الحلول التقنية الحديثة',
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 25 — وادي مكة (10/10, مكة, general CS)
            [
                'company_name'     => 'وادي مكة',
                'sector'           => 'private',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => null,
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 1,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => 'تجربة ممتازه وانصح فيها',
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 27 — هوليداي إن مكة (10/10, مكة, IT)
            [
                'company_name'     => 'هوليداي إن مكة',
                'sector'           => 'private',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'متخصص/ة IT',
                'department'       => 'تقنية المعلومات',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 5,
                'rating_learning'    => 5,
                'rating_culture'     => 5,
                'rating_compensation' => 2,
                'overall_rating'   => 5,
                'recommendation'   => 'yes',
                'review_text'      => "عمل جماعي ممتاز وتعامل الانظمه والبرامج المستخدمه و طريقة عملها و المشاكل التي تظهر و حلها.\nاما طبيعه المهام فهي برمجه أجهزه جديده للعمل على النظام، برمجّه أقفال الأبواب، التأكد من عمل السوتشات بطريقة صحيحه و تتبع المشاكل الصادرة من الاجهزه و حلها في اسرع وقت و ادق حل.\nاما الإجابيات الاعتياد على العمل تحت الظغط و تحمل مسؤولية حدوث اي مشكله من قبلك",
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، فرصة تعلّم تقنيات جديدة، التعامل مع فريق متعاون، إضافة قوية للسيرة الذاتية',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 28 — مستشفى حراء (4/10, مكة, IT support) — negative review
            [
                'company_name'     => 'مستشفى حراء',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'دعم فني',
                'department'       => 'الدعم الفني',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 2,
                'rating_learning'    => 2,
                'rating_culture'     => 2,
                'rating_compensation' => 1,
                'overall_rating'   => 2,
                'recommendation'   => 'no',
                'review_text'      => 'تجربة سيئة للامانة بسبب انه شغل وكرف ولكن تحس بدون فايدة بسبب انه دعم فني ، تعرّف طابعات ، تشبك كمبيوترات للممرضين والدكاترة ، تفصل سلك وترجعه للممرضين والدكاترة. في شفت كم طالب يتدرب امن سيبراني عند مشرف اسمه رياض ، هذا ممكن كويس لانه امن سيبراني ما تنزل المستشفى كثير',
                'pros'             => 'مهام عملية ومفيدة',
                'cons'             => 'المهام بسيطة جداً ولا تناسب تخصص البرمجة أو الأمن السيبراني',
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 29 — وزارة الحج والعمرة (3/10, مكة, data analysis) — negative review
            [
                'company_name'     => 'وزارة الحج والعمرة',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'محلل/ة بيانات',
                'department'       => 'تحليل البيانات وذكاء الأعمال',
                'modality'         => 'onsite',
                'mixed_env'        => false,
                'rating_mentorship'  => 1,
                'rating_learning'    => 2,
                'rating_culture'     => 2,
                'rating_compensation' => 1,
                'overall_rating'   => 2,
                'recommendation'   => 'no',
                'review_text'      => "ما كانت مفيدة. إذا ما طلبت شغل بنفسك ماراح يعطونك لو تقعد فاضي طول مدة التدريب.\n\nالمهام اللي أعطوني إياها كانت بسيطة مرة، مو بمستواي أو مستوى طالب متدرب يبغى يستفيد من التجربة. بحكم إن شغلي تحليل بيانات كان المفترض البيانات عن المواسم وفيها كل تفاصيل الموسم عشان أقدر أحللها وأشتغل عليها، لكن كانت بيانات صغيرة جدًا ومن البيانات المنشوره في البوابة حقت الوزارة.\n\nالبيئة جميلة، لكن الإستفادة قليلة ولا تكاد تذكر حقيقةً",
                'pros'             => 'إضافة قوية للسيرة الذاتية',
                'cons'             => 'مهام بسيطة جداً، بيانات محدودة ومنشورة، غياب التوجيه الفعلي',
                'reviewer_major'   => 'علم البيانات',
            ],

            // Row 30 — الكلية التقنية (7/10, جدة, IT dept) — 1444 cohort
            [
                'company_name'     => 'الكلية التقنية',
                'sector'           => 'government',
                'city'             => 'جدة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => 'تقنية المعلومات',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 4,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'التدريب في قسم الIT بالكليه ، تجي طلبات صيانة معامل .. حل مشاكل.. تثبيت ايميج وإدخال الاجهزه للدومين ، وغيرها كثير ..طبعا غير تعاون المهندسين ويحاولون يعطونكم تجربه ممتازه ومفيده، وفيه أشياء شفهيه ونظريه مثل السيرفرات والكاميرات والى اخره يعني بتاخذ نظره شامله ومفيده. السلبيه الوحيده مكان الكليه بعيد جدا عن احياء شمال ووسط جدة',
                'pros'             => 'بيئة عمل محترفة، مهام عملية ومفيدة، إشراف وتوجيه جيد، التعامل مع فريق متعاون',
                'cons'             => 'بُعد موقع الكلية عن شمال ووسط جدة',
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 31 — مستشفى الملك عبد العزيز (5/10, مكة, IT support)
            [
                'company_name'     => 'مستشفى الملك عبد العزيز',
                'sector'           => 'government',
                'city'             => 'مكة المكرمة',
                'role_title'       => 'دعم فني',
                'department'       => 'الدعم الفني',
                'modality'         => 'onsite',
                'mixed_env'        => true,
                'rating_mentorship'  => 3,
                'rating_learning'    => 3,
                'rating_culture'     => 3,
                'rating_compensation' => 1,
                'overall_rating'   => 3,
                'recommendation'   => 'maybe',
                'review_text'      => 'حاول ان تتعلم بي اكبر شكل ممكن و اسئل عن اي شيء لاتعرفه للمشرف',
                'pros'             => 'مهام عملية ومفيدة، إشراف وتوجيه جيد، التعامل مع فريق متعاون، فرصة تعلّم تقنيات جديدة، بيئة عمل محترفة',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],

            // Row 32 — أمانة جدة (7/10, جدة, general — mostly online)
            [
                'company_name'     => 'أمانة جدة',
                'sector'           => 'government',
                'city'             => 'جدة',
                'role_title'       => 'متدرب تعاوني',
                'department'       => null,
                'modality'         => 'hybrid',
                'mixed_env'        => true,
                'rating_mentorship'  => 3,
                'rating_learning'    => 4,
                'rating_culture'     => 4,
                'rating_compensation' => 1,
                'overall_rating'   => 4,
                'recommendation'   => 'yes',
                'review_text'      => 'التدريب كويس تقدر تقول اغلب الايام اونلاين ايام الدوام الحضوري قليلة يعطون مهام تدريبية ووورش العمل جدا مفيدة',
                'pros'             => 'بيئة عمل محترفة',
                'cons'             => null,
                'reviewer_major'   => 'علوم الحاسب الآلي',
            ],
        ];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $company = Company::firstOrCreate(
                    ['name_normalized' => Arabic::normalize($row['company_name'])],
                    ['name' => $row['company_name'], 'status' => 'approved']
                );

                // Idempotency: skip if a rating already exists for this
                // company whose review_text starts with the same 120 chars.
                $prefix = mb_substr($row['review_text'], 0, 120);

                $exists = Rating::where('company_id', $company->id)
                    ->where('review_text', 'like', $prefix.'%')
                    ->exists();

                if ($exists) {
                    continue;
                }

                Rating::create([
                    'company_id'          => $company->id,
                    'role_title'          => $row['role_title'],
                    'department'          => $row['department'],
                    'city'                => $row['city'],
                    'duration_months'     => 4,
                    'sector'              => $row['sector'],
                    'modality'            => $row['modality'],
                    'stipend_sar'         => null,
                    'had_supervisor'      => null,
                    'mixed_env'           => $row['mixed_env'],
                    'job_offer'           => null,
                    'rating_mentorship'   => $row['rating_mentorship'],
                    'rating_learning'     => $row['rating_learning'],
                    'rating_culture'      => $row['rating_culture'],
                    'rating_compensation' => $row['rating_compensation'],
                    'overall_rating'      => $row['overall_rating'],
                    'recommendation'      => $row['recommendation'],
                    'review_text'         => $row['review_text'],
                    'pros'                => $row['pros'],
                    'cons'                => $row['cons'],
                    'reviewer_name'       => null,
                    'reviewer_major'      => $row['reviewer_major'],
                ]);
            }
        });
    }
}
