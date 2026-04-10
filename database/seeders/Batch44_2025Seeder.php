<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Rating;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real training-place reviews from the
 * "استبيان جهات التدريب لدفعه ٤٤" Google Form (summer 2025).
 *
 * Source file:
 *   استبيان جهات التدريب لدفعه ٤٤  (Responses) - Form responses 1.csv
 *
 * Rules applied during cleaning:
 *  - First data row (blank) skipped.
 *  - Rows whose review_text is empty, "." or "—" or < 20 real characters
 *    are skipped as junk.
 *  - Test / garbage rows (company = "." / "—" / single-letter junk) skipped.
 *  - modality defaults to 'onsite' because col 5 (المنطقه والحي) always
 *    supplies a physical location.
 *  - duration_months defaults to 2 (standard summer training); no column
 *    exists in the source.
 *  - stipend_sar is ALWAYS null for this file (col 4 is boolean-only —
 *    "هل الجهة تقدم مكافأة؟" — the amount is never disclosed).
 *  - sector: حكومي → government, قطاع خاص → private, else → null.
 *  - recommendation: نعم → yes, لا → no, ربما → maybe.
 *  - overall_rating taken verbatim from col 10 (1–5 numeric).
 *  - Subscores calibrated from review tone in the same ballpark as overall.
 *
 * Idempotent: before inserting, checks company_id + first 120 chars of
 * review_text against existing ratings. Re-runs are safe.
 *
 * Run:
 *   php artisan db:seed --class=Batch44_2025Seeder
 */
class Batch44_2025Seeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Row 2 — جمعية عون التقنيه (Flutter dev, 5/5)
            [
                'company_name' => 'جمعية عون التقنية',
                'sector' => 'private',
                'city' => 'مكة المكرمة',
                'role_title' => 'مهندس/ة برمجيات',
                'department' => 'تطوير تطبيقات الجوال',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'recommendation' => 'yes',
                'review_text' => 'هندسه برمجيات،بدانا من التخطيط والتصميم الى برمجة لغة flutter,اغلب المهام تطبيقات بسيطه وممتعه',
                'pros' => 'مسار واضح من التخطيط والتصميم إلى البرمجة بـ Flutter، مهام ممتعة',
                'cons' => null,
                'reviewer_major' => 'علوم حاسب',
            ],

            // Row 4 — جمعية عون التقنية (Student skill development, 5/5)
            [
                'company_name' => 'جمعية عون التقنية',
                'sector' => 'private',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'recommendation' => 'yes',
                'review_text' => 'التدريب لم يكن مدرج تحت مسمى وظيفي فعلي ولكن يستهدف الطلبة في تطوير مهاراتهم',
                'pros' => 'يستهدف تطوير مهارات الطلبة',
                'cons' => 'لا يوجد مسمى وظيفي فعلي',
                'reviewer_major' => 'علوم حاسب',
            ],

            // Row 6 — TeckWin تكوين (Back-end Laravel, 5/5)
            [
                'company_name' => 'تكوين TechWin',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'مطور/ة Back-end',
                'department' => 'تطوير الويب',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'recommendation' => 'yes',
                'review_text' => "Back end developer/ full stack\n\nتم تكليفي بتعلم لارافل فرييم وررك ومسك مشروع مع مجموعه من بداية فترة التدريب ، اول خطوة كتابة الSRS وانشاء الداتا بيس ، برمجة الويب ورفع المشروع على السيرفر ، كان ف الفريق طلاب فرونت ايند ف سوينا لهم API واستخدمنا بوست مان ، وبرضو تعلمنا استخدام قيت هب واوامر القيت، تدريب مميز جدا ومفيد بالكامل",
                'pros' => 'مشروع حقيقي من الصفر، تعلم Laravel وGit وPostman، فريق متكامل',
                'cons' => null,
                'reviewer_major' => 'أمن سيبراني',
            ],

            // Row 7 — أمانة جدة (Data analysis Power BI, 4/5)
            [
                'company_name' => 'أمانة جدة',
                'sector' => 'government',
                'city' => 'جدة',
                'role_title' => 'محلل/ة بيانات',
                'department' => 'تحليل البيانات',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 2,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'recommendation' => 'maybe',
                'review_text' => "المهام كانت عبارة عن تحليل بيانات استخدمنا اكسل وبايثون سوينا داش بوردز على power Bi حلاوة المهام كانت متكاملة للأخير تستلمي بيانات خام وترتيب وتنظفي وتشتغلي عليها الين توصليها لمرحلة انك تعملي داش بورد وفي وقت متاح ماهو ضغط مره.\n \nالعيوب: ماكان في احد يعلم ويوجه ابدا يعني كله حيكون اجتهاد وتعليم شخصي في تطور نعم لكن اوقات الواحد يحتاج يلجأ لاحد خبرة أكثر منه في المجال، الشغل كله كان على اجهزتنا الخاصة",
                'pros' => 'مهام تحليل بيانات متكاملة، استخدام Excel وPython وPower BI، وقت مرن',
                'cons' => 'لا يوجد توجيه أو إرشاد، العمل على أجهزة شخصية',
                'reviewer_major' => 'علم البيانات',
            ],

            // Row 8 — فندق فور بوينتس شيراتون (IT at a hotel, 2/5)
            [
                'company_name' => 'فندق فور بوينتس شيراتون',
                'sector' => 'private',
                'city' => 'مكة المكرمة',
                'role_title' => 'متخصص/ة IT',
                'department' => 'تقنية المعلومات',
                'modality' => 'onsite',
                'mixed_env' => false,
                'rating_mentorship' => 3,
                'rating_learning' => 2,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'recommendation' => 'maybe',
                'review_text' => "المسمى: متخصص it\nالتدريب كنت it في فندق وغالبا المستشفيات نفس السيناريو\nالتدريب من ناحية تخصصي \"ذكاء اصطناعي\" ماله اي فائدة نهائيا مافيه اي برمجه ولا اكواد ولا مشاريع برمجية ولا اي شي له دخل بالتخصص و اغلب الشغل كان فيزيائي ولكن انه ممكن يفيد الي تخصصهم هندسة حاسب لأن الشغل غالبا يكون شبكات واسلاك وتوصيل وسيرفرات ونت وغيرهم \nولكن فيه بعض الاشياء العامه تعلمتها مثل كيف تفرمت اجهزة قديمه وكيف تحمل برنامج اوبيرا وتعرفت على الفندق والقاعات الي فيه ونظام السيرفرات والكهرباء فيه واغلبه كان شغل تربل شوت يعني مشاكل وطريقة حلها\nولكن من ناحية برمجية ما تعلمت اي شي ما انصحه للتخصصات البرمجيه لكن انصح الي تخصصه هندسة شبكات او امن سيبراني او شخص حابب يوسع علمه بمجال غير تخصصه انه يتدرب بفندق او بمستشفى وبيتعلم عنها الكثير\n\nملاحظة: الفندق بالبداية اصلا ماقبلوني لأن تدريبهم على كلامهم فقط للي اخر سنه يعني تتدرب تتخرج يوظفوك على طول ولكن بشوية اقناعات قدرت اتدرب عندهم وانا بالسنه الثالثه",
                'pros' => 'تعلم أساسيات الشبكات والسيرفرات والأجهزة، مفيد لهندسة الشبكات/الأمن السيبراني',
                'cons' => 'لا يوجد برمجة إطلاقاً، غير مناسب لتخصص الذكاء الاصطناعي، عمل فيزيائي',
                'reviewer_major' => 'ذكاء اصطناعي',
            ],

            // Row 9 — وزارة الحج والعمرة (Unhelpful during Hajj, 2/5)
            [
                'company_name' => 'وزارة الحج والعمرة',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => 'الدعم الفني والصيانة',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 2,
                'rating_learning' => 2,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'recommendation' => 'no',
                'review_text' => "مافيه مسمى وظيفي ، في البداية علمونا على طريقة عمل موظفين الصيانة والدعم الفني ، وبعدين كلفونا بمهام غير مفيدة \n\nطبعا السبب هو انهم مضغوطين في موسم الحج ، لحد يتدرب في وزارة الحج والعمرة الترم الثالث ، بيكونون مضغوطين ومحد فاضي لك كل يوم",
                'pros' => 'تعريف أولي بعمل الصيانة والدعم الفني',
                'cons' => 'مهام غير مفيدة، الموظفون مشغولون في موسم الحج، لا يوجد وقت للمتدربين',
                'reviewer_major' => 'تفاعل الإنسان مع الحاسب',
            ],

            // Row 10 — امانة العاصمة المقدسة- الكعكية (Theoretical, 3/5)
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 3,
                'rating_learning' => 2,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 3,
                'recommendation' => 'maybe',
                'review_text' => "المهام بسيطة جدا اغلبها نظري مهندس يتكلم عن شغلو وكذا أشياء بسيطة \nوفيه أشياء ممتعة زي تصميم الواجهات لكن قليل العملي",
                'pros' => 'بعض الأنشطة الممتعة مثل تصميم الواجهات',
                'cons' => 'أغلب المهام نظرية وبسيطة، قليل من التطبيق العملي',
                'reviewer_major' => 'علم البيانات',
            ],

            // Row 11 — أمانة العاصمة المقدسة (Theoretical, 2/5)
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 3,
                'rating_learning' => 2,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 2,
                'recommendation' => 'maybe',
                'review_text' => 'التدريب نظري مثل المحاضرات ، اذا شرحو لنا شي عملي نطبقه ع اجهزتنا ، كلفونا ناخذ دورات ونعرض لهم الشهادات ونتكلم عن دورة وحده منهم ك بريزنتيشن ، برضو اشتغلنا على مشروع خاص فينا وبنقدمه لهم نهاية التدريب. ك طالبة ذكاء الخطه ما تناسبنا لكن ناسبت علوم الحاسب وبنات التفاعل. المشرفين كويسين مره ومتعاونين.',
                'pros' => 'المشرفون متعاونون، مشروع تخرج نهاية التدريب',
                'cons' => 'التدريب نظري مثل المحاضرات، الخطة لا تناسب تخصص الذكاء الاصطناعي',
                'reviewer_major' => 'ذكاء اصطناعي',
            ],

            // Row 14 — إمارة منطقة مكة (Varied tasks, 4/5)
            [
                'company_name' => 'إمارة منطقة مكة المكرمة',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => false,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'recommendation' => 'maybe',
                'review_text' => 'مافي مسمى بالضبط لكن اي مهمة جديدة تشتغل عليها وهي متنوعة',
                'pros' => 'مهام متنوعة',
                'cons' => 'لا يوجد مسمى وظيفي محدد',
                'reviewer_major' => 'علوم حاسب',
            ],

            // Row 15 — الكلية التقنية (Mostly Excel, self-directed, 3/5)
            [
                'company_name' => 'الكلية التقنية',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => false,
                'rating_mentorship' => 2,
                'rating_learning' => 3,
                'rating_culture' => 3,
                'rating_compensation' => 1,
                'overall_rating' => 3,
                'recommendation' => 'maybe',
                'review_text' => 'اغلب مهامي كانت تخص الاكسل إستفدت من هالناحيه كثير لكن اغلب الامور اللي تخص تخصصي اضطريت اسويها بنفسي بدون اشراف الجهة بسبب قلة خبرتهم في التخصصات التقنية',
                'pros' => 'استفادة جيدة من مهارات Excel',
                'cons' => 'قلة خبرة الجهة في التخصصات التقنية، غياب الإشراف المتخصص',
                'reviewer_major' => 'علوم حاسب',
            ],

            // Row 18 — امانة جدة (Data analysis, no teaching, 4/5)
            [
                'company_name' => 'أمانة جدة',
                'sector' => 'government',
                'city' => 'جدة',
                'role_title' => 'محلل/ة بيانات',
                'department' => 'تحليل بيانات الرخص والبلديات',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 3,
                'rating_learning' => 3,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'recommendation' => 'yes',
                'review_text' => 'مافي مسمى - تحليل بيانات رخص وبلديات وحلو الشغل بس المشكله مافي تعليم يعني اذا فاهم شي تسويه منت فاهم محد يعلمك - التدريب جيد بشكل عام بس الشغل مو ذاك الترتيب بس البيئة حلوه والموظفين عسل والمشرف متعاون - تجربة حلوه مره',
                'pros' => 'بيئة عمل جميلة، موظفون لطفاء، مشرف متعاون',
                'cons' => 'لا يوجد تعليم فعلي، العمل غير منظم',
                'reviewer_major' => 'علم البيانات',
            ],

            // Row 19 — الكلية التقنية (Kind staff, data entry, 4/5)
            [
                'company_name' => 'الكلية التقنية',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => false,
                'rating_mentorship' => 4,
                'rating_learning' => 3,
                'rating_culture' => 5,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'recommendation' => 'maybe',
                'review_text' => 'الصدق انحديت عليهم بس كانو قمة بالتعامل الراقي، المهام كلها كانت ادخال بيانات لكن اعتقد لو جيت بوقت بدري مب بنص الترم راح يقبلوني عند دكتورة متخصصة. ايضًا متعاونين من ناحية اذا تبينهم يدربونك بشي بتخصصك او اذا انتي حابه تسوين شي من تخصصك اكيد بيعطونك المجال',
                'pros' => 'تعامل راقٍ، مرونة في السماح بالعمل على مشاريع التخصص',
                'cons' => 'أغلب المهام إدخال بيانات، يحتاج التقديم المبكر لفرصة أفضل',
                'reviewer_major' => 'علوم حاسب',
            ],

            // Row 21 — أمانة جدة (Very negative experience, 1/5)
            [
                'company_name' => 'أمانة جدة',
                'sector' => 'government',
                'city' => 'جدة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'remote',
                'mixed_env' => false,
                'rating_mentorship' => 1,
                'rating_learning' => 1,
                'rating_culture' => 1,
                'rating_compensation' => 1,
                'overall_rating' => 1,
                'recommendation' => 'no',
                'review_text' => "ضاايعين حرفيًا شافت السجل والخطاب و cv نظرة سريعة وقبلت على طول حسيت ما كانت تهمها هذي التفاصيل ما في مكاتب مخصصة لنا وقالت لو اضطريتوا تجون بتجلسون في غرفة كبيييرة مختلطة وفي طاولة زي حقت الاجتماعات مستطيلة نجلس عليها كان  معاي فالتدريب ولدين وشرحت لها اني ما ابغى اشتغل معاهم جمعتنا في قروب واتس لكن للامانة كل شغلنا منفصل ما كانت مهتمة بالتدريب ابددد هي كانت توهمنا بهذا الشيء لكن للامانة احنا الي كنا نجري وراها عطينا شغل وتاسكات او على الاقل داتا نمشي فيها امورنا ما كانت ترد وسافهتنا والوضع فوووضة لكن بعدين بدت تتعاون هي للامانة طيبة لكن التجربة معاهم فااشلة بتشيلون هم كل شيء المشرف الاكاديمي وزيارته والتقارير والشغل ولا شيء قالته لنا على اساس نسوي الخطة فيه خلتنا نشتغل عليه طبعا هي من البداية قالت خطتكم انتو سووها انا ما افهم في تخصصكم (المفترض هجينا من وقتها) طبعا ما كان في دوام اتفقت معانا يكون عن بعد وما نجي الا للحاجة على اساس انه اريح لنا وابغاكم تكونون مركزين وتشتغلون اسرع ومن هذا الكلام وبالاخير لقمنا وحتى لما قالت تعالوا وتجهزنا فنص الطريق قالت عندي لجنة مشرفين لا تجون الزبدة التجرربة فااشلة وللمعلومية الجهة كلها اختلاااط حتى hr الي بتروحون له للتقديم غالبا رجل ما انصح بالجهة وبعدين عرفت انها معروفة تعطي اسم تدريب للطالب فقط بدون تدريب حقيقي او حتى احترام ما اعرف انا هذي تجربتي ممكن تلقون احد غيري تدرب فنفس الجهة لكن تجربته جميللة الفرق اكيد الادارة والمشرفة انصحكم قدموا من بدرييي وقدموا يدوي لا تستهينون واسألوا عن كل حاجة قبل تقبلون واسألوا غيركم كيف تدربوا عن الادارة او المشرفة واستخيروا \n\nبالاخير الي تبغى تتدرب صح وتبغى كرامتهااا والناس ترد عليها وتحترمها وما تتمرمط تهجج من هذي الجهة نصيحة\n\nهذي الجهة تمشون فيها حالكم إذا انزقتوا ولازم تبدون تدريب وتسلكون فيها للجامعة عشان تتخرجون\n\nانتو محظوظين ترى لان في دفعة قبلكم عاشت هذي التجارب ونقلتها اكم",
                'pros' => null,
                'cons' => 'إدارة غير مهتمة، لا توجد مكاتب مخصصة، لا توجد مهام حقيقية، المتدرب يتحمل كل شيء',
                'reviewer_major' => 'علم البيانات',
            ],

            // Row 22 — Inpro Studio (UX/UI → PM, 4/5, English review)
            [
                'company_name' => 'Inpro Studio',
                'sector' => 'private',
                'city' => 'مكة المكرمة',
                'role_title' => 'UX/UI Specialist',
                'department' => 'إدارة المنتج والتصميم',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 3,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'recommendation' => 'maybe',
                'review_text' => "UX/UI specialist: didnt do much as a ux/ui since we didnt have a development/programmer trainee with us so we got transferred as PM but am sure if the team’s diversity was of different majors it wouldve been a much better experience\n\nProduct Manager: as pm we took charge on how a real product was going to work from internal tools to external real working products from interactions to core functionalities and pricing etc\n\nPros: training area was clean, comfortable and suitable as for work environment its encouraging and engaging to be expected from a good company\n\nCons: these are mainly mismanagement and expectations from trainees and recruiters, since our trainees team wasnt diverse in majors we faced rollblocks in term of our journey and as said before from ux/ui to pm as ux we did well but as ui we couldnt do much since we needed programmers  anyways these “bad” parts about the training IS to be expected from work places so i say i earned actual experience and fixed expectations for the other trainees\n\nWhy its a “maybe” as a recommendation it depends on the person and what they want to learn",
                'pros' => 'Clean and comfortable training area, encouraging work environment, real product management experience',
                'cons' => 'Mismanagement of trainee expectations, lack of team diversity blocked UI work',
                'reviewer_major' => 'تفاعل الإنسان مع الحاسب',
            ],

            // Row 23 — تكوين TechWin (Front-end team, 5/5)
            [
                'company_name' => 'تكوين TechWin',
                'sector' => 'private',
                'city' => 'مكة المكرمة',
                'role_title' => 'مطور/ة Front-end',
                'department' => 'تطوير الويب',
                'modality' => 'onsite',
                'mixed_env' => true,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 2,
                'overall_rating' => 5,
                'recommendation' => 'yes',
                'review_text' => "فرونت اند،\nبرمجة موقع من الصفر مع فريق،\nهم اعطونا مشروع بداية التدريب كأنهم عملاء واحنا سميناه،\nبيئة العمل متعاونة ومُثمرة تعليميا ومرحة",
                'pros' => 'مشروع فرونت إند من الصفر مع فريق، بيئة تعاونية ومثمرة ومرحة',
                'cons' => null,
                'reviewer_major' => 'علوم حاسب',
            ],

            // Row 24 — الامانة العاصمة المقدسة منطقة مكة (No real tasks, 1/5)
            [
                'company_name' => 'أمانة العاصمة المقدسة',
                'sector' => 'government',
                'city' => 'مكة المكرمة',
                'role_title' => 'متدرب تعاوني',
                'department' => null,
                'modality' => 'onsite',
                'mixed_env' => false,
                'rating_mentorship' => 1,
                'rating_learning' => 1,
                'rating_culture' => 2,
                'rating_compensation' => 1,
                'overall_rating' => 1,
                'recommendation' => 'no',
                'review_text' => 'بصراحة، ما كان فيه مهام فعلية أو واضحة أقدر أقول إنها أثرت على تطوري المهني. أغلب الوقت كان يمر بدون تكليفات مفيدة، وإذا تم إعطائي مهام، فكانت سطحية جدًا أو مجرد تسليك. الميزة الوحيدة ان أوقات الدوام مرنة،',
                'pros' => 'أوقات الدوام مرنة',
                'cons' => 'لا توجد مهام فعلية أو واضحة، المهام سطحية ومجرد تسليك',
                'reviewer_major' => 'أمن سيبراني',
            ],
        ];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $company = Company::firstOrCreate(
                    ['name' => $row['company_name']],
                    ['status' => 'approved']
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
                    'company_id' => $company->id,
                    'role_title' => $row['role_title'],
                    'department' => $row['department'],
                    'city' => $row['city'],
                    'duration_months' => 2,
                    'sector' => $row['sector'],
                    'modality' => $row['modality'],
                    'stipend_sar' => null,
                    'had_supervisor' => null,
                    'mixed_env' => $row['mixed_env'],
                    'job_offer' => null,
                    'rating_mentorship' => $row['rating_mentorship'],
                    'rating_learning' => $row['rating_learning'],
                    'rating_culture' => $row['rating_culture'],
                    'rating_compensation' => $row['rating_compensation'],
                    'overall_rating' => $row['overall_rating'],
                    'recommendation' => $row['recommendation'],
                    'review_text' => $row['review_text'],
                    'pros' => $row['pros'],
                    'cons' => $row['cons'],
                    'reviewer_name' => null,
                    'reviewer_major' => $row['reviewer_major'],
                ]);
            }
        });
    }
}
