<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\Rating;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statically-typed seeder. Every company and every rating below was
 * transcribed by hand from the 6 form CSVs under `seeders/data/`:
 *
 *   - File #5 → COOP authoritative company directory (no ratings)
 *   - File #2 → 2019-2022 كلية الحاسب survey
 *   - File #7 → Summer 1443 (2022) survey
 *   - File #4 → 2023 training-experiences form (onsite rows only)
 *   - File #1 → 2025 cohort-44 form
 *   - File #8 → 2026 cohort-45 (COOP 1445) form
 *
 * Facet scores were inferred by hand from the Arabic review text for each
 * row — not copied from a single overall score.
 *
 * Re-running truncates both tables first, so the file is idempotent.
 */
class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('ratings')->truncate();
        DB::table('companies')->truncate();
        Schema::enableForeignKeyConstraints();

        $ids = [];

        foreach ($this->companies() as $key => $data) {
            $data['is_imported'] = true;
            $ids[$key] = Company::create($data)->id;
        }

        // File #2 entries (indices 0–202) are from KSU; the rest from UQU.
        foreach ($this->ratings() as $index => $row) {
            $key = $row['company'];
            unset($row['company']);
            $row['company_id'] = $ids[$key];

            if (! isset($row['reviewer_university'])) {
                if ($index < 203) {
                    $row['reviewer_university'] = 'جامعة الملك سعود';
                    $row['reviewer_college'] = 'كلية علوم الحاسب والمعلومات';
                } else {
                    $row['reviewer_university'] = 'جامعة أم القرى';
                    $row['reviewer_college'] = 'كلية الحاسبات وتقنية المعلومات';
                }
            }

            $row['is_imported'] = true;
            Rating::create($row);
        }
    }

    /**
     * @return array<string, array{name: string, type: ?CompanyType, website: ?string, description: ?string, status: string}>
     */
    private function companies(): array
    {
        return [
            // ============================================================
            // File #5 — COOP directory (authoritative company records)
            // ============================================================

            // -- الهيئات -----------------------------------------------------
            'sdaia' => [
                'name' => 'الهيئة السعودية للبيانات والذكاء الاصطناعي',
                'type' => CompanyType::Government,
                'website' => 'https://sdaia.gov.sa/',
                'description' => 'الجهة المرجعية الوطنية للبيانات والذكاء الاصطناعي في المملكة.',
                'status' => 'approved',
            ],
            'socpa' => [
                'name' => 'الهيئة السعودية للمراجعين والمحاسبين',
                'type' => CompanyType::Government,
                'website' => 'https://socpa.org.sa/',
                'description' => 'الهيئة المنظمة لمهنة المحاسبة والمراجعة في المملكة.',
                'status' => 'approved',
            ],
            'hcis' => [
                'name' => 'الهيئة العليا للأمن الصناعي',
                'type' => CompanyType::Government,
                'website' => 'https://hcis.gov.sa/',
                'description' => 'الجهة المسؤولة عن تأمين المنشآت الصناعية الحيوية.',
                'status' => 'approved',
            ],
            'spga' => [
                'name' => 'الهيئة العامة لعقارات الدولة',
                'type' => CompanyType::Government,
                'website' => 'https://spga.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'hrc' => [
                'name' => 'هيئة حقوق الإنسان السعودية',
                'type' => CompanyType::Government,
                'website' => 'https://www.hrc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'saudi_exports' => [
                'name' => 'هيئة تنمية الصادرات السعودية',
                'type' => CompanyType::Government,
                'website' => 'https://www.saudiexports.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'etec' => [
                'name' => 'هيئة تقويم التعليم والتدريب',
                'type' => CompanyType::Government,
                'website' => 'https://www.etec.gov.sa/',
                'description' => 'الجهة الوطنية المسؤولة عن تقويم منظومة التعليم والتدريب.',
                'status' => 'approved',
            ],
            'rcrc' => [
                'name' => 'الهيئة الملكية لمدينة الرياض',
                'type' => CompanyType::Government,
                'website' => 'https://www.rcrc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'saso' => [
                'name' => 'الهيئة السعودية للمواصفات والمقاييس والجودة',
                'type' => CompanyType::Government,
                'website' => 'https://www.saso.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ecza' => [
                'name' => 'هيئة المدن الاقتصادية السعودية',
                'type' => CompanyType::Government,
                'website' => 'http://www.ecza.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'lcgpa' => [
                'name' => 'هيئة المحتوى المحلي والمشتريات الحكومية',
                'type' => CompanyType::Government,
                'website' => 'https://lcgpa.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'cma' => [
                'name' => 'هيئة السوق المالية',
                'type' => CompanyType::Government,
                'website' => 'https://cma.org.sa/',
                'description' => 'الجهة التنظيمية لسوق رأس المال في المملكة.',
                'status' => 'approved',
            ],
            'citc' => [
                'name' => 'هيئة الاتصالات وتقنية المعلومات',
                'type' => CompanyType::Government,
                'website' => 'https://www.citc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'nazaha' => [
                'name' => 'الهيئة الوطنية لمكافحة الفساد',
                'type' => CompanyType::Government,
                'website' => 'https://www.nazaha.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'rcu' => [
                'name' => 'الهيئة الملكية لمحافظة العُلا',
                'type' => CompanyType::Government,
                'website' => 'https://www.rcu.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'rcjy' => [
                'name' => 'الهيئة الملكية للجبيل وينبع',
                'type' => CompanyType::Government,
                'website' => 'https://www.rcjy.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'gac' => [
                'name' => 'الهيئة العامة للمنافسة',
                'type' => CompanyType::Government,
                'website' => 'https://gac.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'sfda' => [
                'name' => 'الهيئة العامة للغذاء والدواء',
                'type' => CompanyType::Government,
                'website' => 'https://www.sfda.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'rega' => [
                'name' => 'الهيئة العامة للعقار',
                'type' => CompanyType::Government,
                'website' => 'https://www.rega.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'gaca' => [
                'name' => 'الهيئة العامة للطيران المدني',
                'type' => CompanyType::Government,
                'website' => 'https://gaca.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'gea' => [
                'name' => 'الهيئة العامة للترفيه',
                'type' => CompanyType::Government,
                'website' => 'https://www.gea.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'gcam' => [
                'name' => 'الهيئة العامة للإعلام المرئي والمسموع',
                'type' => CompanyType::Government,
                'website' => 'https://www.gcam.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'gastat' => [
                'name' => 'الهيئة العامة للإحصاء',
                'type' => CompanyType::Government,
                'website' => 'https://www.stats.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'saip' => [
                'name' => 'الهيئة السعودية للملكية الفكرية',
                'type' => CompanyType::Government,
                'website' => 'https://www.saip.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'modon' => [
                'name' => 'الهيئة السعودية للمدن الصناعية ومناطق التقنية',
                'type' => CompanyType::Government,
                'website' => 'https://modon.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ssc' => [
                'name' => 'الهيئة السعودية للفضاء',
                'type' => CompanyType::Government,
                'website' => 'https://saudispace.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'srca' => [
                'name' => 'الهلال الأحمر السعودي',
                'type' => CompanyType::NonProfit,
                'website' => 'https://www.srca.org.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'scfhs' => [
                'name' => 'الهيئة السعودية للتخصصات الصحية',
                'type' => CompanyType::Government,
                'website' => 'https://www.scfhs.org.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'nca' => [
                'name' => 'الهيئة الوطنية للأمن السيبراني',
                'type' => CompanyType::Government,
                'website' => 'https://nca.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'sta' => [
                'name' => 'الهيئة السعودية للسياحة',
                'type' => CompanyType::Government,
                'website' => 'https://sta.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'monshaat' => [
                'name' => 'الهيئة العامة للمنشآت الصغيرة والمتوسطة',
                'type' => CompanyType::Government,
                'website' => 'https://www.monshaat.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'awqaf' => [
                'name' => 'الهيئة العامة للأوقاف',
                'type' => CompanyType::Government,
                'website' => 'https://www.awqaf.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'zatca' => [
                'name' => 'هيئة الزكاة والضريبة والجمارك',
                'type' => CompanyType::Government,
                'website' => 'https://zatca.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- المؤسسات ----------------------------------------------------
            'sama' => [
                'name' => 'البنك المركزي السعودي',
                'type' => CompanyType::Government,
                'website' => 'https://www.sama.gov.sa/',
                'description' => 'البنك المركزي للمملكة (ساما).',
                'status' => 'approved',
            ],
            'swcc' => [
                'name' => 'المؤسسة العامة لتحلية المياه المالحة',
                'type' => CompanyType::Government,
                'website' => 'https://www.swcc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'tvtc' => [
                'name' => 'المؤسسة العامة للتدريب التقني والمهني',
                'type' => CompanyType::Government,
                'website' => 'https://www.tvtc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'pension' => [
                'name' => 'المؤسسة العامة للتقاعد',
                'type' => CompanyType::Government,
                'website' => 'https://www.pension.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mawhiba' => [
                'name' => 'مؤسسة الملك عبدالعزيز ورجاله للموهبة والإبداع',
                'type' => CompanyType::NonProfit,
                'website' => 'https://www.mawhiba.org/',
                'description' => 'مؤسسة غير ربحية لرعاية الموهوبين والمبدعين.',
                'status' => 'approved',
            ],

            // -- الوزارات ----------------------------------------------------
            'mofa' => [
                'name' => 'وزارة الخارجية',
                'type' => CompanyType::Government,
                'website' => 'https://www.mofa.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mof' => [
                'name' => 'وزارة المالية',
                'type' => CompanyType::Government,
                'website' => 'https://www.mof.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'moj' => [
                'name' => 'وزارة العدل',
                'type' => CompanyType::Government,
                'website' => 'https://www.moj.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'hrsd' => [
                'name' => 'وزارة الموارد البشرية والتنمية الاجتماعية',
                'type' => CompanyType::Government,
                'website' => 'https://hrsd.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'moe' => [
                'name' => 'وزارة التعليم',
                'type' => CompanyType::Government,
                'website' => 'https://www.moe.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mep' => [
                'name' => 'وزارة الاقتصاد والتخطيط',
                'type' => CompanyType::Government,
                'website' => 'https://www.mep.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'misa' => [
                'name' => 'وزارة الاستثمار',
                'type' => CompanyType::Government,
                'website' => 'https://misa.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'moenergy' => [
                'name' => 'وزارة الطاقة',
                'type' => CompanyType::Government,
                'website' => 'https://www.moenergy.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'moc' => [
                'name' => 'وزارة الثقافة',
                'type' => CompanyType::Government,
                'website' => 'https://www.moc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'housing' => [
                'name' => 'وزارة الإسكان',
                'type' => CompanyType::Government,
                'website' => 'https://www.housing.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'media' => [
                'name' => 'وزارة الإعلام',
                'type' => CompanyType::Government,
                'website' => 'https://media.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mcit' => [
                'name' => 'وزارة الاتصالات وتقنية المعلومات',
                'type' => CompanyType::Government,
                'website' => 'https://www.mcit.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mewa' => [
                'name' => 'وزارة البيئة والمياه والزراعة',
                'type' => CompanyType::Government,
                'website' => 'https://www.mewa.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mci' => [
                'name' => 'وزارة التجارة',
                'type' => CompanyType::Government,
                'website' => 'https://mc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'moh' => [
                'name' => 'وزارة الصحة',
                'type' => CompanyType::Government,
                'website' => 'https://www.moh.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mim' => [
                'name' => 'وزارة الصناعة والثروة المعدنية',
                'type' => CompanyType::Government,
                'website' => 'https://mim.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mot' => [
                'name' => 'وزارة النقل',
                'type' => CompanyType::Government,
                'website' => 'https://www.mot.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- المراكز -----------------------------------------------------
            'seec' => [
                'name' => 'المركز السعودي لكفاءة الطاقة',
                'type' => CompanyType::Government,
                'website' => 'https://www.seec.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ncdc' => [
                'name' => 'المركز الوطني للتصديق الرقمي',
                'type' => CompanyType::Government,
                'website' => 'https://www.ncdc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ncc' => [
                'name' => 'المركز الوطني للتنافسية',
                'type' => CompanyType::Government,
                'website' => 'https://www.ncc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ncai' => [
                'name' => 'المركز الوطني للذكاء الاصطناعي',
                'type' => CompanyType::Government,
                'website' => 'https://sdaia.gov.sa/ncai/',
                'description' => 'تابع لسدايا، معني بتطوير تطبيقات الذكاء الاصطناعي الوطنية.',
                'status' => 'approved',
            ],
            'ncar' => [
                'name' => 'المركز الوطني للوثائق والمحفوظات',
                'type' => CompanyType::Government,
                'website' => 'https://ncar.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'kapsarc' => [
                'name' => 'مركز الملك عبدالله للدراسات والبحوث البترولية',
                'type' => CompanyType::NonProfit,
                'website' => 'https://www.kapsarc.org/',
                'description' => 'مركز بحثي غير ربحي متخصص في اقتصاديات الطاقة.',
                'status' => 'approved',
            ],

            // -- الصناديق ----------------------------------------------------
            'sfd' => [
                'name' => 'الصندوق السعودي للتنمية',
                'type' => CompanyType::Government,
                'website' => 'https://www.sfd.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'pif' => [
                'name' => 'صندوق الاستثمارات العامة',
                'type' => CompanyType::Government,
                'website' => 'https://www.pif.gov.sa/',
                'description' => 'الذراع الاستثمارية السيادية للمملكة.',
                'status' => 'approved',
            ],
            'adf' => [
                'name' => 'صندوق التنمية الزراعية',
                'type' => CompanyType::Government,
                'website' => 'https://adf.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'sidf' => [
                'name' => 'صندوق التنمية الصناعية السعودي',
                'type' => CompanyType::Government,
                'website' => 'https://www.sidf.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'redf' => [
                'name' => 'صندوق التنمية العقارية',
                'type' => CompanyType::Government,
                'website' => 'https://www.housing.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ndf' => [
                'name' => 'صندوق التنمية الوطني',
                'type' => CompanyType::Government,
                'website' => 'https://www.ndf.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'hrdf' => [
                'name' => 'صندوق تنمية الموارد البشرية',
                'type' => CompanyType::Government,
                'website' => 'https://www.hrdf.org.sa/',
                'description' => 'صندوق (هدف) لتطوير الموارد البشرية الوطنية.',
                'status' => 'approved',
            ],

            // -- بنوك --------------------------------------------------------
            'ncb' => [
                'name' => 'البنك الأهلي السعودي',
                'type' => CompanyType::Private,
                'website' => 'https://www.alahli.com/',
                'description' => 'البنك الأهلي (SNB) — أكبر بنك في المملكة.',
                'status' => 'approved',
            ],
            'saib' => [
                'name' => 'البنك السعودي للاستثمار',
                'type' => CompanyType::Private,
                'website' => 'https://www.saib.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'alinma' => [
                'name' => 'مصرف الإنماء',
                'type' => CompanyType::Private,
                'website' => 'https://www.alinma.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'bsf' => [
                'name' => 'البنك السعودي الفرنسي',
                'type' => CompanyType::Private,
                'website' => 'https://www.alfransi.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'riyad_bank' => [
                'name' => 'بنك الرياض',
                'type' => CompanyType::Private,
                'website' => 'https://www.riyadbank.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'samba' => [
                'name' => 'مجموعة سامبا المالية',
                'type' => CompanyType::Private,
                'website' => 'https://m.samba.com/',
                'description' => 'اندمجت مع البنك الأهلي لتشكيل البنك الأهلي السعودي (SNB).',
                'status' => 'approved',
            ],
            'alrajhi' => [
                'name' => 'مصرف الراجحي',
                'type' => CompanyType::Private,
                'website' => 'https://www.alrajhibank.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'anb' => [
                'name' => 'البنك العربي الوطني',
                'type' => CompanyType::Private,
                'website' => 'https://anb.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'albilad' => [
                'name' => 'بنك البلاد',
                'type' => CompanyType::Private,
                'website' => 'https://www.bankalbilad.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'baj' => [
                'name' => 'بنك الجزيرة',
                'type' => CompanyType::Private,
                'website' => 'https://www.bankaljazira.com/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- أخرى --------------------------------------------------------
            'stc' => [
                'name' => 'شركة الاتصالات السعودية (STC)',
                'type' => CompanyType::Private,
                'website' => 'https://www.stc.com.sa/',
                'description' => 'أكبر مشغل اتصالات في المملكة.',
                'status' => 'approved',
            ],
            'mobily' => [
                'name' => 'شركة اتحاد اتصالات (موبايلي)',
                'type' => CompanyType::Private,
                'website' => 'https://www.mobily.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'bahri' => [
                'name' => 'الشركة الوطنية للنقل البحري (البحري)',
                'type' => CompanyType::Private,
                'website' => 'https://www.bahri.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'sabic' => [
                'name' => 'الشركة السعودية للصناعات الأساسية (سابك)',
                'type' => CompanyType::Private,
                'website' => 'https://www.sabic.com/',
                'description' => 'عملاق البتروكيماويات السعودي.',
                'status' => 'approved',
            ],
            'aramco' => [
                'name' => 'أرامكو السعودية',
                'type' => CompanyType::Private,
                'website' => 'https://www.aramco.com/',
                'description' => 'شركة الزيت العربية السعودية.',
                'status' => 'approved',
            ],
            'sar' => [
                'name' => 'الشركة السعودية للخطوط الحديدية (سار)',
                'type' => CompanyType::Private,
                'website' => 'https://www.sar.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'kpmg' => [
                'name' => 'KPMG',
                'type' => CompanyType::Private,
                'website' => 'https://kpmg.com/sa/',
                'description' => 'إحدى شركات "Big 4" للاستشارات والتدقيق.',
                'status' => 'approved',
            ],
            'ey' => [
                'name' => 'EY',
                'type' => CompanyType::Private,
                'website' => 'https://www.ey.com/en_sa',
                'description' => 'إحدى شركات "Big 4" للاستشارات والتدقيق.',
                'status' => 'approved',
            ],
            'pwc' => [
                'name' => 'PwC',
                'type' => CompanyType::Private,
                'website' => 'https://www.pwc.com/m1/',
                'description' => 'إحدى شركات "Big 4" للاستشارات والتدقيق.',
                'status' => 'approved',
            ],
            'deloitte' => [
                'name' => 'Deloitte',
                'type' => CompanyType::Private,
                'website' => 'https://www2.deloitte.com/sa/',
                'description' => 'إحدى شركات "Big 4" للاستشارات والتدقيق.',
                'status' => 'approved',
            ],
            'mckinsey' => [
                'name' => 'McKinsey & Company',
                'type' => CompanyType::Private,
                'website' => 'https://www.mckinsey.com/middle-east/',
                'description' => 'شركة استشارات إدارية عالمية.',
                'status' => 'approved',
            ],
            'sanabil' => [
                'name' => 'سنابل للاستثمارات',
                'type' => CompanyType::Private,
                'website' => 'http://www.sanabil.sa/',
                'description' => 'شركة استثمار مملوكة بالكامل لصندوق الاستثمارات العامة.',
                'status' => 'approved',
            ],
            'tawuniya' => [
                'name' => 'التعاونية للتأمين',
                'type' => CompanyType::Private,
                'website' => 'https://www.tawuniya.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ash_cliffordchance' => [
                'name' => 'شركة أبو حيمد وآل الشيخ والحقباني بالتعاون مع Clifford Chance',
                'type' => CompanyType::Private,
                'website' => 'https://www.ashlawksa.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'khoshaim' => [
                'name' => 'شركة الخشيم وشركاه',
                'type' => CompanyType::Private,
                'website' => 'https://www.khoshaim.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'aldhabaan_es' => [
                'name' => 'شركة الضبعان وشركاه بالتعاون مع Eversheds Sutherland',
                'type' => CompanyType::Private,
                'website' => 'https://www.eversheds-sutherland.com/global/en/where/middle-east/saudi-arabia/',
                'description' => null,
                'status' => 'approved',
            ],
            'ekp_legal' => [
                'name' => 'EKP Legal in association with AlEnezee Legal Counsel',
                'type' => CompanyType::Private,
                'website' => 'https://www.ekplegal.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'soaib_law' => [
                'name' => 'شركة الصعيب وشركاه للمحاماة',
                'type' => CompanyType::Private,
                'website' => 'http://soaiblaw.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'jadwa' => [
                'name' => 'جدوى للاستثمار',
                'type' => CompanyType::Private,
                'website' => 'http://www.jadwa.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'tadawul' => [
                'name' => 'تداول',
                'type' => CompanyType::Private,
                'website' => 'https://www.tadawul.com.sa/',
                'description' => 'السوق المالية السعودية.',
                'status' => 'approved',
            ],
            'taqnia' => [
                'name' => 'شركة التقنية السعودية (تقنية)',
                'type' => CompanyType::Private,
                'website' => 'https://taqnia.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'adeem' => [
                'name' => 'أديم المالية',
                'type' => CompanyType::Private,
                'website' => 'http://adeemcapital.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'derayah' => [
                'name' => 'دراية المالية',
                'type' => CompanyType::Private,
                'website' => 'http://www.derayah.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'falcom' => [
                'name' => 'فالكم للخدمات المالية',
                'type' => CompanyType::Private,
                'website' => 'http://www.falcom.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'thiqah' => [
                'name' => 'ثقة لخدمات الأعمال',
                'type' => CompanyType::Private,
                'website' => 'https://thiqah.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'unilever' => [
                'name' => 'يونيليفر',
                'type' => CompanyType::Private,
                'website' => 'https://www.unilever.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'maaden' => [
                'name' => 'شركة التعدين العربية السعودية (معادن)',
                'type' => CompanyType::Private,
                'website' => 'https://www.maaden.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ksb_capital' => [
                'name' => 'شركة كسب المالية',
                'type' => CompanyType::Private,
                'website' => 'http://www.kasbcapital.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'simah' => [
                'name' => 'الشركة السعودية للمعلومات الائتمانية (سمة)',
                'type' => CompanyType::Private,
                'website' => 'https://www.simah.com/',
                'description' => 'مكتب المعلومات الائتمانية السعودي.',
                'status' => 'approved',
            ],
            'takamol' => [
                'name' => 'تكامل القابضة',
                'type' => CompanyType::Private,
                'website' => 'https://www.takamolholding.com/',
                'description' => null,
                'status' => 'approved',
            ],
            // tetco removed — duplicate of tatweer (same website https://tetco.sa/)
            'wasatah' => [
                'name' => 'الوساطة المالية',
                'type' => CompanyType::Private,
                'website' => 'http://www.wasatah.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // ============================================================
            // File #2 — companies referenced by reviewers (not in COOP dir)
            // ============================================================

            // -- جامعات / عمادات / مدن علمية -----------------------------
            'kacst' => [
                'name' => 'مدينة الملك عبدالعزيز للعلوم والتقنية (كاكست)',
                'type' => CompanyType::Government,
                'website' => 'https://www.kacst.gov.sa/',
                'description' => 'المدينة الوطنية للبحث العلمي والتقنية، تضم معاهد ومراكز أبحاث.',
                'status' => 'approved',
            ],
            'kfupm' => [
                'name' => 'جامعة الملك فهد للبترول والمعادن',
                'type' => CompanyType::Government,
                'website' => 'https://www.kfupm.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'seu' => [
                'name' => 'الجامعة السعودية الإلكترونية',
                'type' => CompanyType::Government,
                'website' => 'https://www.seu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'imamu' => [
                'name' => 'جامعة الإمام محمد بن سعود الإسلامية',
                'type' => CompanyType::Government,
                'website' => 'https://imamu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ksu_dect' => [
                'name' => 'عمادة التعاملات الإلكترونية والاتصالات — جامعة الملك سعود',
                'type' => CompanyType::Government,
                'website' => 'https://dect.ksu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ksu_network' => [
                'name' => 'وحدة الشبكات — جامعة الملك سعود',
                'type' => CompanyType::Government,
                'website' => 'https://ksu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ksu_finance' => [
                'name' => 'الإدارة المالية — جامعة الملك سعود',
                'type' => CompanyType::Government,
                'website' => 'https://ksu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'dsr_ksu' => [
                'name' => 'عمادة البحث العلمي — جامعة الملك سعود',
                'type' => CompanyType::Government,
                'website' => 'https://dsrs.ksu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'dsr_pnu' => [
                'name' => 'عمادة البحث العلمي — جامعة الأميرة نورة',
                'type' => CompanyType::Government,
                'website' => 'https://www.pnu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'dsr_iau' => [
                'name' => 'عمادة البحث العلمي — جامعة الإمام عبدالرحمن بن فيصل',
                'type' => CompanyType::Government,
                'website' => 'https://www.iau.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'uqu_cyber' => [
                'name' => 'قسم الأمن السيبراني — جامعة أم القرى',
                'type' => CompanyType::Government,
                'website' => 'https://uqu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'riyada' => [
                'name' => 'معهد ريادة الأعمال الوطني',
                'type' => CompanyType::NonProfit,
                'website' => 'https://riyada.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- مستشفيات / قطاع صحي -----------------------------------
            'kauh' => [
                'name' => 'مستشفى الملك عبدالعزيز الجامعي',
                'type' => CompanyType::Government,
                'website' => 'https://kauh.ksu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'kkuh' => [
                'name' => 'مستشفى الملك خالد الجامعي',
                'type' => CompanyType::Government,
                'website' => 'https://kkuh.ksu.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'kfshrc' => [
                'name' => 'مستشفى الملك فيصل التخصصي ومركز الأبحاث',
                'type' => CompanyType::NonProfit,
                'website' => 'https://www.kfshrc.edu.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'kfmc' => [
                'name' => 'مدينة الملك فهد الطبية',
                'type' => CompanyType::Government,
                'website' => 'https://www.kfmc.med.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'habib_hospital' => [
                'name' => 'مستشفى الدكتور سليمان الحبيب',
                'type' => CompanyType::Private,
                'website' => 'https://hmg.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'hira_hospital' => [
                'name' => 'مستشفى حراء العام',
                'type' => CompanyType::Government,
                'website' => 'https://www.moh.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'erada' => [
                'name' => 'مجمع إرادة للصحة النفسية',
                'type' => CompanyType::Government,
                'website' => 'https://www.moh.gov.sa/',
                'description' => 'تابع لوزارة الصحة، يعنى بالصحة النفسية والتعافي من الإدمان.',
                'status' => 'approved',
            ],
            'dr_arab_center' => [
                'name' => 'مركز الدكتور عاصم عرب',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],

            // -- بلديات / أمانات / إمارات -------------------------------
            'momra' => [
                'name' => 'وزارة الشؤون البلدية والقروية والإسكان',
                'type' => CompanyType::Government,
                'website' => 'https://www.momra.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'riyadh_amanah' => [
                'name' => 'أمانة منطقة الرياض',
                'type' => CompanyType::Government,
                'website' => 'https://www.alriyadh.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'makkah_amanah' => [
                'name' => 'أمانة العاصمة المقدسة',
                'type' => CompanyType::Government,
                'website' => 'https://www.amana.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'makkah_emirate' => [
                'name' => 'إمارة منطقة مكة المكرمة',
                'type' => CompanyType::Government,
                'website' => 'https://www.makkah.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- وزارات / جهات حكومية أخرى ------------------------------
            'mng' => [
                'name' => 'وزارة الحرس الوطني',
                'type' => CompanyType::Government,
                'website' => 'https://mngha.med.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'mod' => [
                'name' => 'وزارة الدفاع',
                'type' => CompanyType::Government,
                'website' => 'https://www.mod.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'hajj' => [
                'name' => 'وزارة الحج والعمرة',
                'type' => CompanyType::Government,
                'website' => 'https://www.haj.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'gccmc' => [
                'name' => 'مجلس النقد الخليجي',
                'type' => CompanyType::Government,
                'website' => 'https://www.gccmc.org/',
                'description' => 'مجلس تابع لدول مجلس التعاون الخليجي معني بالاتحاد النقدي.',
                'status' => 'approved',
            ],
            'sscp' => [
                'name' => 'برنامج الخدمات المشتركة',
                'type' => CompanyType::Government,
                'website' => 'https://ssc.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ncgr' => [
                'name' => 'المركز الوطني للأنظمة الحكومية المشتركة (NCGR)',
                'type' => CompanyType::Government,
                'website' => 'https://www.ncgr.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- بنوك / مالية ------------------------------------------
            'sabb' => [
                'name' => 'البنك السعودي البريطاني (ساب)',
                'type' => CompanyType::Private,
                'website' => 'https://www.sab.com/',
                'description' => 'اندمج مع الأول ليصبح البنك السعودي الأول (SAB).',
                'status' => 'approved',
            ],
            'sab' => [
                'name' => 'البنك السعودي الأول (SAB)',
                'type' => CompanyType::Private,
                'website' => 'https://www.sab.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'sp' => [
                'name' => 'المدفوعات السعودية (Saudi Payments)',
                'type' => CompanyType::Government,
                'website' => 'https://www.saudipayments.com/',
                'description' => 'مشغل شبكة المدفوعات الوطنية (مدى)، تابع للبنك المركزي.',
                'status' => 'approved',
            ],
            'emkan' => [
                'name' => 'شركة إمكان للتمويل',
                'type' => CompanyType::Private,
                'website' => 'https://www.emkanfinance.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'tamara' => [
                'name' => 'تمارا',
                'type' => CompanyType::Private,
                'website' => 'https://tamara.co/',
                'description' => 'شركة تقنية مالية سعودية (اشترِ الآن وادفع لاحقًا).',
                'status' => 'approved',
            ],
            'arabianshield' => [
                'name' => 'الدرع العربي للتأمين التعاوني',
                'type' => CompanyType::Private,
                'website' => 'https://www.das.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'najm' => [
                'name' => 'نجم لخدمات التأمين',
                'type' => CompanyType::Private,
                'website' => 'https://najm.sa/',
                'description' => null,
                'status' => 'approved',
            ],

            // -- اتصالات / تقنية / استشارات ----------------------------
            'elm' => [
                'name' => 'شركة علم',
                'type' => CompanyType::Private,
                'website' => 'https://www.elm.sa/',
                'description' => 'شركة رقمية تابعة لصندوق الاستثمارات العامة، مقدمة لأنظمة رقمية حكومية كأبشر.',
                'status' => 'approved',
            ],
            'lean' => [
                'name' => 'لين لخدمات الأعمال',
                'type' => CompanyType::Private,
                'website' => 'https://www.leanbs.com/',
                'description' => 'شركة تقنية سعودية في مجال البيانات والذكاء الاصطناعي.',
                'status' => 'approved',
            ],
            'tahakom' => [
                'name' => 'شركة تحكم (Tahakom)',
                'type' => CompanyType::Private,
                'website' => 'https://tahakom.com/',
                'description' => 'مشغل نظام ساهر لضبط المخالفات المرورية، مملوكة لصندوق الاستثمارات العامة.',
                'status' => 'approved',
            ],
            'tcc' => [
                'name' => 'شركة تحكم التقنية المحدودة (TCC)',
                'type' => CompanyType::Private,
                'website' => 'https://www.tcc.com.sa/',
                'description' => 'Technology Control Co. — شركة شبه حكومية تابعة لمجموعة تحكم الاستثمارية.',
                'status' => 'approved',
            ],
            'tamkeen' => [
                'name' => 'تمكين للتقنيات',
                'type' => CompanyType::Private,
                'website' => 'https://www.tamkeentech.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'stc_solutions' => [
                'name' => 'حلول بواسطة stc (Solutions by stc)',
                'type' => CompanyType::Private,
                'website' => 'https://www.stcs.com.sa/',
                'description' => 'ذراع تقنية المعلومات لمجموعة stc.',
                'status' => 'approved',
            ],
            'sirar' => [
                'name' => 'سرار من stc',
                'type' => CompanyType::Private,
                'website' => 'https://sirar.sa/',
                'description' => 'شركة متخصصة في خدمات الأمن السيبراني، تابعة لمجموعة stc.',
                'status' => 'approved',
            ],
            'zain' => [
                'name' => 'زين السعودية',
                'type' => CompanyType::Private,
                'website' => 'https://www.sa.zain.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'aec' => [
                'name' => 'شركة الإلكترونيات المتقدمة',
                'type' => CompanyType::Private,
                'website' => 'https://www.aecl.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'dell_emc' => [
                'name' => 'Dell EMC',
                'type' => CompanyType::Private,
                'website' => 'https://www.dell.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'cisco' => [
                'name' => 'Cisco',
                'type' => CompanyType::Private,
                'website' => 'https://www.cisco.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'accenture' => [
                'name' => 'Accenture',
                'type' => CompanyType::Private,
                'website' => 'https://www.accenture.com/sa-en',
                'description' => 'شركة استشارات عالمية.',
                'status' => 'approved',
            ],
            'devoteam' => [
                'name' => 'Devoteam',
                'type' => CompanyType::Private,
                'website' => 'https://www.devoteam.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'masterworks' => [
                'name' => 'Master Works',
                'type' => CompanyType::Private,
                'website' => 'https://master-works.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'ejada' => [
                'name' => 'إجادة للنظم (Ejada Systems)',
                'type' => CompanyType::Private,
                'website' => 'https://ejada.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'dsshield' => [
                'name' => 'DSShield',
                'type' => CompanyType::Private,
                'website' => 'https://dsshield.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'sharedtech' => [
                'name' => 'SharedTech',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'expertise_systems' => [
                'name' => 'شركة نظم الخبرات لتقنية المعلومات',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'lean_node' => [
                'name' => 'Lean Node',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'smart_methods' => [
                'name' => 'الأساليب الذكية',
                'type' => CompanyType::Private,
                'website' => 'https://smartmethods.com/',
                'description' => 'شركة متخصصة في الروبوتات وإنترنت الأشياء.',
                'status' => 'approved',
            ],
            'safe_decision' => [
                'name' => 'القرار الآمن (Safe Decision)',
                'type' => CompanyType::Private,
                'website' => 'https://safedecision.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'es_consulting' => [
                'name' => 'ES Consulting',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'sahab' => [
                'name' => 'سحاب',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'hudhud' => [
                'name' => 'شركة هدهد (Hudhud Conversational AI)',
                'type' => CompanyType::Private,
                'website' => 'https://hudhud.ai/',
                'description' => null,
                'status' => 'approved',
            ],
            'minthar' => [
                'name' => 'شركة عدسة منظار التقنية (Minthar)',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'masarat' => [
                'name' => 'شركة مسارات العالمية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'quant' => [
                'name' => 'Quant Data & Analytics',
                'type' => CompanyType::Private,
                'website' => 'https://www.quant.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'moasharat' => [
                'name' => 'شركة مؤشرات لخدمات الأعمال',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'fixtag' => [
                'name' => 'Fix Tag',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'ebox' => [
                'name' => 'E-box Solution',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'amaz' => [
                'name' => 'أماز',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'osloob' => [
                'name' => 'أسلوب (Osloob)',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'itcl' => [
                'name' => 'ITCl',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'waraq' => [
                'name' => 'شركة الورق العربية (Waraq)',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'wadi_makkah' => [
                'name' => 'شركة وادي مكة',
                'type' => CompanyType::Private,
                'website' => 'https://wadimakkah.sa/',
                'description' => 'ذراع جامعة أم القرى لرأس المال الاستثماري.',
                'status' => 'approved',
            ],
            'kidana' => [
                'name' => 'كدانة للتنمية',
                'type' => CompanyType::Private,
                'website' => 'https://kidana.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'camco' => [
                'name' => 'شركة مترو العاصمة (CAMCO)',
                'type' => CompanyType::Private,
                'website' => 'https://rcrc.gov.sa/',
                'description' => 'مشغل مترو الرياض.',
                'status' => 'approved',
            ],
            'site' => [
                'name' => 'الشركة السعودية لتقنية المعلومات (SITE)',
                'type' => CompanyType::Private,
                'website' => 'https://www.site.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'qabas' => [
                'name' => 'شركة قبس التقنية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'etmal' => [
                'name' => 'شركة أتمال',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'jahez' => [
                'name' => 'شركة جاهز الدولية',
                'type' => CompanyType::Private,
                'website' => 'https://www.jahezgroup.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'rasmal' => [
                'name' => 'RasMal',
                'type' => CompanyType::Private,
                'website' => 'https://www.rasmal.com/',
                'description' => null,
                'status' => 'approved',
            ],
            't2' => [
                'name' => 'T2 Business Research and Development',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],

            // -- مرافق عامة / نقل / طيران ------------------------------
            'nwc' => [
                'name' => 'شركة المياه الوطنية',
                'type' => CompanyType::Government,
                'website' => 'https://www.nwc.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'se' => [
                'name' => 'الشركة السعودية للكهرباء',
                'type' => CompanyType::Private,
                'website' => 'https://www.se.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'saudia_airlines' => [
                'name' => 'الخطوط السعودية (SAUDIA)',
                'type' => CompanyType::Private,
                'website' => 'https://www.saudia.com/',
                'description' => null,
                'status' => 'approved',
            ],
            'saptco' => [
                'name' => 'الشركة السعودية للنقل الجماعي (سابتكو)',
                'type' => CompanyType::Private,
                'website' => 'https://saptco.com.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'ria' => [
                'name' => 'شركة مطارات الرياض',
                'type' => CompanyType::Private,
                'website' => 'https://www.riyadhairports.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            // mob removed — duplicate of mbc (شركة الحافلات الحديثة المحدودة)
            // gsac removed — duplicate of cars_syndicate (النقابة العامة للسيارات)
            'hayyak' => [
                'name' => 'شركة حياك للسفر والسياحة',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'nupco' => [
                'name' => 'الشركة الوطنية للشراء الموحد (نوبكو)',
                'type' => CompanyType::Government,
                'website' => 'https://www.nupco.com/',
                'description' => 'مزود موحد للمستلزمات الطبية للقطاع الصحي.',
                'status' => 'approved',
            ],
            'samref' => [
                'name' => 'شركة سامرف (SAMREF)',
                'type' => CompanyType::Private,
                'website' => 'https://www.samref.com.sa/',
                'description' => 'مصفاة أرامكو السعودية–موبيل في ينبع.',
                'status' => 'approved',
            ],
            'sultan_holding' => [
                'name' => 'شركة السلطان القابضة',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],

            // -- جمعيات / غير ربحي --------------------------------------
            'takaful_charity' => [
                'name' => 'جمعية تكافل الخيرية',
                'type' => CompanyType::NonProfit,
                'website' => 'https://takaful.org.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'tawasul' => [
                'name' => 'جمعية تواصل للتقنيات المساعدة',
                'type' => CompanyType::NonProfit,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'dawatuha' => [
                'name' => 'مؤسسة وقف دعوتها',
                'type' => CompanyType::NonProfit,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'kkf' => [
                'name' => 'مؤسسة الملك خالد',
                'type' => CompanyType::NonProfit,
                'website' => 'https://kkf.org.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'umqura_charity' => [
                'name' => 'جمعية أم القرى الخيرية النسائية',
                'type' => CompanyType::NonProfit,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],

            // -- أخرى / محلية ------------------------------------------
            'gsfmo' => [
                'name' => 'المؤسسة العامة للحبوب',
                'type' => CompanyType::Government,
                'website' => 'https://www.gsfmo.gov.sa/',
                'description' => null,
                'status' => 'approved',
            ],
            'elkaraj' => [
                'name' => 'الكراج',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'tatweer' => [
                'name' => 'شركة تطوير لتقنيات التعليم',
                'type' => CompanyType::Government,
                'website' => 'https://tetco.sa/',
                'description' => 'شركة تابعة لوزارة التعليم تقدم حلول تقنية لقطاع التعليم العام.',
                'status' => 'approved',
            ],
            'mbc' => [
                'name' => 'شركة الحافلات الحديثة المحدودة',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تشغيل نقل عام تقدم خدمات الحافلات وأنظمة النقل الذكية.',
                'status' => 'approved',
            ],
            'cars_syndicate' => [
                'name' => 'النقابة العامة للسيارات',
                'type' => CompanyType::NonProfit,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],

            // — File #7 new companies —
            'makkah_chamber' => [
                'name' => 'غرفة مكة المكرمة',
                'type' => CompanyType::NonProfit,
                'website' => 'https://makkahcci.org.sa/',
                'description' => 'غرفة تجارية تمثل القطاع الخاص في منطقة مكة المكرمة.',
                'status' => 'approved',
            ],
            'git_innovations' => [
                'name' => 'شركة الابتكارات العالمية لتقنية المعلومات',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية في وادي مكة متخصصة في تصميم الواجهات وتحليل النظم.',
                'status' => 'approved',
            ],
            'kaah_makkah' => [
                'name' => 'مستشفى الملك عبدالعزيز بمكة المكرمة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'مستشفى حكومي في حي الزاهر بمكة المكرمة.',
                'status' => 'approved',
            ],
            'eisar' => [
                'name' => 'شركة إيسار لتقنية المعلومات',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية في مكة المكرمة متخصصة في تطوير الويب والتطبيقات.',
                'status' => 'approved',
            ],
            'intercontinental_makkah' => [
                'name' => 'فندق دار التوحيد انتركونتننتال',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'فندق فاخر بجوار المسجد الحرام تابع لمجموعة IHG.',
                'status' => 'approved',
            ],
            'vision_experts' => [
                'name' => 'شركة رؤية الخبراء الاستشارية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة استشارية في وادي مكة تقدم معسكرات تدريبية في تحليل البيانات والرؤية الحاسوبية.',
                'status' => 'approved',
            ],
            'makkah_police' => [
                'name' => 'شرطة العاصمة المقدسة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'dsp_tech' => [
                'name' => 'Digital Solution Provider (DSP)',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة حلول رقمية مقرها برج الساعة بمكة المكرمة.',
                'status' => 'approved',
            ],
            'noor_hospital' => [
                'name' => 'مستشفى النور التخصصي',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'مستشفى خاص في مكة المكرمة.',
                'status' => 'approved',
            ],
            'holoul_tech' => [
                'name' => 'شركة حلول التقنية المتكاملة',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية في الزايدي بمكة المكرمة.',
                'status' => 'approved',
            ],

            // — File #4 new companies —
            'kamc_makkah' => [
                'name' => 'مدينة الملك عبدالله الطبية بمكة المكرمة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'مدينة طبية حكومية في مكة المكرمة.',
                'status' => 'approved',
            ],
            'maternity_makkah' => [
                'name' => 'مستشفى الولادة والأطفال بمكة المكرمة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'مستشفى حكومي متخصص في الولادة والأطفال بمكة المكرمة.',
                'status' => 'approved',
            ],
            'mutawifeen_africa' => [
                'name' => 'شركة مطوفي حجاج أفريقيا غير العربية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة خدمات حج وعمرة في مكة المكرمة.',
                'status' => 'approved',
            ],
            'awn_tech' => [
                'name' => 'جمعية عون التقنية',
                'type' => CompanyType::NonProfit,
                'website' => null,
                'description' => 'جمعية تقنية في وادي مكة تقدم تدريبات في تصميم الواجهات وتحليل النظم.',
                'status' => 'approved',
            ],
            'sfh_makkah' => [
                'name' => 'مستشفى قوى الأمن بمكة المكرمة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'مستشفى تابع لقوى الأمن الداخلي في مكة المكرمة.',
                'status' => 'approved',
            ],
            'first_city' => [
                'name' => 'المدينة الأولى',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية في وادي مكة.',
                'status' => 'approved',
            ],
            'ksau_hs' => [
                'name' => 'جامعة الملك سعود للعلوم الصحية',
                'type' => CompanyType::Government,
                'website' => 'https://www.ksau-hs.edu.sa/',
                'description' => 'جامعة حكومية متخصصة في العلوم الصحية.',
                'status' => 'approved',
            ],

            // — File #1 new companies —
            'techwin' => [
                'name' => 'تكوين TechWin',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية في حي الزاهر بمكة المكرمة متخصصة في تطوير المواقع والتطبيقات.',
                'status' => 'approved',
            ],
            'jeddah_amanah' => [
                'name' => 'أمانة محافظة جدة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'أمانة محافظة جدة.',
                'status' => 'approved',
            ],
            'four_points_makkah' => [
                'name' => 'فندق فور بوينتس شيراتون مكة النسيم',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'فندق تابع لمجموعة ماريوت في مكة المكرمة.',
                'status' => 'approved',
            ],
            'wajn_cyber' => [
                'name' => 'وجن للأمن السيبراني',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة أمن سيبراني في مكة المكرمة.',
                'status' => 'approved',
            ],
            'makkah_tc' => [
                'name' => 'الكلية التقنية بمكة المكرمة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'كلية تقنية تابعة للمؤسسة العامة للتدريب التقني والمهني في مكة المكرمة.',
                'status' => 'approved',
            ],
            'inpro_studio' => [
                'name' => 'Inpro Studio',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'استوديو تصميم وتطوير منتجات في حي النزهة بمكة المكرمة.',
                'status' => 'approved',
            ],
            // — File #8 new companies —
            'public_prosecution' => [
                'name' => 'النيابة العامة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'الجهة المختصة بالتحقيق والادعاء العام في المملكة العربية السعودية.',
                'status' => 'approved',
            ],
            'lucidya' => [
                'name' => 'Lucidya',
                'type' => CompanyType::Private,
                'website' => 'https://lucidya.com/',
                'description' => 'شركة تقنية سعودية متخصصة في تحليل بيانات وسائل التواصل الاجتماعي بالذكاء الاصطناعي.',
                'status' => 'approved',
            ],
            'rukaya' => [
                'name' => 'ركايا للاستشارات الادارية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة استشارات تقنية وادارية في مكة المكرمة.',
                'status' => 'approved',
            ],
            'knowledgex' => [
                'name' => 'Knowledgex',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية في مكة المكرمة.',
                'status' => 'approved',
            ],
            'nmc_weather' => [
                'name' => 'المركز الوطني للأرصاد',
                'type' => CompanyType::Government,
                'website' => 'https://ncm.gov.sa/',
                'description' => 'المركز الوطني للأرصاد المسؤول عن التنبؤات الجوية في المملكة.',
                'status' => 'approved',
            ],
            'qanoniah' => [
                'name' => 'قانونية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة تقنية قانونية في مكة المكرمة.',
                'status' => 'approved',
            ],
            'isolution' => [
                'name' => 'اختراع الحلول للتقنية iSolution',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'شركة حلول تقنية في جدة.',
                'status' => 'approved',
            ],
            'education_admin_makkah' => [
                'name' => 'ادارة التعليم بمنطقة مكة المكرمة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'ادارة التعليم الحكومية في منطقة مكة المكرمة.',
                'status' => 'approved',
            ],
            'laith_hospital' => [
                'name' => 'مستشفى الليث العام',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'مستشفى حكومي في محافظة الليث.',
                'status' => 'approved',
            ],
            'haramain_authority' => [
                'name' => 'هيئة العناية بشؤون الحرمين الشريفين',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'هيئة حكومية مسؤولة عن العناية بشؤون المسجد الحرام والمسجد النبوي.',
                'status' => 'approved',
            ],
            'holiday_inn_makkah' => [
                'name' => 'فندق هوليداي ان مكة العزيزية',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => 'فندق تابع لمجموعة IHG في مكة المكرمة.',
                'status' => 'approved',
            ],
            'jeddah_tc' => [
                'name' => 'الكلية التقنية بجدة',
                'type' => CompanyType::Government,
                'website' => null,
                'description' => 'كلية تقنية تابعة للمؤسسة العامة للتدريب التقني والمهني في جدة.',
                'status' => 'approved',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ratings(): array
    {
        return [
            // File #2 — تجارب التدريب - كليه الحاسب - Original Form responses.csv
            [
                'company' => 'kacst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'كتبت تجربتي في علم وكاكست هنا: https://caramellaapp.com/raghad/amkYhUcr8/mabad-alsnh-alauwla',
            ], // file#2:4
            [
                'company' => 'momra',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'التدريب كان تعاقد من شركة تقنية، خطة التدريب ما اتبعوه، لان تدريبهم يكون لوظائفهم مو عن تدريب الجامعة نفسها.',
            ], // file#2:44
            [
                'company' => 'riyada',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تدربت مع الابتكار الناس مثلا عندهم فكره برنامج واحنا نسويها وانجزنا iOS application انصح بالتدريب فيها لاني تعلمت اشياء فيها كان ممكن ما اتعلمها ابدا',
            ], // file#2:45
            [
                'company' => 'kacst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Machine learning - Data Analysis- Dashboards design',
            ], // file#2:46
            [
                'company' => 'elm',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'تعلم تطوير أندرويد, القدرة على بناء تطبيق بشكل مستقل',
                'cons' => 'لا يوجد احتكاك مع موظفين علم, التدريب كورس خارجي فقط, لا يوجد خبرة ببيئة العمل',
                'review_text' => 'التدريب كان اشبه بكورس اندرويد من قبل مدربة خارجيه (ليست من موظفين علم)، لا يوجد عمل خاص لعلم ولم يكن هنالك احتكاك او تواصل مع موظفين علم، رح تطلعين وانت فاهم الاندرويد وتقدرين تطورين تطبيق لحالك بس لا يوجد خبر من بيئة العمل. غرفة المكتب للمتدربات فقط ولكن موجوده في قسم الرجال، لعدم وجود مكان في قسم النساء يكفي عدد المتدربات.',
            ], // file#2:47
            [
                'company' => 'etec',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'مبرمجه، التيم الي معي البنات كانو توهم خبرتهم سنه تقريبا اما تيم الرجال خبرتهم عاليه فوق ال١٦ سنه في هالمجال. تدربت في قسم تقنيه المعلومات كان التيم متعاون وتعلمنا من بعض كثير.',
            ], // file#2:48
            [
                'company' => 'lean',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'pros' => 'اختيار التقنيات بنفسك, استخدام GitHub, تنوع المهام بين تطوير وجودة, التعامل مع عملاء',
                'review_text' => 'كان تدريبي تدريب تعاوني كمطور تطبيقات، تدربت بـ technology team، التيم اللي معاي بعضهم متدربين من نفس التخصص وأغلبهم موظفين، بداية تدريبي كان كله بال infrastructure and Unix command (وجدًا تغير تفكيري بعده كان مشاريع بسيطه زي المهمات يبون انجزها بس ما كانت لها دخل بمشاريعهم هم كان مجهزين هذه المهام لي). اغلب تدريبي كان بال development team على تقنيات أنا اخترتها (React.js, React Native, Android studio). ثم سويت معاهم مشروعين عليها كلها تقريبًا. من الأشياء اللي حبيتها بالفريق خلوني التزم ب GitHub. بنص التدريب أخذت أسبوع ونص تقريبًا مع ال quality team واشتغلت فيه على مشروع وسوينا تقرير على بعض ال bugs وال design errors. أيضًا أتيح لي الفرصة ان أقرا ال BRD وأتعامل مع customers. كانت تجربة تستحق الذكر صراحة.',
            ], // file#2:49
            [
                'company' => 'itcl',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 2, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'تعلم ذاتي فقط, لا يوجد احتكاك بالشركة, لا مشاريع حقيقية',
                'review_text' => 'المشاريع كانت من افكارنا وسويناها من انفسنا، ما اشتغلنا ابدا مع الشركه ولا احتكينا فيهم بس عرضوا لنا باقي التخصصات كميتنق. لا انصح فيها ابدا للاسف كانت عباره عن سيلف ليرننق ومن انفسنا عشان نفيد انفسنا فقط.',
            ], // file#2:50
            [
                'company' => 'kauh',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'tester, programmer. we reprogram system. لا انصح بالجهه لان لا يوجد مشرف والعمل عشوائي.',
            ], // file#2:51
            [
                'company' => 'tahakom',
                'role_title' => 'Front-End Intern',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'Front-End Intern. كان شغلي مع Front-End Developers ومن احد المشاريع اللي اشتغلت عليها كان مشروع جانبي سويته بنفسي عشان اتدرب على Angular بحكم ان الشركة كان شغلهم على Angular. وأيضاً كان مطلوب مني اني اضيف feature لاحد المشاريع تبع الشركة وكانت فكرة الfeature هي اني اغيّر الlayout تبع الموقع في حالة ان اللغة كانت عربي وبرضو كان شغلي اني اترجم الصفحات.',
            ], // file#2:52
            [
                'company' => 'tamkeen',
                'role_title' => 'Front-End Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1800, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'front-end developer. الأختلاط في الإجتماعات فقط اثناء العمل الرجال والنساء مفصولين.',
            ], // file#2:54 (file#2:57 identical duplicate — skipped)
            [
                'company' => 'es_consulting',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'كنت اقوم بدور شبيه بدور مساعد المستشار في القسم الخاص بإدارة اجراءات العمل BPM. كان فيه متدرب معي و المستشار اللي يشرف علينا و يوكل لنا المهمات. الأشياء اللي اشتغلت عليها شخصياً بسيطة. كانت عبارة عن مشروعين بسيطين فقط. حالتي نوعاً ما مختلفة. كان المفروض انشب في المدرب من اول يوم باتصالات و رسائل بسبب انهم منضغطين لكثرة المشاريع. فممكن يواعدك في يوم معين و ينساك تماماً بسبب انشغاله. نصيحتي تدرب عندهم اذا صرت خريج لانهم يعرضون عليك وظيفه.',
            ], // file#2:55
            [
                'company' => 'dr_arab_center',
                'role_title' => 'باحثة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'باحثه. لا انصح فيه ابداً.',
            ], // file#2:58
            [
                'company' => 'elm',
                'role_title' => 'Android Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اندرويد ديفلوبر. البيئة مختلطة ولكن يوجد قسم نسائي واغلب الوقت مع نساء كذلك.',
            ], // file#2:59
            [
                'company' => 'fixtag',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'اشتغلت على تصميم واجهات و تجربة المستخدم و تحليل نظم لموقع طاقات، و اشتغلت على اختبار الجودة (quality assurance) لنظم تخطيط موارد مؤسسات (كانوا نظامين واحد لهيئة الملكية الفكرية و الثاني للجنة تطوير البيئة النظيفة).',
            ], // file#2:60
            [
                'company' => 'thiqah',
                'role_title' => 'Project Manager',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'Project manager, delivery department, it was a perfect and cooperating team.',
            ], // file#2:61
            [
                'company' => 'mng',
                'role_title' => 'Web Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اشتغلت ويب ديفلوبر في المجالين الويبسايتز والابلكيشن بفريمورك الasp.net والموبايل ionic.',
            ], // file#2:62
            [
                'company' => 'thiqah',
                'role_title' => 'Business Development',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'Business development, we used to work on business cases, feasibility studies, benchmarks. It was more business than IT. You don\'t have to work on the same department you trained at, it takes time to apply and for them to process your application and interview you and come up with a training plan so don\'t stress, everyone will do the training :)',
            ], // file#2:63
            [
                'company' => 'elm',
                'role_title' => 'Android Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مبرمج أندرويد.',
            ], // file#2:64
            [
                'company' => 'elm',
                'role_title' => 'Android Mobile Application Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 4, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'القسم: علم سوفت. المسمى الوظيفي: android mobile application developer. المدرب: الاء اغا كرس (CTO في شركة Superior ICT) من خارج الشركة. المشاريع: Elm survey system. مشروع من اختيارنا: ديمو فقط. البيئة مختلطه في الممرات و قاعات الاجتماعات لكن المتدربات في مكتب خاص مع مدربات.',
            ], // file#2:65
            [
                'company' => 'thiqah',
                'department' => 'Information Security Governance',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 5,
                'review_text' => 'part of the information security department / Information security governance. I worked on the organization\'s policies, awareness sessions and everything that included in the governance.',
            ], // file#2:70
            [
                'company' => 'kauh',
                'role_title' => 'مبرمج',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'مبرمج، اشتغلنا على جزء من برنامج المستشفى esihi. الجهة لأسباب مجهولة ما كانت مهتمة بالمتدربين وتوجيههم فالتعليم كان ذاتي بدون أي توجيه ولا نصح.',
            ], // file#2:73
            [
                'company' => 'hrsd',
                'department' => 'ادارة تقنية المعلومات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'لايوجد مسمى وظيفي، طبيعة التدريب كانت مهام اسبوعية متفرقة بسيطة جدا مهام تتعلق في فريق الجودة، التطوير، الدعم الفني، الموارد البشرية، المهام كانت بعيدة عن عمل الوزارة، الصراحة ما استفدت منهم ومن الموظفات شيء يذكر + لايوجد مكان مجهز للمتدربات.',
            ], // file#2:74
            [
                'company' => 'dell_emc',
                'department' => 'Support',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'موظفين متعاونين, تطوير السيرة الذاتية ومهارات المقابلة, تنمية السوفت سكيلز, شهادة نهاية التدريب',
                'cons' => 'أغلب التدريب نظري وجلسات, تطبيق عملي قليل',
                'review_text' => 'كان بقسم support. اغلب التدريب كان عبارة عن sessions يعلمونا اكثر من اننا نطبق. عرضوا علينا Demo لشغلهم كيف يصير، شرحوا لنا عن المسميات الوظيفية ايش يشتغلون، شرحوا عن الستورجز والسيرفيرز، عطونا كورس اسمه ISM بس الشهاده على الكورس احنا ناخذها بأنفسنا اذا اجتزنا الاختبار. كان فيه كثير موظفين صغار لان عندهم برنامج توظيف لحديثين التخرج، الموظفين جدا متعاونين اذا كان عندك استفسار عن اي شي يساعدونك، علمونا كيف نخلي السيره الذاتيه كويسه وشافوا السير الذاتية حقتنا وعطونا نصايح وعلمونا كيف نجاوب على اسئلة المقابلة الوظيفية. هدفهم كان انهم ينمون السوفت سكيلز فسوينا تقريبا ٣ برزنتيشنز، سوينا مشروع بروجكت مانجمينت. كان فيه شهادة نهاية التدريب.',
            ], // file#2:75
            [
                'company' => 'stc_solutions',
                'role_title' => 'Pre-sales Engineer',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'Pre-sales Engineer. المكافأة تختلف باختلاف عدد المتدربين واختلاف الفترة التدريبية. بالنسبة للعرض الوظيفي هو غير اساسي و حالاته فردية (حاولو تبرزون نفسكم عشان تحصلون عليه). البيئة جدًا مختلطة و بعض المكاتب مشتركة مع الرجال (مع ملاحظه وجود مكاتب نسائية). العمل يطلب لغة إنجليزية كويسة لأنه فيه شريحة كبيرة من الموظفين أجانب (غير ناطقين بالعربية).',
            ], // file#2:76
            [
                'company' => 'safe_decision',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'pros' => 'شغل حقيقي بالسايبر, كورسات مكثفة, دوام مرن, بيئة متعاونة, اجتماعات مع CEO',
                'review_text' => 'تدريبي كان عن Cybersecurity. الخطة كانت تدريب على كل أقسام الشركة بس بتدريبنا كان التركيز على المعمل ومركز العمليات الأمنية بالشركة. بالبداية أخذنا كورسات مكثفة بالنتورك والسكيوريتي بعدها بدأنا العمل على شبكة خاصة فينا سوينا لها installing and configuring وجربنا نشوف الـlogs. شاركنا بـ incident response كان attack على جهة وكنا نحاول نفهم طريقتهم وحاولنا نوقفهم وسوينا تقرير عنه. اشتغلنا على Miter-Lab. دخلت الـSOC وهو مكان يراقبون فيه اللوقز والاتتاكس ويسوون لها تحليل وريسبونز. التدريب كان رائع وما أذكر بيوم كنت فاضية وماعندي شغلة. كل يوم إنجاز جديد، معلومات جديدة. الكل متعاون ويقدم لك المساعدة متى ما احتجت. وكان فيه ميتنقز اسبوعية مع الـCEO. الدوام مرن.',
            ], // file#2:80
            [
                'company' => 'hrsd',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'الخطة لم تُطبق, لا يوجد مشروع حقيقي, شغل فعلي ساعة واحدة فقط باليوم',
                'review_text' => 'اذا كان على الخطة اللي عطوناياها كانت ممتازة و فيها اشياء حلوة تتعلمين عليها و تعطيك خبرة جيدة لكن الحقيقة طلع كلشي عكس المتوقع ما جهزو ولا شي و كل واحد يعطيك رؤوس اقلام عن الخطة و يمشي لدرجة ان الدوام ما نشتغل فيه الا بالكثير ساعة وحدة. قسم تقنية المعلومات وما كان فيه مشروع معين نشتغل عليه.',
            ], // file#2:81
            [
                'company' => 'seu',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => '4 اقسام (البنيه التحتيه - البرمجيات - الشبكات - الدعم الفني) اشتغلنا على مشروع (تطبيق للجامعه). ما كان فيه شغل عملي كثير اغلب التدريب كان شرح.',
            ], // file#2:82
            [
                'company' => 'quant',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'تحليل البيانات، خبرة التيم علم بيانات وتحليل البيانات. انصح لمن تخصصهم تقنية المعلومات مسار علم البيانات.',
            ], // file#2:83
            [
                'company' => 'kfshrc',
                'role_title' => 'مساعد باحث',
                'department' => 'الحوسبه العلميه',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مساعد باحث، الحوسبه العلميه، بنات من جامعة الملك سعود، انشاء تطبيقات تفيد اقسام المستشفى.',
            ], // file#2:84
            [
                'company' => 'aec',
                'department' => 'الهندسة والتطوير',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2555, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم الهندسة والتطوير.',
            ], // file#2:85
            [
                'company' => 'ebox',
                'role_title' => 'Web Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Web developer.',
            ], // file#2:86
            [
                'company' => 'riyadh_amanah',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'كنا ١٧ طالبة في التدريب، قسمونا ل٣ مجموعات كل مجموعة فيها ٥-٦ طالبات. كل مجموعة وكلت بمشروع تسويه وكنا مخيرين بين الويب سايت او آب. إنا اخترنا الويب سايت وبديناه من الصفر وفي الأخير عرضناه لإدارة الأمانة. مره استمتعت في تدريبي البنات كانو مره لطيفات والمشرفة كانت تساعدنا قدر الإمكان.',
            ], // file#2:87
            [
                'company' => 'aec',
                'department' => 'E&D',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'تدربت في قسم ال E&D. النساء لهم مكان مخصص ولكن يكون هناك عمل مشترك مع الرجال.',
            ], // file#2:90
            [
                'company' => 'gccmc',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'متدربه - ماكان عندهم برنامج للتدريب فالمهام كانت مره بيسك وماتعلمت شي جديد.',
            ], // file#2:91
            [
                'company' => 'ksu_finance',
                'role_title' => 'مبرمجة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 2, 'rating_real_work' => 3, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'المسمى الوظيفي: مبرمجة. الادارة المالية-قسم التدقيق. فريق مكون من ٥ طالبات. برمجة تطبيق على سطح المكتب يقوم بحساب الغرامات تبع الجامعة/المستشفيات. اعتقد بأن التدريب يصبح افضل في بيئة يوجد فيها مشرف تقني - الادارة المالية لا يوجد فيها اي مشرف تقني.',
            ], // file#2:92
            [
                'company' => 'saip',
                'role_title' => 'Business Analyst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Business analyst.',
            ], // file#2:93
            [
                'company' => 'lean_node',
                'role_title' => 'System Analyst',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'محلل نظم/System analyst. في قسم الهندسة. خبرتهم ٤ سنين في السوق والمشاريع التي دخلوا فيها كثير. هدفهم الأساسي يبنون أفكار العملاء الستارت اب ليكون تطبيق/موقع. وعندهم بعض المشاريع مع الجهات الحكومية. يستخدمون إطار Scrum في إدارة المشاريع. أحدث التقنيات يستخدمونها في تطوير البرامج (React.js, React-native, Node.js).',
            ], // file#2:94
            [
                'company' => 'ksu_dect',
                'role_title' => 'Software Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تدربت كمطور برمجيات في قسم البوابة الرقميه. اقترح علينا فكرة مشروع Augmented Reality بحيث يكون بلاتفورم تحفظ فيه الaugmented reality experiences في داتابيس. التقنيات اللي تدربت عليها كانت AR و java spring boot و رياكت. جهزت باك اند للمشروع باستخدام جافا سبرنق بوت وفرونت اند باستخدام react و client على ios بسويفت.',
            ], // file#2:95
            [
                'company' => 'stc',
                'department' => 'قطاع التطبيقات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تدربنا في قطاع التطبيقات بالخصوص على نظام يسمى CRM هذا النظام مهم واساسه من اوراكل بس تم تطويره من قبل الاتصالات. اشتغلنا مع مطورين للنظام وبرضو tester وتعمقنا بالتيستينق كثير. قطاع الاتصالات عميق ومتشعب وفعلاً كانت تجربه حلوه. المشرفات كانو قمه بالأخلاق والتعامل كانو يعطونا من قلب وماقصرو معنا بشي.',
            ], // file#2:96
            [
                'company' => 'amaz',
                'role_title' => 'Web Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'المسمى الوظيفي: web developer وتدربت تحت قسم الـ IT وكان صدق تدريب ممتع ومثري جدًا، ماكنت ماخذه كورس الويب وطلعت من التدريب وانا اقدر اسوي مواقع بنفسي. المشاريع اللي اشتغلت عليها متنوعه فيه موقع سويناه من الصفر وفيه مواقع حسناها، ككل البيئه ممتعه والخبرة عاليه.',
            ], // file#2:97
            [
                'company' => 'osloob',
                'role_title' => 'مهندس برمجيات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1700, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'عرض وظيفي بعد التدريب',
                'cons' => 'لا يوجد تنظيم, تعلم ذاتي بدون شرح مفصل, المهام ليست على المسمى الوظيفي',
                'review_text' => 'مهندس برمجيات. شركة صغيرة مره (startup) الشركة مب ذاك التنظيم فالتاسكات متساهلين. بالنسبة للي راح تتعلمه سواء برمجة او اساليب ما راح ينشرح لك بشكل مفصل يمكن كذا ابو دقيقتين مقدمة عنه لكن بيعطونك اسمه ويقولك دور بالنت وتعلمه. ما فيه تنظيم بالاشياء اللي راح تتعلمها وما راح تكون على مسماك الوظيفي. عرض علي وظيفة بعد التدريب مهندس برمجيات راتب ٥٠٠٠.',
            ], // file#2:98
            [
                'company' => 'tatweer',
                'role_title' => 'مطور برمجيات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مطور برمجيات، قسم التطبيقات، سوينا مشروعين عن طريق التواصل مع api.',
            ], // file#2:99
            [
                'company' => 'riyadh_amanah',
                'role_title' => 'System Analyst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'سيستم اناليست - كان الوضع عازلين بنات التدريب بمبنى لحاله، كنا قروب خمس بنات ومعطينا مشروع خربوطي لويب سايت على مدى الشهرين نسويه مع حملة توعوية. صراحة اللي يبي شغل صح وتدريب على مستوى لا يفكر فيها للأمانة تحسفت مره اني تدربت فيها.',
            ], // file#2:100
            [
                'company' => 'ksu_dect',
                'role_title' => 'مطور',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مطور في عمادة التعاملات الإلكترونية. عمل مريح وطورنا موقع للتسجيل في النادي الرياضي.',
            ], // file#2:102
            [
                'company' => 'aec',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدربت فيه اكثر من قسم.',
            ], // file#2:103
            [
                'company' => 'cisco',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'حصلت دورات ccna.',
            ], // file#2:104 (file#2:105 byte-identical duplicate — skipped)
            [
                'company' => 'tcc',
                'role_title' => 'Full Stack Intern',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'المسمى: Full stack Intern. الي بيتدرب انصحه ياخذ التدريب اخر مستوى.',
            ], // file#2:106
            [
                'company' => 'elm',
                'role_title' => 'مطورة تطبيقات جوال أندرويد',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'قسم علم سوفت - متدربة مطورة تطبيقات جوال أندرويد. الشركة اختصاصها امن المعلومات والحلول الرقمية. عملنا على مشروعين تطبيق جوال وكان فيه برزنتيشن. بيئة العمل محفزة على التعلم والتطوير طورنا تطبيقات الجوال من الصفر ووصلنا لمستوى متقدم. التدريب لا يتطلب خبرة مسبقة في الاندرويد فقط معرفة بالجافا وبالبرمجة الكائنية. كانوا يقيمون لنا دورتين او جلسات كل اسبوع لمواضيع مختلفة جداً انصح بالتدريب فيها.',
            ], // file#2:107
            [
                'company' => 'gsfmo',
                'role_title' => 'User Support',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'المسمى الوظيفي: user support. التيم عاديين السعوديين اللي موجودين وفيه هنود كثير كويسين الصراحة بس ما يشاركون كثير من معلوماتهم يخافون احد يمسك منصبهم. البيئة عادية والشغل عادي واقل من عادي. مافيه مشاريع تذكر ماكانوا يدخلوني بكثير من المشاريع بخصوص انها جهة حكومية ومعلوماتها تعتبر سرية. بشكل عام جيدة الى اقل من جيدة التجربة.',
            ], // file#2:108
            [
                'company' => 'mobily',
                'department' => 'البنية التحتية، العمليات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'قسم البنية التحتية، العمليات. التجربة كانت ممتازة جداً لكن بما أن الشركة كبيرة فهم يتعاقدون مع الشركات لإنجاز الأعمال وهم يديرونهم فقط فمن يبحث عن فائدة عبر الممارسة فالشركات الكبيرة خيار غير مناسب تماماً له.',
            ], // file#2:110
            [
                'company' => 'sabic',
                'role_title' => 'Business Analyst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'Business analyst.',
            ], // file#2:112
            [
                'company' => 'elm',
                'role_title' => 'مطورة أندرويد',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'تعلم أندرويد, مشروع تطبيقي',
                'cons' => 'لا يوجد احتكاك مع الموظفين, الخطة التدريبية لم تطبق, التدريب يشبه الجامعة',
                'review_text' => 'تدربنا كمطورين اندرويد كنا خمسين بنت وكان عندنا مدربه تعلمنا على الاندرويد وسوينا بالنهاية مشروع خاص فينا. للاسف ماكان فيه اي احتكاك مع الموظفين غير السيشنز اللي مره كل اسبوع. بالخطه التدريبيه كانوا كاتبين اننا بنحتك مع الموظفين وكنا متوقعين نكون نفس السنوات اللي قبل بس للاسف كان نفس الجامعه. الشركة حلوه لكن ماكانت مثل ماتوقعت.',
            ], // file#2:113
            [
                'company' => 'kkf',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'موظفين متعاونين وطيبين, بيئة مريحة',
                'cons' => 'أعمال إدارية فقط, لا يوجد عمل تقني, لا استجابة لطلب التغيير',
                'review_text' => 'في البداية بالمقابلة قالو لي اني راح اتدرب معهم لتطوير الموقع بحكم خبرتي فيه وانهم ممكن بعدها يدخلوني في مهام تقنية اخرى. قبلت وكنت متحمسه لكن الصدمه انهم خلوني طوال الشهرين اسوي اعمال ادارية بحته مثل عمل تقارير وعروض واصمم بوسترز. طلبت منهم اني اشتغل مع القسم التقني ولكن ماكان فيه استجابة. الموظفين بشكل عام متعاونين وطيبين جداً والبيئة مريحة لكن مساحة ان الطالب التقني يبدع عندهم ويتعلم اشياء جديدة جداً ضعيفة. لا انصح طلاب التقنية بالتدريب عندهم.',
            ], // file#2:115
            [
                'company' => 'thiqah',
                'department' => 'أمن المعلومات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'كنت متدربه في قسم أمن المعلومات والتيم كامل كان شباب. خبراتهم جداً كانت عاليه ومتعاونين ويساعدون بكل الطرق الممكنه. اشتغلت على اكثر من شي وللاسف ماعندي صلاحيه اني اتكلم عنها بس كانت اشياء جداً تعطي خبره. البيئه جداً جداً مختلطه يعني اللي عندها حدود في الاختلاط مع الرجال ماراح تقدر تتأقلم لكن الكل بلا تخصيص كانو محترمين جداً والبيئه كويسه كبيئة عمل ومريحه.',
            ], // file#2:117
            [
                'company' => 'alinma',
                'department' => 'ادارة التعليم والتدريب',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'تعامل سيء مع المتدرب, لا جهاز ولا إيميل, التيم بعيد عن التخصص التقني',
                'review_text' => 'كان منسق تدريب مسؤول عن البيانات لكل موظف بقسم ادارة التعليم والتدريب. نسقنا وخططنا لبرنامج تطوير الخريجين برنامج القوي الامين الرابع. التيم خبرته كانت كلها عن التخطيط والعمليات. التعامل كان معي كمتدرب سيء وحتى ابسط حقوقي مثل جهاز شخصي او ايميل ماعطوني.',
            ], // file#2:118
            [
                'company' => 'habib_hospital',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'غير مستعدين للمتدربين, لا خطة واضحة, لا أجهزة أو صلاحيات, تدريب نظري فقط, مجرد مشاهدة',
                'review_text' => 'تدربت في مستشفى الحبيب كان معي خمس بنات وحطونا بقسم شركة مشغله لهم اللي هي حلول السحابة. القسم كان عبارة عن دعم فني عن طريق الرد على اتصالات الموظفين ومعرفة المشاكل اللي تواجههم وحلها. مااعطونا يوزرات او اجهزة بحيث نرد على الاتصالات. كنا مجرد نجلس بجانب الموظفين ونشوفهم يشتغلون. اللي يبي يستفيد من التدريب ما انصحه يتدرب عندهم لاسباب كثيرة: غير مستعدين للمتدربين وماعندهم خطه واضحة للتدريب، بتشوفينهم يشتغلون على نظام خاص بالمستشفى يعني حتى ماراح تاخذين خبرة. تدريبهم نظري يعني ماراح يكون فيه تطبيق عملي.',
            ], // file#2:119
            [
                'company' => 'rasmal',
                'duration_months' => 4, 'modality' => 'remote',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'كانوا Start-up يوم كنت اشتغل معهم والصراحة ما كان شغلهم احترافي أبد. كان الشغل عن بعد.',
            ], // file#2:120
            [
                'company' => 'takaful_charity',
                'role_title' => 'Data Analysis',
                'duration_months' => 2, 'modality' => 'remote',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Data Analysis. تدربت في قسم البيانات. الفريق الي معي كان مكون من ثلاث اشخاص دكتور واثنين سينيور. ما اشتغلت على مشروع معين التاسكات اللي كانت تجيني تعتمد على احتياج الشركه. بشكل عام تجربتي كانت متوسطه بحكم ان الشغل كان اون لاين فمادخلت مع التيم بشكل ممتاز. الاشخاص الموجودين كانوا جدا متساعدين. تجربتي يمكن تكون مختلفة تماما بحكم اني تدربت اون لاين بسبب كورونا.',
            ], // file#2:121
            [
                'company' => 'smart_methods',
                'department' => 'IOT',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدربت بقسم ال IOT وكمان بمسارات متعدده بالهندسه هي شركه مختصه بصناعة الروبوتات وبرمجتها التدريب مفيد جدا تعلمت اشياء كثير الي مايشتغل ما انصحه بذا المكان.',
            ], // file#2:123
            [
                'company' => 'ncgr',
                'role_title' => 'Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'Developer - Build API Project at ASP.NetCore + Create Database + web pages + validation + Testing + business analysis and design. البيئة رائعه، طاقم العمل رائع جدا معطائين ويهتمون ان الطالب يعمل ويتعلم باكبر طريقه ممكنه.',
            ], // file#2:124
            [
                'company' => 'kacst',
                'department' => 'المركز الوطني لتقنية الروبوت وانترنت الاشياء',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'تدربت في المركز الوطني لتقنية الروبوت وانترنت الاشياء. مدة التدريب كانت ٣ شهور. المبنى كان عادي جداً وصغير. اول اسبوع كان تعريفي بالمركز. ارسلوا لنا عدة مشاريع وقالوا اختاروا اللي تبون عشان راح تشتغلون عليها طول الفترة. المشروع حقي كان انشاء موقع وربطه بقاعدة بيانات باستخدام جافا سكربت و sql server. كل اسبوع يتجمع معنا المشرف ويشوف ايش وصلنا له. خلال الفترة هذي كانوا يسوون لنا ورش عمل. تجربتي كانت زينه بس احس ماستفدت اشياء كثيره تخص سوق العمل يعني الوضع اقرب ماله جامعة.',
            ], // file#2:125
            [
                'company' => 'tawasul',
                'department' => 'المشاريع التقنية',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'قسم المشاريع التقنية. اشتغلنا على تطوير تطبيق يخدم الناس اللي عندهم صعوبات أو عدم قدرة في النطق والكلام. مسكنا تحليل النظام وكان الشغل أقرب ما يكون لهندسة البرمجيات واشتغلنا كتيم وكان علينا مشرفة. اخر شي مسكوا كل بنت مهمة تخدم التطبيق. مهمتي كانت برمجية والتجربة كانت لطيفة ومفيدة.',
            ], // file#2:130
            [
                'company' => 'dawatuha',
                'role_title' => 'اخصائية تقنية',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اخصائية تقنيه بس التعامل كله كان مع قواعد البيانات الخاصه بالشركه وانشاء قاعدة بيانات جديدة.',
            ], // file#2:131
            [
                'company' => 'aramco',
                'department' => 'نقل المعرفة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'بيئة عمل جميلة ومتنوعة, خطة تدريب بكورسات',
                'cons' => 'لا مشاريع حقيقية بسبب سرية المعلومات, فائدة عملية محدودة',
                'review_text' => 'تدربت في فريق التعلم التعاوني والتقنية يتبع لقسم نقل المعرفة. ما راح تشتغل على مشاريع حقيقية بحكم حساسية المعلومات وكونك مجرد متدرب. طريقتهم انهم يبنون لك خطة تدريب بكورسات من عند نظام التعلم عندهم وممكن تشتغل على مشروع جانبي مع المشرف. لا أنصح بالتدرب مع أرامكو لمن يطمع بالفائدة العملية والتطوير المهني. انصحك فيها كتجربة لتطلع على بيئة عمل جداً جميلة ومتنوعة بشكل كبير.',
            ], // file#2:132
            [
                'company' => 'kauh',
                'role_title' => 'Web Developer',
                'department' => 'إدارة تقنية المعلومات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'شغل على مشاريع حقيقية, تيم لطيف',
                'cons' => 'التيم مشغول ودعم المتدربين قليل, شغل فوضوي, الخطة التدريبية لم تطبق',
                'review_text' => 'تدربت في قسم إدارة تقنية المعلومات. كنت ماسكة أشغال web development. التيم لطيف لكنه مشغول أغلب الوقت فما فيه ذاك الدعم الكافي للمتدربين. كانوا يعطونني شغل على بروجكتس حقيقية عندهم. عيبهم بس إن شغلهم فوضوي ومشرفتي خططت لي خطة جدًا ممتازة لكن للأسف ما طبقتها.',
            ], // file#2:138
            [
                'company' => 'kacst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اشتغلنا فرونت اند انهانسمنت لمشروع هم مسوينه وشبه منتهي لكن باقي الفرونت اند يبيله تحسين وحل بعض البقز. خلونا ندخل في القيت لاب حقهم ونسوي بش وبل. كنا نعرض شغلنا اسبوعيًا. مكان كويس للي ما يطمح انه يتعلم كثير بالتدريب. البيئة مختلطه لكن اختلاط خفيف جدًا.',
            ], // file#2:140
            [
                'company' => 'anb',
                'department' => 'Information Security',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'Information Security department. الاداره كلها جديده والستاف صغار وكلهم فاهمين شغلهم. البيئه جدا جدا مريحه. اشتغلت على data privacy and protection. كنا مركزين أكثر شي على classification للتطبيقات والملفات.',
            ], // file#2:141
            [
                'company' => 'sdaia',
                'department' => 'Research Center',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت بقسم ال research center واشتغلت على مشاريع NLP وكل الموظفين يجننون ويبون يعلمونك كلشي يقدرون عليه. انصح اي احد مهتم بالذكاء الاصطناعي يتدرب عندهم.',
            ], // file#2:147
            [
                'company' => 'masarat',
                'role_title' => 'مطورة ويب',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 2, 'rating_organization' => 3,
                'pros' => 'شغل على مشاريع حقيقية, تعلم .NET, دوام مرن',
                'cons' => 'لا يوجد عمل جماعي, شركة صغيرة جداً',
                'review_text' => 'كنت مطورة ويب. مامعي احد بس انا والمبرمج حقهم. خلوني اشتغل على موقعين للشركة. الشركة مره صغيره. تعلمت على .net واستفدت ومرنين بساعات العمل مايدققون علي ابد. لكن اللي يبحث عن تكوين علاقات وعمل كفريق ماتصلح له.',
            ], // file#2:150
            [
                'company' => 'ria',
                'role_title' => 'Cybersecurity',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 2, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'Cybersecurity. الشركة كأسم حلوه بس ما كان في شغل ابد اضطريت اشتغل من نفسي. وقت الخروج والطلعة سهالات فلكسبل كانو.',
            ], // file#2:152
            [
                'company' => 'sabb',
                'role_title' => 'Security Officer',
                'department' => 'Information Security Risk',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'المسمى كان Security Officer. تدربت في قسم information security risk تحديدا Third Party.',
            ], // file#2:153
            [
                'company' => 'mcit',
                'department' => 'الإدارة العامة للأمن السيبراني',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'تدربت في الإدارة العامة للأمن السيبراني. خطتهم رائعة طريقة تدريبهم rotation بين SOC and GRC شهر هنا وشهر هناك واذا تبين شهر زياده تختارين اي قسم تتدربين فيه مع التيم. كانت فرصه عظيمه بالنسبه لي وانصح اي احد يتدرب عندهم لانهم ماياخذون اعداد كبيره اقصى عدد ٣ متدربين ويعطونك حقك واكثر. التيم متعاونين جدا الجميع بلا استثناء يحاول يساعدك ويفيدك باللي عنده وبيئتهم حلوه مره.',
            ], // file#2:154
            [
                'company' => 'sfda',
                'department' => 'ادارة الأمن السيبراني',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم ادارة الامن السيبراني. تقريبا اطلعت على اغلب شغلهم وعملت على قليل منه. التدريب كأنه دراسه اكثر من انه شغل وغالبا يجب الركض خلفهم.',
            ], // file#2:156
            [
                'company' => 'ksu_network',
                'role_title' => 'مهندس شبكات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'مهندس شبكات. عملت ميدانيا في قسم الشبكة بالعمادة مع الفنيين في اصلاح اعطال الشبكة في الحرم الجامعي ثم عملت مع المهندسين في اعداد السوتشات وتحديثها.',
            ], // file#2:157
            [
                'company' => 'sahab',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'كنا نمشي على اللايف سايكل كامله بس لان التيم اللي معي من المتدربين كنا سريعين خلصنا السايكل بدري فعطونا شغل بالكوالتي والتستنق بس الصراحه يجي الشغل بعد نشبه شوي. الدوام مره مرن واهم شي عندهم الانجاز اكثر من ساعات الدوام.',
            ], // file#2:159
            [
                'company' => 'kfmc',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'التدريب عندهم فكرته غريبه نظامه كأنك ماخذه دورة مو تدريب. يشرح الدكتور اول شهر وبعدها اشتغلنا كقروبات وكل قروب اختار فكرة مشروع واشتغل عليه.',
            ], // file#2:160
            [
                'company' => 't2',
                'department' => 'ادارة المشاريع',
                'duration_months' => 3, 'modality' => 'remote',
                'stipend_sar' => 500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدريبي كان اونلاين. القسم اللي تدربت فيه كان ادارة المشاريع PM. أول شهرين تقريبًا كانوا يعطونا زي المحاضرات كل اسبوع أو اسبوعين كان يجينا موظف مختلف ويكلمنا عن أحد المواضيع. واخر شيء اخذو متدربين من كل مسار وقسمونا ثلاث مجموعات واعطونا مشروع.',
            ], // file#2:162
            [
                'company' => 'saudia_airlines',
                'role_title' => 'مطور ويب',
                'department' => 'Development',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'المسمى مطور ويب. تدربت في development department ثاني اهم قسم تقني في الخطوط. كنا ٦ اشخاص: ثلاث بنات وثلاث أولاد. وكان علينا ثلاث مشرفين. القسم كان برمجي بحت والمشروع اللي اشتغلنا عليه كان موجود لديهم ولكن يبغوا يشوفوا طريقتنا في برمجته وتصميمه. كان مشروع integrated وجدا كنا مستمتعين في العمل. انصح العمل في الخطوط في هذا القسم.',
            ], // file#2:174
            [
                'company' => 'kfmc',
                'department' => 'تقنية المعلومات - تدريب الأنظمة',
                'duration_months' => 2, 'modality' => 'remote',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم تدريب الانظمة. التدريب كان عن بعد من الساعه ٩ الصباح الى ٤. التدريب عباره عن دروس ومبادئ تساعد بالاخير ان ننشئ web application development باستخدام لغات عده منها sql و C# و html css.',
            ], // file#2:175
            [
                'company' => 'sdaia',
                'role_title' => 'مهندس جودة برمجيات',
                'department' => 'توكلنا - الجودة',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت في توكلنا - الجودة تحت مسمى مهندس جودة برمجيات quality assurance واشتغلت على اكثر من مسار وكان فيه امكانية تنتقل لادارة ثانية غير الجودة. الفريق جدا متعاون وحريص على انك تتعلم وكانت تجربة مثرية. كنت جزء من التيم فكنت اشتغل على مشاريع حقيقية.',
            ], // file#2:185
            [
                'company' => 'smart_methods',
                'duration_months' => 3, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'تدربت بمسار القوى الكهربائية والالكترونيات. المهمات كانت عبارة عن تكوين الدوائر الكهربائية والاردوينو. التدريب اونلاين او عن بعد حسب الرغبة وكل مسار ينفذ مهام مختلفه للمشاركة بتجهيز مشروع واحد ضخم. ما كان التدريب صعب فقط عبارة عن تعليم ذاتي ومستواه جيد حتى للمبتدئين. انصحكم تاخذوه بسنه مختلفه عن تدريب الجامعه.',
            ], // file#2:186
            [
                'company' => 't2',
                'role_title' => 'Software Development Trainee',
                'department' => 'Software Development',
                'duration_months' => 4, 'modality' => 'remote',
                'stipend_sar' => 1000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 5,
                'review_text' => 'Software Development Trainee. Every trainee is assigned to one or two mentors from the beginning of the training. The mentors were actual software developers working in the company so they are very experienced. After two months, the trainees were divided to teams and worked together to develop and deploy a complete web system. The company provides really good quality training, and two of my colleagues were employed by the company afterwards, so there is a high chance of getting employed.',
            ], // file#2:187
            [
                'company' => 'aramco',
                'department' => 'Information Security Analysis - WRDD',
                'city' => 'جدة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 2,
                'pros' => 'مشرف متعاون, تطوير مهارات, نصائح قيمة, فريق متعاون',
                'cons' => 'لا يوجد خطة واضحة للتدريب, الشغل يعتمد على ما يجيهم أسبوعياً',
                'review_text' => 'تدربت في قسم information security analysis في WRDD. المشرف كان متعاون جدا ونمى فيني مهارات كثيره واعطاني نصايح قيمه جدا. اشتغلت على مشروع معاهم عبارة عن IT optimizations. ما انصح كثير التدريب في هذا القسم لان ما كان عندهم خطه واضحه للتدريب على حسب الشغل اللي يجيهم ذاك الاسبوع. لكن من جهه الناس في القسم جدا متعاونين.',
            ], // file#2:190
            [
                'company' => 'hudhud',
                'role_title' => 'Machine Learning Engineer',
                'department' => 'Product Engineering',
                'duration_months' => 2, 'modality' => 'remote',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'تعلم كثير في المجال, كمية شغل مناسبة, فريق ممتاز علمياً وتوجيهياً, مشرف ممتاز',
                'review_text' => 'المسمى الوظيفي: Machine Learning Engineer. الشركة تبني Customer Service ChatBot باللغة العربية. كان شغلي تطوير أحد أجزاء تعلم الآلة في الchatbot وبالتحديد معالجة لغات طبيعية Natural Language Processing (NLP). الشركة كانت صغيرة وقتها لكن مشرفي كان ممتاز جدًا والتواصل عن بعد ما كان عائق. المميزات: اتعلمت كثير في المجال، كمية الشغل مناسبة لفترة التدريب، فريق العمل ممتاز من ناحية علمية وتوجيهية. باختصار التجربة ممتازة جدًا بالنسبة لي.',
            ], // file#2:191
            [
                'company' => 'tahakom',
                'role_title' => 'مطور تطبيقات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مطور تطبيقات. التيم الي اشتغلت معاه كله متدربات معي واذا احتجنا شي نسأل الموظفات. اخذنا مشاريع صغيرة نجرب تقنيات جديدة وفي الأخير عطونا مشروع ويب يخدم الشركة. الجهة تبحث عن حديثي تخرج التقديم عليها من خلال لينكدان.',
            ], // file#2:204
            [
                'company' => 'stc',
                'role_title' => 'مبرمج',
                'department' => 'تطوير التطبيقات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'مسمى مبرمج وتدربت بقسم تطوير التطبيقات وكان جدا مفيد وسع تفكيري وكان عمل جماعي والمعلومات اكتسبتها من الشغل معهم وسويت كود وجربت الكود بالموقع وطلع لليوزر وكان ممتع اشوف الشغل والتغير مباشره عند اليوزر النهائي. نصيحه التدريب خلوه اخر شي عشان لو عرضو عليكم وظيفه تكملون معهم.',
            ], // file#2:205
            [
                'company' => 'dsr_ksu',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 1, 'rating_organization' => 1,
                'cons' => 'لا يوجد تيم أو مشرف تقني, متطلبات غير واضحة, ضغط نفسي, لا خبرة مكتسبة',
                'review_text' => 'مافيه اي مسمى وظيفي ولا عندهم تيم. كانوا يبون احد يسوي لهم داتابيس بدون ما يستعينون بمبرمج. ولا عندهم اي معرفه ولا خبرة بالموضوع ولا فيه اي احد نرجع له. هم بنفسهم مو عارفين ايش يبغون واعطونا متطلبات غير واضحه وراحت الشهرين كلها نعدل بشغل اول اسبوعين. بالمختصر تجربة سيئة وضغط نفسي ما انصح اي احد يتدرب عندهم.',
            ], // file#2:206
            [
                'company' => 'zain',
                'department' => 'ERP',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'التيم متعاون',
                'cons' => 'لا يوجد شغل حقيقي, سرية المعلومات تمنع العمل, مجرد مشاهدة واستماع',
                'review_text' => 'متدربة في قسم ERP. التيم متساعد بس مايعطونك شغل بعذر انه فيه سرية للمعلومات وماتقدرين تشتغلين عليها. فقط تشتغلين مع موظفين وتكونين بس مستمعة وهم اللي يشتغلون. السيستم ممنوع تفتحينه بجهازك. باختصار مجرد متطلعة ماتسوين شي. انصح فيها للي يبي يعدي التدريب وبس وما انصح فيها للي يبي يستفيد.',
            ], // file#2:207
            [
                'company' => 'dsr_pnu',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'لا إشراف ولا تعليم, استغلال المتدربين, بطء الردود, لا يوجد تيم',
                'review_text' => 'مافيه مسمى وظيفي ولا فيه تيم. تدربت انا وصاحبتي هناك. خلونا نسوي ٢ داتا بيس ومعاها فورمز. كل الشغل علينا مافيه اشراف ولا احد يعلمك ويشرح لك. قضينا تقريبا شهر على التعديلات وبال يالله يردون عليك واضح انهم استغلاليين يبونك تسوين لهم كل شي. بالمختصر تجربه سيئة ما انصح فيها ابد.',
            ], // file#2:208
            [
                'company' => 'saptco',
                'department' => 'التطبيقات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'متدربة في قسم التطبيقات. اهم المهام هي اختبار نظام للعثور على المشاكل.',
            ], // file#2:209
            [
                'company' => 'ksu_network',
                'role_title' => 'مهندسة شبكات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مهندسة شبكات. كان بوحدة الشبكات ماكان في متدربات معي كنت لحالي. الشغل عبارة عن اطلع لبلاغات الموظفات عندهم مشاكل بشبكة الانترنت. تحول لي مشرفتي البلاغات واخذ لابتوب اسلاك وارجع اشوف وافحص. انصح فيها الي يبي تدريب خفيف لان في وقت كثير جالسه فيه.',
            ], // file#2:211
            [
                'company' => 'sdaia',
                'department' => 'ادارة الحوسبة السحابية - ادارة الخدمات',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'مهام في المجال, شغل على بيانات حقيقية, موظفين محترمين ومتعاونين',
                'cons' => 'مهام قليلة جداً, وقت فراغ كثير, لازم تنشب عشان تاخذ شغل',
                'review_text' => 'اشتغلت في ادارة الحوسبة السحابية في قسم ادارة الخدمات. عادية جداً. طوال فترة تدريبي كانت تجيني تاسك الى ٢ تاسك بالأسبوع بعد ما انشب لهم واقدر اخلص التاسك الواحد بساعه او ساعتين باليوم وباقي الوقت فراغ. لكن التاسك نفسها للامانه تعجبني وفي مجالي خلوني اشتغل على نظامهم واخذ داتا حقيقية واحللها في اكسل وكانو فعلا يعتمدونها بعد مايراجعونها. موظفينهم محترمين متعاونين.',
            ], // file#2:212
            [
                'company' => 'najm',
                'role_title' => 'اخصائي دعم وخدمات تقنية المعلومات',
                'department' => 'IT',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'القسم الي تدربت فيه IT. مسمى الوظيفي اخصائي دعم وخدمات تقنية المعلومات. صراحه التدريب كان مره حلو والتيم الي معي يساعدون مره اشتغلت تقريباً نفس شغلهم. بالقسم الدعم الفني باختصار هو ان تحل اي مشكله تقنيه تواجهه الموظفين.',
            ], // file#2:213
            [
                'company' => 'aramco',
                'department' => 'منع الخسائر',
                'city' => 'جدة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'تدربت في قسم منع الخسائر بجدة. القسم أبدًا مو IT وكنت لوحدي فيه بس اعطوني تاسكات نوعًا ما ناسبت مجالي. اشتغلت على تاسكات تسهل طريقة شغلهم وشيء يستفيدون منه. كانوا متعاونين جدًا وتعلمت منهم كثير. ضروري تثبتون لهم انكم شغيلين ومو جايين للاسم بس.',
            ], // file#2:214
            [
                'company' => 'kfmc',
                'department' => 'تقنية المعلومات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'متدربة. قسم تقنية المعلومات. تصميم ويب سايت باستخدام لغة سي شارب و اس كيو ال سيرفر مانجمنت ستوديو. انصح فيه وبشدة.',
            ], // file#2:215
            [
                'company' => 'najm',
                'role_title' => 'أخصائي أمن سيبراني',
                'department' => 'الأمن السيبراني',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'أخصائي أمن سيبراني. قسم الأمن السيبراني.',
            ], // file#2:216
            [
                'company' => 'mcit',
                'department' => 'إدارة الأمن السيبراني',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'إدارة الأمن السيبراني. كان التدريب مثري جدًا ويعكس طبيعة العمل الحقيقية في المجال. الفريق والبيئة رهيبة جدًا والاجتهاد مطلوب.',
            ], // file#2:217
            [
                'company' => 'aramco',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'كويسه.',
            ], // file#2:219
            [
                'company' => 'sp',
                'role_title' => 'Business Analyst',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Business analyst.',
            ], // file#2:220
            [
                'company' => 'elm',
                'role_title' => 'محلل أعمال / مطور',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'محلل أعمال مطور. شغلونا على مشاريعهم الحالية وكانوا داعمين لنا طول الوقت. عندهم أقسام نسائية لكن الاجتماعات غالبا مختلطة أو اونلاين.',
            ], // file#2:221
            [
                'company' => 'kacst',
                'department' => 'علم البيانات والذكاء الاصطناعي',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 2, 'rating_organization' => 3,
                'review_text' => 'تدربت في قسم علم البيانات والذكاء الاصطناعي. اشتغلت مع مشرفتي على مشروع كمبيوتر ڤجن. كنت المتدربة الوحيدة مع مشرفتي فكان الوضع مرن وكانت تسألني اذا ابي تعطيني لكتشرز وكانت مفيدة جدًا. البيئة غير مختلطة الى حد كبير ساعات الدوام مرنة جدًا ولا يوجد بصمة.',
            ], // file#2:222
            [
                'company' => 'sdaia',
                'department' => 'قسم الدراسات',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'pros' => 'شغل متنوع مع شركات عالمية, مكتب خاص لكل متدرب, ساعات مرنة, موظف مسؤول عن كل متدرب',
                'review_text' => 'مكان التدريب: قسم الدراسات. التدريب كان متنوع ويعطونك من الشغل والمشاريع الي يشتغلون عليها واغلبها مع شركات عالمية. اشتغلت على إدارة المشاريع وتحليل الاعمال وتحليل البيانات. كل متدرب يكون مسؤول عنه موظف واحد. بيئة العمل مريحة وكل متدرب له مكتب خاص فيه تحس كأنك موظف مو متدرب. ساعات عمل مرنه. الجميع خلوقين وسلسين في التعامل وودهم يفيدون المتدربين بأكبر قدر ممكن.',
            ], // file#2:224
            [
                'company' => 'tatweer',
                'role_title' => 'مطور تكامل',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'اشتغلت كمطور تكامل في مجال الواجهات. التيم كان ممتاز جدا. شغالين على مشروع كبير جدا.',
            ], // file#2:236
            [
                'company' => 'jahez',
                'role_title' => 'Business Analyst',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 2500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Business analyst.',
            ], // file#2:239
            [
                'company' => 'kacst',
                'role_title' => 'Cybersecurity Trainee',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'Cybersecurity trainee. المدينة اغلب شغلها بحوث. خلال التدريب بديت مع احد الدكاترة بحث علمي فللي يبي يكمل دراسته يكون خيار مناسب.',
            ], // file#2:240
            [
                'company' => 'stc',
                'role_title' => 'IT Project Manager Trainee',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 5700, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'IT PROJECT MANAGER trainee. مافيه بوزشن واضح يحطونك بأي مكان. مهارات التواصل اهم من شغلك اللي تنجزه.',
            ], // file#2:241
            [
                'company' => 'sp',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تجربة جميلة جداً ومثرية.',
            ], // file#2:242
            [
                'company' => 'tahakom',
                'role_title' => 'Test Engineer',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت ك Test Engineer تحت مسمى Coop trainee. اول كم اسبوع ما كان فيه شغل بعدين بدينا ندخل في مشاريع الشركة. التدريب كان ممتاز لانك تشتغل كموظف وتيم التيستنق رهيبين ومتعاونين جدا.',
            ], // file#2:243
            [
                'company' => 'dsr_iau',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'استغلال المتدربين, لا يوجد موظفين, شهادة بدون توقيع أو ختم',
                'review_text' => 'قسم التسويق. كل المتدربين هم طلاب جامعتنا والبقية متطوعين. يوجد موظفة واحدة تخصصها حاسب. لا أنصح بالتدرب لديهم. جهة التدريب تستغل الطالب والمتطوع. لا يوجد لديهم موظفين لذلك يستغلون الطلاب وحتى الشهادة الا يعطونك اياها شهادة ممزقه ولا يوجد بها لا توقيع ولا ختم.',
            ], // file#2:246 (file#2:247 byte-identical duplicate — skipped)
            [
                'company' => 'kkuh',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'كنا ٥ عيال التدريب كان من نفس الجامعة. على اساس اننا بنشتغل في قسم IT لكن اشتغلنا مدخلين بيانات على نظام SAP. الصراحة ما انصح بالجهة لانها كانت ممله ولا راح تعطيك الخبرة.',
            ], // file#2:248
            [
                'company' => 'kfmc',
                'role_title' => 'مطور تطبيقات ويب',
                'department' => 'تقنية المعلومات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'cons' => 'لا يناسب مخرجات طلاب الحاسب, لا خطة دقيقة, عدد متدربين كبير, لا تركيز فردي',
                'review_text' => 'مطور تطبيقات الويب. قسم تقنية المعلومات. التيم عبارة عن متدربين من مختلف تخصصات الحاسب. المشروع عبارة عن عمل موقع بلغة سي شارب. لا انصح بالتدريب في هذه الجهة كون ان التدريب لا يناسب مخرجات طلاب الحاسب ولا يوجد خطة دقيقة للتدريب بالاضافة الى ان عدد المتدربين جداً كبير فمافي تركيز او تطوير على الصعيد الشخصي.',
            ], // file#2:250
            [
                'company' => 'mobily',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 2500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اشتغلت على Power BI, SQL.',
            ], // file#2:251
            [
                'company' => 'aec',
                'role_title' => 'مهندس برمجيات',
                'department' => 'الهندسة والتطوير E&D',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'اشتغلت كمهندس برمجيات في قسم الهندسة والتطوير E&D. التيم رهيب يحبونك تسأل ويساعدون بشكل حلو حتى اللي برا المبرمجين كانو يساعدوني في كل شي نصايح حتى انهم دفوني على الادارة عشان اتوظف. الفرق بينك وبين الموظف حرفيا البطاقه يعطونك تاسكات لين تشبع بتتعلم كثير وبسرعه معهم.',
            ], // file#2:253
            [
                'company' => 'cma',
                'department' => 'ادارة امن المعلومات - GRC & SOC',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'تدربت بقسم ادارة امن المعلومات تحتها فريقين GRC & SOC. ب GRC تدربت اني اكتب السياسات واراجع سياسات تتعلق بالامن السيبراني. بالنسبه SOC اشتغلت معاهم شغل حقيقي من ناحيه spam and phishing and email release. مهام الSOC كانت حلوه لانه تشتغل شغل حقيقي عكس ال GRC اللي كله سياسات. مكاتب البنات منفصله عن مكاتب الرجال بس وقت الاجتماع فيها اختلاط.',
            ], // file#2:255
            [
                'company' => 'sabic',
                'department' => 'ERP',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'قسم ERP ما اعطونا صلاحيات لكن كانوا يطلعون بيانات الموظفين ويطلب منا المدير نسوي تقرير على حسب اذا يبغى يشوف الكلاسيفيكيشن للقسم وكيف نحسن القسم وموظفيه.',
            ], // file#2:258
            [
                'company' => 'elkaraj',
                'role_title' => 'Backend Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'باك اند وكان الشغل لحالي اشوف دورات على سطر وغيره واسوي بروجكت كبير بالنهايه. تدريبهم فادني بس موب مره خلوه اخر خيار.',
            ], // file#2:259
            [
                'company' => 'stc',
                'role_title' => 'Application Performance',
                'department' => 'Operations',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'كان مسماي الوظيفي Application performance تحت قسم Operation الشغل عبارة عن تحليل اداء KPIs بس للأسف على وقتي كان التيم حقي مشغول عني فما أخذت حقي كامل بالتدريب.',
            ], // file#2:260
            [
                'company' => 'kfupm',
                'department' => 'System',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تخصصي برمجة تطبيقات والاجهزة الذكيه. تدربت في قسم System. كان التدريب حلو في ايام اداوم بدون ما اسوي شي والسوبرفايزر كان يحاول يعطيني اشياء في تخصصي.',
            ], // file#2:261
            [
                'company' => 'mewa',
                'department' => 'حوكمة تقنية المعلومات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت بقسم حوكمة تقنية المعلومات. اشتغلنا على مشروع cmmi و iso اكثر من فئه وعطوني مهام تحليل للسياسات والاجراءات بادارة تقنية المعلومات والتحول الرقمي وعطوني بوزشنز تقنية اسوي لها وصف وظيفي. بيئة العمل مره حلوه. المكاتب ماهي مختلطه البنات لهم قسم مكاتب خاص مقفل بس الاجتماعات مختلطه.',
            ], // file#2:262
            [
                'company' => 'kacst',
                'department' => 'معهد الذكاء الاصطناعي والروبوت',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 2, 'rating_organization' => 3,
                'review_text' => 'معهد الذكاء الاصطناعي والروبوت. اشتغلت على البحث وبناء deep learning models في مجال الطاقة. ماكان معي تيم كل متدرب مع مشرفه. نسبة التوظيف بعد التدريب 0% وجودة التدريب تختلف من مشرف لمشرف ومن معهد لمعهد.',
            ], // file#2:263
            [
                'company' => 'lean',
                'role_title' => 'ML Engineer',
                'department' => 'Data',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'ML Engineer, Data department. تيم مكون من ٥-٦ اشخاص نصهم سينير ونصهم توهم متوظفين. التدريب ٦ اشهر وعرضو علي تمديد للIntern. دخلت معهم بالشغل مباشرة ويعتمدون على شغل المتدربين على طول. خفيفين دم واذا سألتيهم يجاوبون. المكان في الرقمية. فيه يومين الى اربع ايام اونلاين.',
            ], // file#2:264
            [
                'company' => 'cisco',
                'duration_months' => 12, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'كان جزء من برنامج apprenticeship لمدة سنة. جتني الفرصة عن طريق مسك. بالبداية كانوا مخليني على راحتي بس عطوني ccna bootcamp و soft skills bootcamp وبعدين لمن خلصت الاختبارات بديت معاهم مشاريع مع منتور. بس مو مرة يعطون شغل يبي لك تلاحقين وراهم بس بشكل عام المكان حلو والتعلم ممتاز.',
            ], // file#2:284
            [
                'company' => 'pwc',
                'role_title' => 'Technology Consultant',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Technology consultant.',
            ], // file#2:285
            [
                'company' => 'sabic',
                'role_title' => 'Cybersecurity Assurance Trainee',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'cybersecurity assurance trainee. التيم متعاون ويساعدونك قد ما يقدرون ويعطونك اشياء تسوينها كانك موظفة. لكن اللي تبغى تدريبها يكون تيكنيكال ما بيناسبها. تجربتك تختلف عن تجربة غيرك وانبسطت بالتدريب صراحة.',
            ], // file#2:286
            [
                'company' => 'tahakom',
                'role_title' => 'محللة اعمال',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'دخلت كمحللة اعمال وكان عندنا مشروع من عندهم على عدة مراحل. قاعدين نحاكي شغل محلل الاعمال بشكل عام. لحد الان مادخلوني بمشاريع حقيقيه. البيئه شبه مختلطه.',
            ], // file#2:288
            [
                'company' => 'sirar',
                'role_title' => 'Cybersecurity Trainee',
                'department' => 'MSS-OPS / Cyber Resilience',
                'duration_months' => 8, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت في قسم عمليات الامن السيبراني المدارة MSS-OPS حالياً يسمى بالcyber resilience. القسم يوفر حلول امنيه للعملاء ك blue team زي web security, vulnerability management, email security, awareness campaigns, anti-DDoS. التيم متعاون جداً وفيهم ناس خبرة بيثرونكم ويدعمونكم وبشكل عام البيئة ممتازه. التدريب ما يقبلونكم اذا اقل من ٦-٨ شهور.',
            ], // file#2:289
            [
                'company' => 'minthar',
                'role_title' => 'مطور / مساعد مشروع',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'pros' => 'تطور مهني جيد, تنوع المهام, حرص على تطوير المتدرب',
                'cons' => 'يدخنون في المكتب',
                'review_text' => 'اول شيء اشتغلت تحت مسمى مطور وبرمجت باستخدام فلاتر ثم بديت أساعدهم باشياء ثانيه زي التستنق وغيره وآخر الأسابيع اشتغلت كمساعد مشروع. من ناحية الشغل والتطور المهني فهم جيدين وحريصين تتطور بس البيئة حقيقة شينه شوي لأنهم يدخنون.',
            ], // file#2:290
            [
                'company' => 'devoteam',
                'role_title' => 'Data Engineer',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'Data Engineer. تدريبي عبارة عن برنامج يمتد ل6 اشهر فيه اهداف لازم احققها من شهادات او اسايمنتس. اشتغلت كمهندس بيانات. اجتزت بعض الدورات المتعلقه بالبرمجه وحوكمة البيانات بعدها دخلت مشروع يركز على الذكاء الاصطناعي والبيانات الضخمه. اشتغلت على جودة البيانات على برنامج informatica وايضا ETL و data cleansing وprofiling والبرمجه بلغه sql وبايثون. البيئة جدا متساعده وخبراتهم عاليه. كونها شركه استشاريه الدوام يكون بمقر الكلاينت.',
            ], // file#2:291
            [
                'company' => 'minthar',
                'role_title' => 'مساعدة مدير مشاريع تقنية',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'pros' => 'فرصة تجربة كل شي, يعتمدون على المتدرب, تنوع المهام في SDLC',
                'cons' => 'لا التزام بالخطة, تعلم ذاتي بدون توجيه, يدخنون في المكتب بكثرة',
                'review_text' => 'في البداية كنت تقنية او مطورة بعدين تغير مسماي إلى مساعدة مدير المشاريع التقنية. جربت واشتغلت بكل شي من بداية التحليل وبعدين التصميم UI/UX وكل SDLC اللي هي نفس حقت أجايل. المميز في الشغل في startup خاصة منظار انهم يعطونك فرصة تجربين كل شي ويعتمدون عليك. بس للاسف ما يلتزمون مرة بالخطة واغلب التدريب انتي روحي دوري واكتشفي بنفسك. يدخنون في المكتب واجد وذا سبب لي ازمة.',
            ], // file#2:293
            [
                'company' => 'sscp',
                'department' => 'ادارة المشاريع التقنية',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'في ادارة المشاريع التقنية وكانت تجربة فريدة ورائعة. الموظفين متعاونين بشكل رهيب.',
            ], // file#2:295
            [
                'company' => 'mod',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'راح تستفيد جزئيًا وبتحتاج اجتهاد من عندك عشان تستخرج معلومات أكثر. بس عمومًا الوقت اللي بتقضيه مع نفسك بالتعلم الذاتي خلال وقت الدوام ثمين وأكثر شيء بيفرق معك لو استغليته.',
            ], // file#2:297
            [
                'company' => 'sabic',
                'department' => 'IT',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم IT مانشتغل على مشاريع كمبتدئين لكن اشتغلنا على نظامهم المستخدم.',
            ], // file#2:298
            [
                'company' => 'citc',
                'department' => 'التقنيات الناشئة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'كانت تحت قسم التقنيات الناشئة بالهيئة مختصة باصدار التصريحات والانظمة اللي تنظم أي تقنية ناشئة بالمملكة مثل انترنت الاشياء والذكاء الاصطناعي والبلوك تشين و VR والميتافيرس. العمل جيد بس مافيه تيكنيكال ابد فقط نظري وتنظيمات وتعديلات.',
            ], // file#2:299
            [
                'company' => 'tahakom',
                'department' => 'الذكاء الاصطناعي + تطوير حلول',
                'duration_months' => 8, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'قسمين: الذكاء الاصطناعي عطوني مشروع اختباري لفكرة ويسمحون لك اكسس على السوبر كومبيوتر حقهم. لديهم كل اسبوعين تقريبا سشن لنقل الخبرات. قسم تطوير حلول بمسمى intern software developer فرونت اند باستخدام angular. مشرفة المباشرة كانت جدا رائعه دربتنا على مشروع خارجي ثم دخلتنا مشروع فعلي. تستخدم حقيقي git على fork, bitbucket. يسوون لك ريفيو وأنت ايضا تسوي لهم. البيئة جيدة الكل يحب يتعرف على المتدربين.',
            ], // file#2:301
            [
                'company' => 'arabianshield',
                'department' => 'IT Governance',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'بشكل عام فيه جميع الاقسام وتختار اللي يعجبك. بالنسبة لي اشتغلت اكثر شيء على حوكمة قسم الIT.',
            ], // file#2:307
            [
                'company' => 'redf',
                'department' => 'التحول الرقمي',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 1,
                'review_text' => 'كنت في قسم التحول الرقمي التيم كانو ممتازين لكن ماكان فيه شغل لمدة شهر لين لقى لي احد الموظفين موظف يشتغل ذكاء اصطناعي وكلمه يشغلني معه ودعست معه. لكن كان معي حول 8 متدربين كانو قاعدين فاضين فالغالب مب مكان تتدرب فيه لكن فيه فرصة هناك.',
            ], // file#2:308
            [
                'company' => 'sfda',
                'role_title' => 'Front-End Developer',
                'department' => 'التحول الرقمي',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'كنت front-end developer. تدربت في قسم التحول الرقمي. المشرف والقسم كانوا متعاونين جداً. طورت مكتبه UI تمثل هوية الهيئة يستفيدون منها المطورين.',
            ], // file#2:309
            [
                'company' => 'tcc',
                'role_title' => 'COOP',
                'department' => 'Security Services - GRC & DFIR',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'اشتغلت بقسم Security Services كان شهر مع GRC وشهر مع DFIR. الشركة مافيها متدربين كثير لان التدريب عندهم فقط ترشيح داخلي. الشركة شبه حكوميه ومملوكة لتحكم الاستثماريه. شغلهم ما كانوا يمسكوني شغل حقيقي غالبا لكن كان زي التاسك هم قبل اشتغلوها ويراجعون معي شغلي. أنصحكم فيها لو الاختلاط مو مشكلة.',
            ], // file#2:310
            [
                'company' => 'alinma',
                'department' => 'Shared Services',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 2, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 1,
                'cons' => 'لا مهام حقيقية, لا أجهزة للمتدربين, برزنتيشن ومهام بسيطة فقط',
                'review_text' => 'ما فيه مسمى تدريبي. تدربت في قسم shared services. ما كان فيه مهام التدريب بس برزنتيشن ومهام بسيطة جدا sql query. ما أعطونا أجهزة عشان نبدأ نتدرب معهم. ما انصح في التدريب معهم إلا لو كنتوا تعرفون مين المشرف اللي بيمسكك.',
            ], // file#2:311
            [
                'company' => 'ncgr',
                'department' => 'Data Migration',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'الفريق مشغول ومحد فاضي للمتدرب, لا شغل واضح, المكافأة توقفت بعد أول شهر',
                'review_text' => 'كنت ضمن فريق data migration. طوال الست اشهر كان الفريق مشغول بمشروع ومحد فاضي يدربني ومافي شغل واضح. اذا ماركضت وراهم مايعطونك شي ولا كأنك موجود. المكافأة ٣٠٠٠ ريال اعطوني اياها اول شهر بس وسحبو بقية الاشهر بحجة مشكلة بالنظام.',
            ], // file#2:312
            [
                'company' => 'sharedtech',
                'role_title' => 'Web Developer',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'Web Developer. اشتغلت على Drupal و PHP. صراحة متعاونين جدا والبيئة جدا ممتازة.',
            ], // file#2:313
            [
                'company' => 'masterworks',
                'role_title' => 'Data Governance Analyst',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'المسمى بالتدريب كان Data Governance Analysis. البيئة محفزه وداعمه جدا ويساعدونك بكل شيء. اشتغلت في مشروعين مطارات الرياض وهيئة المدن والمناطق الاقتصادية والخاصة. التيم في الجهتين كلها كانو متعاونين جدا وخبرتهم عالية. اكبر ميزه بالشركة الخبره اللي تقدمها. عندهم اكثر من 300 مشروع حكومي وشبه حكومي.',
            ], // file#2:314
            [
                'company' => 'pwc',
                'role_title' => 'متدرب استشارات تقنية',
                'duration_months' => 8, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'متدرب استشارات تقنية. القسم هو استشارات، المجال حسب المشروع. كنت حريصه اني اشتغل في مشاريع إدارة واستراتيجية البيانات. غالبًا كل مشروع فيه فريق من ٣-٦ اشخاص. فرص التعلم كبيره سواءً في المجال التقني أو على المستوى الشخصي. الexposure الي تتعرض له كمتدرب في شركة استشارية مفيد جدا لأنك تشتغل مع عملاء كثير في وقت جدا قصير.',
            ], // file#2:315
            [
                'company' => 'tahakom',
                'role_title' => 'محلل أعمال',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'محلل أعمال. البيئة شبه مختلطة يعني فيه ادوار نسائيه بالكامل لكن عادي تشتغلين عن بعد او تحضرين اجتماعات مع رجال.',
            ], // file#2:328
            [
                'company' => 'elm',
                'role_title' => 'مطور مواقع',
                'department' => 'أبشر',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'مطور مواقع. التيم جداً متعاون ومريح كان لي مكتب. اغلب وقتي كنت ادرس والتاسكات كانت تعديلات على المواقع. تعلمت على تولز كثيرة جداً. انصح بالتدريب عندهم.',
            ], // file#2:329
            [
                'company' => 'expertise_systems',
                'department' => 'السايبر',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'الخطة لم تُطبق, شغل أوفيس فقط, لا خبرة سايبر حقيقية, تدريب تسليكي',
                'review_text' => 'كنت متدرب في قسم السايبر. صراحة التدريب كان تسليكي جدا حتى الخطة الي كتبوها ما مشوا عليها وكان ٩٩٪ من شغلي بور بوينت وورد واكسل. فالخبرة المكتسبة قليلة جدا بالنسبة لمجال السايبر. اذا تبي فايدة من التدريب فما انصحك تتدرب.',
            ], // file#2:331
            [
                'company' => 'tamkeen',
                'department' => 'Datacenter',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدربت في Datacenter ونهاية التدريب بدوا يدخلوني على backup. البيئة اختلاط لكن القسم اللي اشتغلت فيه للبنات لحال.',
            ], // file#2:332
            [
                'company' => 'sabic',
                'role_title' => 'Business Analyst',
                'department' => 'Business Application',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تدربت في قسم ال Business Application في مسمى بزنس اناليست واشتغلت على مشروع خاص بسابك. كانت تجربة جميلة وتفرق التجربة على حسب المنجر اللي بيكون مشرفك. بيئة مختلطه لكن مكاتب البنات بجهه والعيال بجهه.',
            ], // file#2:333
            [
                'company' => 'elkaraj',
                'department' => 'تحليل الأعمال',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اشتغلت بقسم تحليل الأعمال. البيئة تقنية حلوة واشوفها ممتازة لخريجين الحاسب. يعتمد على مشرفك اذا حاب يعلمك او لا. فيه اقسام كثيرة مثل تصميم الواجهات وتطوير الجوال والتطبيقات. البيئة مختلطة في الاجتماعات ولكن المكاتب معزولة.',
            ], // file#2:335
            [
                'company' => 'alinma',
                'role_title' => 'UI/UX',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'UI/UX. كانت التجربة مره رهيبة لأنه المديرة حريصه علي.',
            ], // file#2:340
            [
                'company' => 'stc_solutions',
                'department' => 'Integrated Solutions',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تدربت في قسم Integrated Solutions. مديري اقترح علي خطة التدريب اني امر على الأقسام واخذ فكرة عامة عن كل قسم واتابع سلسلة اساسيات الشبكات ccna. ميزة الموظفين انهم محترفين بمجالهم. يوم خلصت من المرور على الاقسام طلبت من مديري امدد واني مازلت محتاج اطبق على القسم الي مهتم فيه ومدد لي ثم مددو لي تمهير. خلال التدريب ضموني مع فرق لتغطية WiFi وشبكات في events.',
            ], // file#2:341
            [
                'company' => 'pwc',
                'role_title' => 'Technology Consulting - GenAI CoE',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Technology Consulting, Digital Services > GenAI CoE.',
            ], // file#2:348
            [
                'company' => 'stc',
                'department' => 'System Operations - Digital Applications',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'تيم متعاون, خبرة قوية ومجال عميق, مكان مثالي للتدرب',
                'cons' => 'لازم تسعى وراهم بنفسك ما ينادونك',
                'review_text' => 'System operations - digital applications. التيم جدًا متساعد ويعطيك وقته وجهده عشان تتعلم بس يبغى لك تسعين وراهم يعني ماراح ينادونك هم. الخبرة جدًا قوية هنا لو طلعتي وانتي عارفة كل شيء بتكون خبرتك واو لأن هالمجال فهالشركة مره deep. اشوفه مكان مثالي للتدرب فيه. البيئة مختلطة ولكن يوجد ladies section.',
            ], // file#2:349
            [
                'company' => 'ey',
                'role_title' => 'Technology Consultant Intern',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'pros' => 'بيئة تعليمية داعمة, شهادات ودورات مجانية, خبرة متنوعة بأكثر من مشروع',
                'cons' => 'الكونسلتنق فيه بزنس أكثر من حاسب',
                'review_text' => 'Technology consultant intern. التيم والمشاريع تختلف على حسب الجهة لان بتشتغلون في مكان العميل. الكونسلتنق يدخل فيه بزنس مره كثير يعني بتستفيدون بس مو مره من ناحية الحاسب. البيئة مره تساعد انكم تتعلمون الكل يبي يساعدك ويعلمك وعندهم شهادات ودورات مجاناً من udemy. بتكتسبين خبرة كبيرة لان بتشتغلون في اكثر من مشروع.',
            ], // file#2:354
            [
                'company' => 'accenture',
                'role_title' => 'Technology Strategy & Advisory',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 6000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'فرص مميزة في الاستشارات, تنقل بين شركات ومشاريع متنوعة, تعلم كثير, تعامل مع مناصب عالية',
                'cons' => 'يعتمد على حظك بنوع المشروع والعميل, ضغط عالي, لا يوجد استقرار',
                'review_text' => 'اشتغلت في القسم الاستشاري تحديدا Technology Strategy & Advisory. العميل كان احدى شركات PIF والبروجكت كان Operating model سوينا اعادة تصميم وتشغيل قسم الIT كامل عند الكلاينت. من الايجابيات: الفرص في الشركات الانترناشنل وفي اقسام الاستشارات مالها مثيل، تتنقل بين شركات كثير وبروجكتس متنوعة وتتعلم كثير. الexposure عالي جدا لان الشركة الاستشارية تتعامل مباشرةً مع اصحاب المناصب العالية. من السلبيات: انت وحظك بنوع البروجكت والكلاينت. ضغط عالي. مافيه استقرار.',
            ], // file#2:356
            [
                'company' => 'sdaia',
                'role_title' => 'مهندس بيانات',
                'department' => 'بنك البيانات الوطني',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'المسمى كان مهندس بيانات في بنك البيانات الوطني. التيم كان ممتاز والأغلب كانت خبرتهم ٣-٥ سنوات. بالبداية المشرف شرح لي وش البروجكتس اللي عندهم واعطاني بعض الكونسبتس. بعدها كانت تاسكات مثل الاسايمنتس زي building ETL pipelines واشياء أخرى. وبعدها خلوني أمسك ريكويستس حقيقية مع الموظفين إلى نهاية تدريبي. البيئة مختلطة والدوام مرن اهم شيء تكمل ٨ ساعات.',
            ], // file#2:368
            [
                'company' => 'ksu_dect',
                'department' => 'ادارة المشاريع',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'متدربة بادارة المشاريع. تدربت على ادارة المشاريع والبنية المؤسسية. البيئة مره متعاونين وجاهزين يعلمونك اي حاجة. مافيه شغل تقني جامد زي ماتوقعت لكن اذا حابين اقسام ثانية تقولون لوحدة التدريب تنقلكم.',
            ], // file#2:374
            [
                'company' => 'tahakom',
                'department' => 'تطوير البرمجيات - التطبيقات',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => true, 'mixed_env' => false,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'تدربت في قسم تطوير البرمجيات وتحديدًا التطبيقات. التدريب مفيد جدًا وكانوا منظمين. الكل متعاون ولطيف جدًا يدخلونكم الاجتماعات ويعطونكم شغل حقيقي. البيئة جميلة جدًا من مبنى وموظفات وطبيعة عمل. المبنى 7 أدوار دورين منها للنساء ومنفصلة تمامًا.',
            ], // file#2:375
            [
                'company' => 'kacst',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'على حسب مشرفك تتحدد مهامك. ولا تتوقع عمل واو لأن الجهة بحثية فعلى حسب مشروع مشرفك بيكون حجم عملك.',
            ], // file#2:376
            [
                'company' => 'elm',
                'role_title' => 'مهندس اختبار جودة البرمجيات',
                'department' => 'الحلول الرقمية',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'تدربت كمهندس اختبار جودة البرمجيات تحت قطاع الحلول الرقمية. التيم معي مره بطل كان الكل يحاول يساعدني عشان اتعلم. المشاريع اللي مسكتها كبيره منها الضمان الاجتماعي المطور ورابطه العالم الاسلامي وعاملوني معامله موظف. اقدر اقول اني لميت كل شغل QA خلال مده التدريب. الحمد لله بعد التدريب جاني عرض وظيفي منهم. البيئه مختلطه لكن المكاتب منفصله فيه اقسام نسائيه متكامله.',
            ], // file#2:377
            [
                'company' => 'sab',
                'department' => 'IT / Cybersecurity',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 1,
                'review_text' => 'كنت اول شهر مع IT وطلبت اروح مع الشبكات والامن السيبراني. مافيه خطه واضحه للتدريب.',
            ], // file#2:384
            [
                'company' => 'sdaia',
                'department' => 'الحوسبة السحابية - تطوير البرمجيات',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'خطة تدريب ومتابعة من المشرفة',
                'cons' => 'لا مشاريع حقيقية, مشروع افتراضي فقط, لا مكاتب للمتدربين, لا اندماج مع الموظفين',
                'review_text' => 'تدربت في إدارة الحوسبة السحابية تحديدًا تحت قسم تطوير البرمجيات. مشرفتي اعطتني خطة مدة التدريب أمشي عليها وكانت تراقب شغلي كل فترة. بس ماكنت اشتغل على مشاريع خاصة بسدايا كان مشروع افتراضي خاص فيني وماكنت أدخل معهم كموظفين. مافيه مكاتب للمتدربين بالقسم. الجهة مو أسوء شي بالتدريب بس مو أحسن شي.',
            ], // file#2:385
            [
                'company' => 'nwc',
                'department' => 'IT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'IT. تدربت في كل الاقسام الموجودة في فرع العوالي مكه الاقسام قليله. الموظفين جدا محترمين ويعطوا تاسكات ويعلموك عدل.',
            ], // file#2:386
            [
                'company' => 'erada',
                'department' => 'IT',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'رحت لقسم التدريب في مستشفى الملك خالد فتم توجيهي إلى مجمع إرادة للصحة النفسية. معي ثلاثة طلاب. المشرف يعطينا مهام تشمل فحص اجهزة الحاسب واستقبال مشكلات الموظفين وتثبيت وتحديث البرامج وتعديل أنظمة الحماية. الدوام المشرف كان يتساهل معنا.',
            ], // file#2:388
            [
                'company' => 'etmal',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 1, 'rating_organization' => 1,
                'review_text' => 'ما انصحكم في التدريب في ذي الشركة ما عندهم خطة تدريب وماعندهم جدية في التدريب.',
            ], // file#2:401
            [
                'company' => 'qabas',
                'role_title' => 'Cybersecurity Specialist',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'دوام مرن بدون تدقيق',
                'cons' => 'لا شغل سايبر حقيقي, كل الشغل دوكيومنت فقط, لا فائدة مكتسبة',
                'review_text' => 'المشرفه قالت لي اني سايبر سيكيورتي سبيشلت بس الصراحه الشغل كان كله دوكيومنت يعني مااشتغلت على تولز او شي فعليا سايبر. الحضور والانصراف مايدققون فيه. عرضوا علي فرصه تمهير بس رفضتها لان ماعندهم شغل سايبر. حسيت اني ماتعلمت او استفدت شي عندهم.',
            ], // file#2:402
            [
                'company' => 'sdaia',
                'department' => 'مكتب ادارة البيانات الوطنية - البحوث والدراسات',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت في مكتب ادارة البيانات الوطنية تحديدًا ادارة البحوث والدراسات. التيم اللي كانوا معاي كلهم كويسين وحريصين جدًا اني اتعلم واستفيد قدر الامكان. كانوا يعطوني مهام كثير ويتابعون معي اول بأول ويتناقشون وياخذون ويعطون سلسين جدًا.',
            ], // file#2:403
            [
                'company' => 'nupco',
                'role_title' => 'Data Operations',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Data Operations. قد يكون هناك فرص وظيفية عن طريق بوابة مسك (التدريب على رأس عمل) او عن طريق شركات مشغلة داخل الشركة.',
            ], // file#2:404
            [
                'company' => 'moe',
                'department' => 'إدارة التحول الرقمي',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 1, 'rating_organization' => 1,
                'cons' => 'بيئة سامة, لا وضوح لسياسات التدريب, تغيير مفاجئ بالمتطلبات',
                'review_text' => 'بناء على تجربتي بالقسم اللي تدربت فيه البيئة توكسيك مره ماانصح وافضل انهم يكونون اخر خيار. مافيه وضوح لسياسات التدريب. فجأه صدموني اني لازم اتدرب عندهم ٦ شهور وفجأه قالوا لازم تسوي مشروع عشان ينتهي تدريبك.',
            ], // file#2:405
            [
                'company' => 'saib',
                'department' => 'Cybersecurity',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'pros' => 'خطة تدريب واضحة, rotation بين أقسام السايبر, دورات ولابات مجانية, بيئة شغل محفزة, شغل حقيقي',
                'review_text' => 'تدريبي كان بقسم السايبر سيكوريتي rotation بين GRC, Architecture, Defense, Assurance. سوو لي خطة تدريب واسندوا لي داخل كل قسم بالسايبر وتحديدا كل موضوع مين الموظف المسؤول اللي بيعطيني شرح. اول شهر كان اوفرفيو عن السايبر كامل بعدها يعتمد على حسب احتياج اقسام السايبر ومدى التزامك وجديتك. دخلت معهم بالشغل عشان اساعد في GRC. من الايجابيات فيه موقع خاص لموظفين البنك فيه دورات ولابات بالسايبر. بيئة شغل صدقي الكل يشتغل ويكرف تتحمسين معهم.',
            ], // file#2:410
            [
                'company' => 'hayyak',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'كلمتهم يعطوني مسمى وظيفي وصرفوني. سيئة جدا اعطوني مهمة وحدة على الاكسل. ما انصح فيها ابدا للتخصصات التقنية. لا انصح بالتدرب لديهم ضياع للوقت. اضطريت اتدرب عندهم عشان ما لقيت جهة تقبلني.',
            ], // file#2:416
            [
                'company' => 'kpmg',
                'role_title' => 'Data Analyst',
                'department' => 'IT Innovation and Solutions',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 4, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدربت تحت قسم IT Innovation and Solutions في تقنية المعلومات الداخلية Central Service مع فريق تحليل البيانات على Power BI. اشتغلت على مشاريع على Power BI وتعلمت عليه وساعدوني اخذ شهادة مايكروسوفت. لو تصدق اذا قالوا لك بتشتغل كثير ونعتبرك موظف لأني بالحسرة قدرت آخذ منهم شغل حقيقي.',
            ], // file#2:418
            [
                'company' => 'imamu',
                'role_title' => 'System Analysis',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'System analysis. التدريب مشروع فاشل بس يعطل الطلاب عن التخرج. لا تضيع وقتك تدور جهة تدربك حاول تخلصه بأسرع وقت عشان تاخذ الوثيقة وتتوظف.',
            ], // file#2:419
            [
                'company' => 'sultan_holding',
                'department' => 'التسويق',
                'city' => 'القنفذة',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'كنت في فريق التسويق. اشتغلنا معهم طول الفترة في اغلب أعمالهم وقمنا بشكل خاص بإنشاء موقع للشركة وتحديثه. التيم كان متعاون جدا والمشرفة كانت مهتمة بتعليمنا. التدريب كان فوق الممتاز بس ما ناسب تخصصي لأني كمبيوتر ساينس فالمفروض يكون تدريبي تقني أكثر.',
            ], // file#2:420
            [
                'company' => 'umqura_charity',
                'role_title' => 'اخصائية تقنية',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'مسماي الوظيفي: اخصائية تقنية. تم تدريبي في قسم تقنية المعلومات. التيم اللي معي كان جدا جدا متعاون. الشغل كله دعم فني وصيانه وشوي اشتغلنا على تطوير الموقع الالكتروني. ماكان فيه مشاريع نشتغل عليها. اذا كان تخصصك حاسب مابيفيدك التدريب بشكل كبير.',
            ], // file#2:422
            [
                'company' => 'hajj',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'تم تدريبنا في اغلب الأقسام تقريبا ولكن بشكل بسيط جدا بسبب ضيق الوقت. ما انصح احد يسجل عندهم فترة الحج لانهم مضغوطين فيها.',
            ], // file#2:424
            [
                'company' => 'dsshield',
                'role_title' => 'Project Coordinator',
                'department' => 'PMO',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'المسمى الوظيفي project coordinator. اتدربت في قسم PMO. التيم كان متعاون جدا وخبراتهم ممتازة. اشتغلت على مشاريع كثيرة ومريت على جميع مراحل المشاريع بدايته نهايته.',
            ], // file#2:426
            [
                'company' => 'se',
                'role_title' => 'UX/UI Designer',
                'department' => 'تجربة المستخدم',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'ux/ui قسم تجربة المستخدم. التيم جدا ممتاز ومتعاون واشتغلت على مشروع تصميم واجهات في تجربة المستخدم في فيقما.',
            ], // file#2:427
            [
                'company' => 'kidana',
                'department' => 'الأمن السيبراني - GRC',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'قسم الأمن السيبراني تحديدا GRC. كل التيم خبرتهم من ذهب ما شاء الله. افادوني جدا جدا.',
            ], // file#2:429
            [
                'company' => 'hira_hospital',
                'department' => 'IT',
                'city' => 'مكة المكرمة',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'IT.',
            ], // file#2:430
            [
                'company' => 'hajj',
                'department' => 'أمن المعلومات',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 2,
                'pros' => 'كوادر رائعة, بيئة ممتازة',
                'cons' => 'مهام بسيطة خلال موسم الحج, لا خطة واضحة',
                'review_text' => 'قسم أمن المعلومات. توجد كوادر رائعة وبيئة ممتازة لكن لابد ان يكون وقت التدريب مناسب للجهه لا يتزامن مع فترة الحج ورمضان وان تتأكد من وجود خطة واضحة مع المسؤولين. تزامن وقت تدريبنا مع فترة الحج والعمرة فكانت المهام بسيطه.',
            ], // file#2:431
            [
                'company' => 'makkah_emirate',
                'department' => 'ادارة تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'أشتغلت على ثلاث مشاريع الاولى بناء نظام والثنتين الباقية مشابهه لها في الاهمية. أستفدت من التدريب الحمد لله.',
            ], // file#2:432
            [
                'company' => 'moasharat',
                'role_title' => 'محلل بيانات',
                'department' => 'العمليات',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 1000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'محلل بيانات في قسم العمليات. اصدار تقارير باور بي اي وميتابيز. المتابعة الميدانية والتاكد من جودة البيانات واصدار الخطة اليومية.',
            ], // file#2:433
            [
                'company' => 'makkah_amanah',
                'city' => 'مكة المكرمة',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'كانت تجربة ثريه استفدت الكثير وقابلت ناس كثير من تخصصات تقنية وخبرات عالية.',
            ], // file#2:434
            [
                'company' => 'uqu_cyber',
                'city' => 'مكة المكرمة',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'خلونا نتدرب على اشياء الجامعة الفعليه والدومينات وكانو متعاونين معانا مره وصراحة كويسيين على انو ما كانو مجهزين لنا خطة قوية واتوقع السنين الجاية حتكون احسن وافضل.',
            ], // file#2:435
            [
                'company' => 'mbc',
                'department' => 'ITS',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم ITS. كان troubleshooting للباصات. شركة جيدة جداً لإكتساب خبره.',
            ], // file#2:436
            [
                'company' => 'cars_syndicate',
                'role_title' => 'Full Stack Developer',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'Front end and back end. Angular, C#. موقع النقابة.',
            ], // file#2:437
            [
                'company' => 'kacst',
                'role_title' => 'اخصائي تفاعل الإنسان مع الحاسب',
                'department' => 'معهد أبحاث الأمن السيبراني',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'pros' => 'جهة تدريب رهيبة, شغل على مشاريع حقيقية, تجربة مستخدم وواجهات',
                'cons' => 'تتطلب سكن ومواصلات بالرياض, لا دعم من المدينة',
                'review_text' => 'اخصائي تفاعل الإنسان مع الحاسب. تدربت في معهد أبحاث الأمن السيبراني. تدربت مع تيم دكتورة اثير. اشتغلت على مشروعين ومسكت الواجهات فيهم وتجربة المستخدم. جهة التدريب رهيبة جدًا انصح بها الكل لكنها بالرياض وتتطلب سكن ومواصلات ولا يوجد أي تعاون من المدينة من هذي الناحية.',
            ], // file#2:442
            [
                'company' => 'wadi_makkah',
                'role_title' => 'Full Stack Developer',
                'department' => 'الاستثمار',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مبرمجة full stack. قسم الاستثمار. كنا تيم بنات وعيال. الخبرات كلها كانت في الفرونت اند ماعدا واحد كان داتا بيس. تطوير موقع من الصفر حتى تم استخدامه لنظام الشركة.',
            ], // file#2:443
            [
                'company' => 'lean',
                'department' => 'Advanced Analytics',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'كنت في قسم البيانات advanced analytics. الشغل عبارة عن sql اما تحليل بيانات او جهات خارجية طالبين بيانات. تعديلات على داشبورد او تسوي واحد من الصفر. بعد التدريب التعاوني مافي توظيف ولكن يعرضون GDP. بالنسبة لقسم البيانات البيئة جدا مريحة والكل متعاون مع المتدربين. ويعطون المتدرب شغل رح تطلع بخبرة.',
            ], // file#2:444
            [
                'company' => 'tahakom',
                'role_title' => 'محلل اعمال',
                'department' => 'ادارة الحلول - تحليل الاعمال',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 2,
                'review_text' => 'محلل اعمال. القسم ممتاز وفيه شغل بس لازم تلاحق ورا الشغل او يخلونك تتدرب على مستندات تدريبية. فيه كم مشروع وبعضهم الحركة فيه قليلة وبعضهم حار وكثير الشغل فيه. التيم متعاون ولكن ضرووري تلحق ورا الشغل. التوظيف الان شبه موقف.',
            ], // file#2:446
            [
                'company' => 'waraq',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'شغل Help Desk فقط, فائدة قليلة جداً, جهة سيئة',
                'review_text' => 'فيها فريقين Infrastructure و Application. غالبا بيحطونك Help Desk تشوف مشاكل المستخدمين وتحلها. لا انصح فيها نهائيا. بالنسبة لي نشبت نفسي مع تيم الApplication وللامانه ما استفدت كثيرا. جهة سيئة جدا يروحها المضطر فقط.',
            ], // file#2:447
            [
                'company' => 'bsf',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => 2000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => null,
            ], // file#2:449
            [
                'company' => 'site',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 3000, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'المسمى: coop. القسم اداري. التجربة عموما رائعة ويعاملونك كموظف. فيه مكاتب نسائية فقط للي ماتبي اختلاط.',
            ], // file#2:450
            [
                'company' => 'emkan',
                'role_title' => 'Project Manager',
                'department' => 'Digital',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'اشتغلت كبروجكت مانجر بالديجتال ديبارتمنت. كانت تجربة رهيبة والتيم والبيئة كويسه مره وتتعلم وتستفيد منهم ومن خبرتهم ويعاملونك معاملة شخص من التيم مو متدرب بالمسؤوليات. مره انصقلت منهم وجتني اوفرز من برا بعد الله والاكسبرينس الي اخذتها منهم.',
            ], // file#2:457
            [
                'company' => 'camco',
                'role_title' => 'IT Trainee',
                'department' => 'IT',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'صفر مشاريع, كل واحد يرميك على الثاني, بيئة عمل كئيبة, لا فائدة',
                'review_text' => 'المسمى الوظيفي: IT Trainee. قسم IT. ١١ موظف في قسم IT المدير و Seniors كلهم اجانب الباقين سعوديين اغلب التيم خصوصا السعوديين جديدين بالشركة. مشاريع اشتغلت عليها: صفر. ماراح تستفيد شي عندهم اذا بغيت شي من Senior كل واحد يرميك على الثاني. المكان اللي يداومون فيه IT كئيب جدا.',
            ], // file#2:458
            [
                'company' => 'kpmg',
                'role_title' => 'Solution Development and Innovation',
                'department' => 'IT Central',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 1500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'Department solution development and innovation under the IT department (central). I was basically doing advisory work internally, developing solutions for internal departments such as tax, audit, people, and help out in external projects. I was mainly working on the power platform, sometimes azure too. They offer LinkedIn learning and pluralsight subscriptions. Working hours are flexible but you got to do your tasks on time.',
            ], // file#2:459
            [
                'company' => 'tamara',
                'role_title' => 'Backend Software Engineer',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 4000, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'مهندس برمجيات backend. البيئة جيدة وتعلمت كثير. التدريب كان ٦ شهور.',
            ], // file#2:484
            [
                'company' => 'ejada',
                'role_title' => 'AI Trainee',
                'department' => 'AI Delivery',
                'duration_months' => 6, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'AI Trainee - AI Delivery Department. تجربة ممتازة الحمدلله من كل النواحي. قدرت أشتغل و أجرب على أكثر من شي وفي كم مجال. رحت كلمت أحد الموظفين أنه يكون مينتور لي وصار شغلي كله معه. غالب مدة التدريب اشتغلت في product management في أحد المشاريع. الدوام مره كان مرن جدًا وكان فيه اختيار أني أداوم ريموتلي. كانت ممتازة الحمدلله.',
            ], // file#2:485
            [
                'company' => 't2',
                'department' => 'الأمن السيبراني',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدريبي كان في قسم الأمن السيبراني. بداية كان عبارة عن GRC وبعدها دخلنا على Wazuh لين نهاية التدريب كان في خطه له. التدريب كله كان اونلاين بس نداوم بالمقر. كان في دورات إضافيه. كنا نشتغل على أشياء وهمية مو حقيقي وكل نهاية اسبوع نستعرض ايش سوينا مع المشرف.',
            ], // file#2:492
            [
                'company' => 'accenture',
                'role_title' => 'Technology Strategy New Associate',
                'department' => 'Technology Strategy and Advisory',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => 6000, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'رواتب ممتازة',
                'cons' => 'مهام جانبية كثيرة متعبة تخص الشركة, المجال الاستشاري قد لا يناسب الجميع',
                'review_text' => 'تدربت في قسم technology strategy and advisory. ولا انصح الصراحة بالتدريب يمكن لان المجال ما عجبني (الكونسلتنق). يطلبون منك اشياء جانبيه تخص اكسنتشر نفسهم اشياء كثير جدا متعبه غير الشغل اللي يخص الكلاينت. بخصوص رواتبهم ممتازه الانالست ياخذ اول راتب ٢٠ الف.',
            ], // file#2:494
            [
                'company' => 'samref',
                'role_title' => 'Process Engineering Intern',
                'department' => 'Process',
                'city' => 'ينبع',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => 3500, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'Process Engineering Intern - Process Department. Overall, it was a valuable learning and working opportunity, though the experience largely depends on the supervisor. Fortunately, my supervisor was excellent, which allowed me to make the most of the 3 months internship.',
            ], // file#2:495

            // File #7 — جمع تجارب التدريب الصيفي لكلية الحاسب ونظم المعلومات 1443هـ
            [
                'company' => 'smart_methods',
                'department' => 'انترنت الاشياء',
                'city' => 'مكة المكرمة',
                'duration_months' => 7, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'مسار انترنت الاشياء تجربه سيئه وما استفدت كثير اشتغلنا على روبوت مسارات متداخله منها هندسه وذكاء اصطناعي وغيره.',
            ], // file#7:2
            [
                'company' => 'makkah_chamber',
                'role_title' => 'مبرمجة ومطورة',
                'department' => 'التحول الرقمي',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'مبرمجه ومطوره - قسم التحول الرقمي - مشاريع في تحليل المشاريع - تقارير من قواعد بيانات - UI/UX - سكيورتي.',
            ], // file#7:3
            [
                'company' => 'git_innovations',
                'role_title' => 'مصممة واجهات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'كنت مصممة واجهات صممت مشروع واجهات موقع الكتروني وبعد الانتهاء من التصميم قمت بتحليل المشروع SRS. البيئة متعاونة ومحترمه جدا.',
            ], // file#7:4
            [
                'company' => 'kaah_makkah',
                'role_title' => 'فنية دعم فني',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'pros' => 'تعلم أشياء كثيرة, برمجة موقع للقسم',
                'cons' => 'لا يوجد كراسي تكفي المتدربات, عدد المتدربات كبير جداً',
                'review_text' => 'فنية دعم فني. قسم تقنية المعلومات. برمجة اكسل وتصليح الاجهزة والطابعات وفرمتة للأجهزة. العيوب ما كان في كراسي تكفي كل البنات لاننا كنا تقريبا ٢٠ متدربة. المزايا اتعلمت اشياء كتير وكان في برمجة لموقع خاص بالقسم.',
            ], // file#7:5
            [
                'company' => 'sdaia',
                'department' => 'الأمن السيبراني',
                'city' => 'الرياض',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'pros' => 'بيئة محفزة, كفاءات سعودية ناجحة, تعرف على المجال عملياً ونظرياً',
                'cons' => 'الإدارة لم تكن على علم بمجيء المتدرب مسبقاً',
                'review_text' => 'عملت في ادارة تخص الأمن السيبراني وتعرفت على المجال عمليا ونظريا في أكثر من قسم. البيئة محفزة وتؤهلك لسوق العمل. الموظفين من الكفاءات السعودية الناجحة. عيب التدريب انه لم تكن ادارتي على علم بمجيئي مسبقا. انصح بسدايا جدا.',
            ], // file#7:6
            [
                'company' => 'smart_methods',
                'department' => 'انترنت الاشياء',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'مسار انترنت الاشياء. كانت في مهام كثير في مساري بالبداية قالوا سوي ٤ منها عشان تاخذي الشهادة بس بعدين قالو خلاص مهمتين تكفي. كان عددنا كبير فمحد منتبه انتي ايش سويتي او انتي فاهمة ولالا.',
            ], // file#7:7
            [
                'company' => 'smart_methods',
                'department' => 'IOT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'iot. تدريب زق لازم تدفع فلوس عشان يعلموك خذ دورة ولا انك تدخل عندهم.',
            ], // file#7:13
            [
                'company' => 'smart_methods',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 1, 'rating_organization' => 1,
                'review_text' => 'ابعد كل البعد عن التدريب ذا عشان مافي منو فائدة.',
            ], // file#7:14
            [
                'company' => 'smart_methods',
                'department' => 'انترنت الاشياء',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'مسار انترنت الاشياء. اشتغلت على اربع تاسكات كل اثنين مع بعض. التدريب بشكل عام كان سهل بس في البداية ضياع لازم الواحد يسأل ويستفسر ويكلم المشرفين.',
            ], // file#7:15
            [
                'company' => 'eisar',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تم تقسيمنا الى مسارات: الويب والتطبيقات والتصميم. اعطوا لكل مسار دورات مثلا الويب درسوا Laravel والتطبيقات Flutter. بعدها طلبوا مننا نبني نظام حلّلنا النظام مع المهندس وبدينا. اشوف انها كانت تجربة واقعية في بناء نظام مع فريق والمهندس كان خبير واستفدنا منه كثير.',
            ], // file#7:16
            [
                'company' => 'smart_methods',
                'department' => 'انترنت الاشياء ونظم البيانات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 1, 'rating_organization' => 1,
                'review_text' => 'كنت في مسار انترنت الاشياء ونظم البيانات ما كان فيه اهتمام ولا شي والتدريب بكبره كانه شو بس.',
            ], // file#7:19
            [
                'company' => 'smart_methods',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'اشتغلت على كل المجالات الهندسة الكهربائيه والميكانيكية والذكاء الاصطناعي وانترنت الاشياء. ما انصح فيه كتدريب جامعي فيه فرص كثيره افضل ممكن بعد التخرج او الاجازات الصيفية للمتعة.',
            ], // file#7:20
            [
                'company' => 'kaah_makkah',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'كانت تجربه جدا ممتعه كل اسبوع تقريبا نتعلم شي جديد مثلا انظمة الحمايه واستقبال الاتصالات وحل المشكلات وتغيير الاجهزه ومعرفة الخلل ونقاط الشبكات وادخال وتعديل بيانات الموظفين. المهندسين الموجودين جدا متعاونين.',
            ], // file#7:21
            [
                'company' => 'kaah_makkah',
                'role_title' => 'دعم فني',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'دعم فني. مسؤلين عن استقبال اتصالات اعطال الاجهزه والشاشات والطابعات في جميع المستشفى واصلاحها.',
            ], // file#7:22
            [
                'company' => 'makkah_amanah',
                'department' => 'الحلول الرقمية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'في ادارة الحلول الرقمية. في خطة بس ما كان في احد يعطينا بسبب انهاء عقد الشركة المسؤولة. اخذنا بس يومين في الاسبوع ناخذ مهمه بسيطة. مافي فائدة كثير لان اغلب الاشياء نعرفها. اكبر مشكله هي اللخبطة.',
            ], // file#7:23
            [
                'company' => 'intercontinental_makkah',
                'department' => 'IT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'pros' => 'تجربة حقيقية لشغلهم, تعلم أنظمة وسيرفرات',
                'cons' => 'موظفين القسم قليلين ومشغولين, وقت التدريب غير كافٍ',
                'review_text' => 'تدربت في قسم IT. المدرب يعلمنا عن كل شيء يخص IT للفندق. تعلمنا عن الانظمة السيرفرات اللي يستخدمونها وعن حل المشكلات التقنية. من مزايا التدريب انه تجربة حقيقية لشغلهم. عيوب التدريب: موظفين القسم قليلين فكانوا ينشغلون اغلب الوقت فما يكون عندهم وقت كافي يدربونا. اللغة الانجليزية مهمة جداً.',
            ], // file#7:27
            [
                'company' => 'git_innovations',
                'department' => 'UI/UX + Requirements',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تدربت بقسم UI/UX وقسم كتابة المتطلبات وتحليلها. الفرصة كانت رهيبة وحلوة واللي دربوني بنات مره كانو لطيفات ومتعاونات. اشتغلنا على مشروع كان اساسا مطلوب لعميل والشركة سوته بس احنا حاكيناه وحللنا متطلباته وصممنا الواجهات.',
            ], // file#7:28
            [
                'company' => 'vision_experts',
                'department' => 'تحليل البيانات وذكاء الأعمال',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'مسار تحليل البيانات وذكاء الأعمال لمدة ٦ أسابيع مفيدة. التدريب حلو من كل النواحي. هم يدربوك على مسارك وفي نهاية التدريب يطلبو منك تسوي مشروع ويساعدوك عشان تطلعه بأفضل صورة. بيئة العمل رائعه ومحفزه ويسوو دورات لمواضيع مختلفه. بشكل مجمل التدريب عندهم ممتاز انا دخلت ومو عارفه ايش هي تحليل البيانات وطلعت فاهمه وعارفه كيف أسويه.',
            ], // file#7:30
            [
                'company' => 'kaah_makkah',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'متدربة في قسم تقنية المعلومات. مهتمين بالصيانة والشبكات اكثر من البرمجة. ماعندهم خطة واضحة. متعاونين ومتساهلين جدا.',
            ], // file#7:35
            [
                'company' => 'smart_methods',
                'department' => 'انترنت الاشياء',
                'city' => 'مكة المكرمة',
                'duration_months' => 3, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'عندهم اقسام متنوعه: ذكاء اصطناعي وانترنت الاشياء وأقسام متعلقة بالهاردوير. انا اخذت انترنت الاشياء كان عباره عن برمجة واجهات المستخدم: مواقع وداتابيس وتطبيقات.',
            ], // file#7:36
            [
                'company' => 'makkah_police',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'صيانة شبكات بالعموم.',
            ], // file#7:37
            [
                'company' => 'se',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 2,
                'pros' => 'بيئة جيدة, موظفين متعاونين',
                'cons' => 'لا يوجد خطة تدريبية مسبقة',
                'review_text' => 'تدربت في مبنى تقنية المعلومات والبيئة كانت جيدة والموظفين متعاونين مع المتدربين. السلبية الوحيدة انه لم توجد خطة تدريبية مسبقة وكانت الخطة عشوائية.',
            ], // file#7:39
            [
                'company' => 'dsp_tech',
                'role_title' => 'مهندس شبكات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'pros' => 'أجهزة متوفرة, تعليم احترافي',
                'cons' => 'لازم تطلب المهام بنفسك من المدرب',
                'review_text' => 'المسمى مهندس شبكات. قسم الشبكات. فقط مهام تعطى من المدرب. من المزايا الاجهزه متوفره وتعليم احترافي. العيوب يجب على المتدرب طلب مهام من المدرب.',
            ], // file#7:40
            [
                'company' => 'cars_syndicate',
                'role_title' => 'مبرمج',
                'department' => 'إدارة تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'التدريب في قسم إدارة تقنية المعلومات بمسمى مبرمج. اشتغلنا على تطوير نظام النقابة. اتعلمنا اكثر عن قواعد البيانات. اتعلمنا عن ذكاء الاعمال واشتغلنا على مشروعين فيها. القسم كامل متعاون ومرحب بالمتدربين.',
            ], // file#7:45
            [
                'company' => 'makkah_amanah',
                'department' => 'المدن الذكية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'cons' => 'أغلبه نظري, سوء تنظيم, غير فاضيين للمتدربين, تجربة مخيبة للآمال',
                'review_text' => 'تدربت في قسم المدن الذكية. من ناحية تدريب المدن الذكية كان اغلبه كلام نظري شرح لادارتهم. اخر شي طلبو مننا نقترح مبادرات ذكية. صار تغيير لمدير القسم وأرسلو لنا مهندس شرح لنا على السريع دورة حياة البرمجيات. باختصار تحسهم متورطين بالمتدربين ومو فاضيين لهم والمدير الجديد بنفسه قال لنا انه فيه سوء تنظيم للمتدربين هذا العام. تجربتي كانت مخيبة للامال.',
            ], // file#7:51
            [
                'company' => 'smart_methods',
                'department' => 'IoT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'قسم ال IoT تشغيل الدوائر الكهربائية عن طريق البرمجة.',
            ], // file#7:52
            [
                'company' => 'makkah_amanah',
                'department' => 'الدعم الفني',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'دعم فني الوضع ملخبط ما كان في خطة تدريب. اول اسبوعين كانت اجازه الحج اعطونا حالة واقعة نحلها. اشتغلنا في الشبكات والصيانة.',
            ], // file#7:53
            [
                'company' => 'se',
                'department' => 'البلاغات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم البلاغات - حل المشاكل التقنية اللي تواجه كمبيوترات الموظفين واستقبال بلاغاتهم.',
            ], // file#7:54
            [
                'company' => 'makkah_amanah',
                'department' => 'إدارة الدعم الفني',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'اول اسبوعين كانت عن بعد. بعدها عملنا على اعمال الدعم الفني مثل فورمات وبرامج وتركيب سلك شبكة لكن كان فيه كثير وقت فراغ. الثلاث اسابيع الاخيرة وضحنا لهم استيائنا وتم تغيير المشرفه واعطائنا بنود لتنفيذ اعمال فقمنا بتحليل وتصميم نظام لمتابعة المشاريع.',
            ], // file#7:55
            [
                'company' => 'smart_methods',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'تغطي أكثر من تخصص, تجربة ممتعة',
                'cons' => 'لا يوجد شهادة, فقط إفادة حضور',
                'review_text' => 'تجربة ممتعه كطالبة علوم حاسب لانها تغطي اكثر من تخصص. موضوع التدريب كان يتمحور حول الروبوتات وكيفية بناءها ميكانيكياً واليكترونياً وهندسياً وبرمجياً. عيوب التدريب: لا يوجد شهادة للمتدربين في المسار المدفوع فقط افادة حضور.',
            ], // file#7:56
            [
                'company' => 'eisar',
                'city' => 'مكة المكرمة',
                'duration_months' => 8, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'سرعة التواصل والمساعدة, مهام كثيرة ومفيدة, مشاريع حقيقية',
                'cons' => 'أغلب الأسبوع عن بعد ويوم واحد حضوري, بيئة العمل غير موفرة للمتدربات',
                'review_text' => 'كنا ١٠ طالبات تقسمنا لقسمين: قسم لتطوير الويب وقسم للتطبيقات. المشاريع: تحليل نظام الشركة، رسم database، إنشاء تطبيق للشركة وربطه بالAPI. عيوب: كل الاسبوع عن بُعد ويوم واحد بس حضوري. بيئة العمل غير موفّرة للمتدربات. المزايا: سرعة التواصل ومساعدتهم لنا والتاسكات الكثيرة اللي كانت جدًا مفيدة.',
            ], // file#7:62
            [
                'company' => 'noor_hospital',
                'department' => 'السجلات الطبية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'التدريب كان في قسم السجلات الطبية. اشتغلت في كل الاقسام الموجودة في القسم: الاستقبال والجودة والارشفة الالكترونية وكتبة جناح والترميز الطبي.',
            ], // file#7:73
            [
                'company' => 'holoul_tech',
                'role_title' => 'مبرمج انظمة',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مبرمج انظمة.',
            ], // file#7:74
            [
                'company' => 'makkah_amanah',
                'department' => 'المدن الذكية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'تدربت في قسم المدن الذكية. المكان مرتب لا يوجد اختلاط مباشر. اغلب المهام كانت افكار ومبادرات طلبت منا ومهام ادارية غير متعلقة بقسم الحاسب.',
            ], // file#7:75
            [
                'company' => 'sdaia',
                'department' => 'سحابة ديم - Cloud Security',
                'city' => 'الرياض',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => true,
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'تدرّبت في سدايا تحديدًا سحابة ديم في cloud security team. بالبداية عطوني بريف واضح عن طبيعة العمل وبعدها صرت اتابع مع مشاريعهم ومع الvendors وبعدها صرت small manager على مشروع من مشاريع الإداره. بيئة عمل جدًا رهيبه وجميع اللي بسحابة ديم كانوا أشخاص متعاونين ومعطائيين لدرجه كبيره. تجربه رهيبه مليانه مزايا وخاليه من العيوب.',
            ], // file#7:76
            [
                'company' => 'holoul_tech',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 2,
                'pros' => 'المهندس المشرف محترم, بيئة محترمة ومتعاونة',
                'cons' => 'المهام غير مرتبة, البداية بحث وبرزنتيشن فقط',
                'review_text' => 'هندسة برمجيات ولكن التاسكات ما كانت مرتبه وفي البداية كان اغلبها ابحثي وسوي برزنتيشن. عبال ما تخطينا مرحلة البحث وبدينا نشتغل بروتوتايب وفلاتر. مزايا التدريب الرجال والمهندس المشرف جدا محترم والشركة كلها تراعي حدود التعامل ومتعاونين.',
            ], // file#7:79

            // File #4 — تجارب التدريب 2023 (skipped rows: 9-13,17,21,25,28,35-44 = عن بعد/دورات; row 10 = garbage)
            [
                'company' => 'makkah_amanah',
                'department' => 'ادارة تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 2, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'بلاغات دعم فني. قسم ادارة تقنية المعلومات. ما اشتغلنا على مشاريع وصراحه اعتبر التدريب لعب. التدريب مايفيدني الا لو توظفت عندهم لأن بشتغل على نظام الأمانه. مره متعاونين من ناحية الحضور ولا فيه مراقبه او توقيع.',
            ], // file#4:2
            [
                'company' => 'kaah_makkah',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'المهام كانت تتقسم الى صيانة ودعم فني وكان فيه صيانة وقائية (جرد الاقسام) وتطبيق على اسلاك الشبكة.',
            ], // file#4:14
            [
                'company' => 'noor_hospital',
                'department' => 'السجلات الطبية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم السجلات الطبية. وحدة حفظ الملفات والتصنيف والارشفه مريح وممتع اكثر من استقبال القسم او الرسبشن بالادوار.',
            ], // file#4:15
            [
                'company' => 'makkah_police',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'دوام خفيف يوم ايوه يوم لا',
                'cons' => 'استفادة قليلة, نظام تعليمي نظري يشبه الجامعة',
                'review_text' => 'المهام: تصميم شبكة - صيانة الحاسب. المزايا: شغلهم ماهو كثير والدوام يوم اي ويوم لا فما راح تتعبون. العيوب: الاستفادة ماهي كبيرة بنهاية التدريب لان النظام تعليمي كاننا بالجامعة فما حسيت مرة استفدت.',
            ], // file#4:16
            [
                'company' => 'kamc_makkah',
                'department' => 'IT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدربت في قسم IT.',
            ], // file#4:18
            [
                'company' => 'kaah_makkah',
                'department' => 'تقنية المعلومات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'دور مختص تقنية المعلومات. يعلمونك عن الشبكات والأسلاك ودور الدعم الفني واستقبال الاتصالات. يفيدك تتعلمين الcommon troubleshooting لكن لو توجهك تطوير برمجيات مواقع وتطبيقات مافيه.',
            ], // file#4:19
            [
                'company' => 'maternity_makkah',
                'department' => 'الصحة الالكترونية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'قسم الصحة الالكترونية. القسم فيه ٤ اقسام تم العمل في كل قسم لمدة من اسبوع الى ٢.',
            ], // file#4:20
            [
                'company' => 'mutawifeen_africa',
                'department' => 'الشبكات',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدربت في قسم الشبكات على جزء السيرفرات اتعلمت فيه عن السيرفرات من الصفر كيفية انشاءها وتشغيلها وانشاء سيرفرات افتراضيه وتوزيع الانترنت من السيرفر وعدة اشياء متعلقه بالسيرفرات والتحكم بها.',
            ], // file#4:22
            [
                'company' => 'eisar',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'pros' => 'كورسات تقنية متنوعة, خبرات المهندسين مفيدة, تعلم بناء موقع من الصفر',
                'cons' => 'الوقت قصير جداً وما كفى',
                'review_text' => 'بالبداية تعلمنا عن الشركة وبعدين اخذنا كورسات في الدورة التقنية وفي القت هب وسوينا مركز مساعدة ورفعناه بالقت هب واخذنا دورات UI/UX وتحليل الانظمة. مره استفدنا من خبرات المهندسين وتعلمنا كيف نسوي موقع ويب من الصفر. العيب ان الوقت كان جدًا قصير وماكفانا.',
            ], // file#4:23
            [
                'company' => 'moj',
                'department' => 'الدعم الفني',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'المدربين متعاونين, طبيعة العمل بسيطة',
                'cons' => 'صلاحيات محدودة, غير مجهزين لاستقبال متدربين',
                'review_text' => 'نساعد في قسم الدعم الفني بكل مره تكون عندهم مشكله نحلها. العيوب: اللي يبغى يتطور ويستفيد فعلا ما انصح لان صلاحياتك كمتدرب محدوده ولا عندهم استعداد فعلي ومجهزين لاستقبال متدربين. المزايا: المدربين متعاونين وطبيعه العمل بسيطة.',
            ], // file#4:24
            [
                'company' => 'awn_tech',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'أول أربعة أيام أخذنا دورات في UI/UX وكتابة SRS بعد كذا عرضولنا مشاريع حقيقية نختار منها واحد وكونا تيم من خمسة أشخاص اشتغلنا على المشروع من ناحية تصميم الواجهات وكتابة SRS. كان التدريب مره حلو وممتع اكتسبت خبرة من بعض المتدربين والمشرفة كانت مره متعاونة.',
            ], // file#4:26
            [
                'company' => 'sfh_makkah',
                'department' => 'ICT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 4,
                'review_text' => 'التدريب كان في قسم ICT. خطة التدريب كانت مقسمة بين مهام يتم تنفيذها على موقع المستشفى والجزء الآخر مشروع نظام بفكرة محددة يستخدم في المستشفى. يحتاج من المتدرب معرفة نظام joomla ولغة php واطار العمل codeigniter. التدريب يحاكي عمل الموظف الطبيعي في القسم.',
            ], // file#4:29
            [
                'company' => 'awn_tech',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'جدا استمتعت واستفدت في التدريب وكان عبارة عن تصميم واجهات وكتابة srs file و testing.',
            ], // file#4:33
            [
                'company' => 'makkah_amanah',
                'department' => 'وكالة الحلول الرقمية',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'تدربنا في أقسام مختلفة مثل برمجة واجهات وبرمجة تطبيقات وباك اند وهندسة برمجيات لكن المعرفة اللي اضيفت لنا بسيطة جدا ما اشتغلنا عمليا على مشاريع كانت كلها حاجات بسيطة لمبتدئين وسبق تعلمها في الجامعة بشكل أعمق. شرحوا بشكل نظري لغة php واكواد مشاريع حقيقية.',
            ], // file#4:34
            [
                'company' => 'first_city',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'قمنا بعمل مشروع تطبيق جوال متكامل. مزاياه بيئة العمل متعاونة جدا.',
            ], // file#4:45
            [
                'company' => 'ksau_hs',
                'department' => 'IT',
                'city' => 'جدة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تم اعطائي جدول للمرور على العديد من اقسام IT والتعرف على بيئة وطبيعة العمل.',
            ], // file#4:46
            [
                'company' => 'sdaia',
                'department' => 'الحوسبة السحابية - دمج الحوسبة السحابية',
                'city' => 'الرياض',
                'duration_months' => 4, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تدربت في قسم الحوسبة السحابية تحت قسم دمج الحوسبة السحابية. العمل في هذا القسم يتطلب نقل مركز البيانات المادي الى السحابة بجميع السيرفرات والبيانات الموجودة عليها. ايضاً اجتماعات مع العملاء لفهم طلباتهم وبيئتهم وشرح السحابة لهم وكيف ستكون حل افضل لهم.',
            ], // file#4:47

            // File #1 — استبيان جهات التدريب لدفعه ٤٤ (skipped: rows 3,7,28,31,32,35 = garbage/blank)
            [
                'company' => 'awn_tech',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'هندسه برمجيات. بدأنا من التخطيط والتصميم الى برمجة لغة flutter. اغلب المهام تطبيقات بسيطه وممتعه.',
            ], // file#1:4
            [
                'company' => 'makkah_amanah',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'ذكاء اصطناعي',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => null,
            ], // file#1:5
            [
                'company' => 'awn_tech',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'التدريب لم يكن مدرج تحت مسمى وظيفي فعلي ولكن يستهدف الطلبة في تطوير مهاراتهم.',
            ], // file#1:6
            [
                'company' => 'techwin',
                'role_title' => 'Backend Developer / Full Stack',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'أمن سيبراني',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 5,
                'review_text' => 'Backend developer / full stack. تم تكليفي بتعلم لارافل فريم ورك ومسك مشروع مع مجموعه من بداية فترة التدريب. اول خطوة كتابة الSRS وانشاء الداتابيس ثم برمجة الويب ورفع المشروع على السيرفر. سوينا API واستخدمنا بوستمان وتعلمنا استخدام قيت هب واوامر القيت. تدريب مميز جدا ومفيد بالكامل.',
            ], // file#1:8
            [
                'company' => 'jeddah_amanah',
                'city' => 'جدة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 1, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'pros' => 'مهام متكاملة في تحليل البيانات, استخدام اكسل وبايثون و Power BI',
                'cons' => 'لا يوجد توجيه أو تعليم, تعلم ذاتي بالكامل, الشغل على أجهزة خاصة',
                'review_text' => 'المهام كانت عبارة عن تحليل بيانات استخدمنا اكسل وبايثون سوينا داشبوردز على Power BI. حلاوة المهام كانت متكاملة تستلمي بيانات خام وترتبي وتنظفي وتشتغلي عليها لين توصليها لمرحلة انك تعملي داش بورد. العيوب: ماكان في احد يعلم ويوجه ابدا يعني كله اجتهاد وتعليم شخصي. الشغل كله كان على اجهزتنا الخاصة.',
            ], // file#1:11
            [
                'company' => 'four_points_makkah',
                'role_title' => 'متخصص IT',
                'department' => 'IT',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'reviewer_major' => 'ذكاء اصطناعي',
                'rating_mentorship' => 3, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'pros' => 'مناسب لتخصص الشبكات والأمن السيبراني',
                'cons' => 'لا يناسب التخصصات البرمجية, لا برمجة أو أكواد, شغل فيزيائي فقط',
                'review_text' => 'متخصص IT في فندق. من ناحية تخصصي ذكاء اصطناعي ماله اي فائدة نهائيا مافيه اي برمجه ولا اكواد ولا مشاريع برمجية. اغلب الشغل كان فيزيائي شبكات واسلاك وتوصيل وسيرفرات. من ناحية برمجية ما تعلمت اي شي. ما انصحه للتخصصات البرمجيه لكن انصح اللي تخصصه هندسة شبكات او امن سيبراني.',
            ], // file#1:14
            [
                'company' => 'hajj',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'تفاعل الإنسان مع الحاسب',
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'مافيه مسمى وظيفي. في البداية علمونا على طريقة عمل موظفين الصيانة والدعم الفني وبعدين كلفونا بمهام غير مفيدة. السبب هو انهم مضغوطين في موسم الحج. لحد يتدرب في وزارة الحج والعمرة الترم الثالث بيكونون مضغوطين ومحد فاضي لك.',
            ], // file#1:21
            [
                'company' => 'makkah_amanah',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 3, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'المهام بسيطة جدا اغلبها نظري مهندس يتكلم عن شغله. وفيه أشياء ممتعة زي تصميم الواجهات لكن قليل العملي.',
            ], // file#1:24
            [
                'company' => 'makkah_amanah',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'ذكاء اصطناعي',
                'rating_mentorship' => 3, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'التدريب نظري مثل المحاضرات. اذا شرحو لنا شي عملي نطبقه على اجهزتنا. كلفونا ناخذ دورات ونعرض لهم الشهادات ونتكلم عنها كبريزنتيشن. اشتغلنا على مشروع خاص فينا. كطالبة ذكاء الخطه ما تناسبنا لكن ناسبت علوم الحاسب وبنات التفاعل. المشرفين كويسين مره ومتعاونين.',
            ], // file#1:26
            [
                'company' => 'wajn_cyber',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'أمن سيبراني',
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 1, 'rating_organization' => 1,
                'review_text' => 'مافيش حاجة جيدة.',
            ], // file#1:27
            [
                'company' => 'makkah_emirate',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'مافي مسمى بالضبط لكن اي مهمة جديدة تشتغل عليها وهي متنوعة.',
            ], // file#1:29
            [
                'company' => 'makkah_tc',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'اغلب مهامي كانت تخص الاكسل إستفدت من هالناحيه كثير لكن اغلب الامور اللي تخص تخصصي اضطريت اسويها بنفسي بدون اشراف الجهة بسبب قلة خبرتهم في التخصصات التقنية.',
            ], // file#1:30
            [
                'company' => 'jeddah_amanah',
                'city' => 'جدة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'مافي مسمى. تحليل بيانات رخص وبلديات وحلو الشغل بس المشكله مافي تعليم يعني اذا فاهم شي تسويه مانت فاهم محد يعلمك. التدريب جيد بشكل عام بس الشغل مو ذاك الترتيب بس البيئة حلوه والموظفين عسل والمشرف متعاون.',
            ], // file#1:33
            [
                'company' => 'makkah_tc',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'الصدق انحديت عليهم بس كانو قمة بالتعامل الراقي. المهام كلها كانت ادخال بيانات لكن اعتقد لو جيت بوقت بدري مب بنص الترم راح يقبلوني عند دكتورة متخصصة. متعاونين من ناحية اذا تبينهم يدربونك بشي بتخصصك.',
            ], // file#1:34
            [
                'company' => 'jeddah_amanah',
                'city' => 'جدة',
                'duration_months' => 2, 'modality' => 'remote',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'review_text' => 'ضايعين حرفياً. ما كانت مهتمة بالتدريب ابد. احنا الي كنا نجري وراها عطينا شغل وتاسكات. ما كانت ترد وسافهتنا والوضع فوضى. التجربة فاشلة بتشيلون هم كل شي المشرف الاكاديمي وزيارته والتقارير والشغل. قالت خطتكم انتو سووها انا ما افهم في تخصصكم. ما كان في دوام اتفقت معانا يكون عن بعد.',
            ], // file#1:36
            [
                'company' => 'inpro_studio',
                'role_title' => 'Product Manager',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'تفاعل الإنسان مع الحاسب',
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'pros' => 'شغل على منتجات حقيقية, بيئة مريحة ومحفزة',
                'cons' => 'سوء إدارة, توقعات عالية من المتدربين, فريق غير متنوع بالتخصصات',
                'review_text' => 'UX/UI specialist then transferred to PM. As PM we took charge on how a real product was going to work from internal tools to external real working products. Training area was clean comfortable and suitable. Work environment was encouraging and engaging. Cons: mismanagement and expectations from trainees since our team wasn\'t diverse in majors we faced roadblocks.',
            ], // file#1:43
            [
                'company' => 'techwin',
                'role_title' => 'Front-End Developer',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => true,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'فرونت اند. برمجة موقع من الصفر مع فريق. هم اعطونا مشروع بداية التدريب كأنهم عملاء واحنا سميناه. بيئة العمل متعاونة ومثمرة تعليميا ومرحة.',
            ], // file#1:52
            [
                'company' => 'makkah_amanah',
                'city' => 'مكة المكرمة',
                'duration_months' => 2, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => false,
                'reviewer_major' => 'أمن سيبراني',
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 1, 'rating_team_environment' => 2, 'rating_organization' => 1,
                'pros' => 'أوقات الدوام مرنة',
                'cons' => 'لا مهام فعلية, تكليفات سطحية, تدريب تسليكي',
                'review_text' => 'ما كان فيه مهام فعلية أو واضحة. أغلب الوقت كان يمر بدون تكليفات مفيدة واذا تم إعطائي مهام فكانت سطحية جدًا أو مجرد تسليك. الميزة الوحيدة ان أوقات الدوام مرنة.',
            ], // file#1:56

            // File #8 — استبيان تجارب التدريب التعاوني – كلية الحاسبات 1445 (0-10 scale → 1-5; row 5 garbage skipped; row 43 anonymized skipped)
            [
                'company' => 'public_prosecution',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 4,
                'review_text' => 'تطوير الويب (web development) وتحليل البيانات. إضافة قوية للسيرة الذاتية والتعامل مع فريق متعاون وفرصة تعلّم تقنيات جديدة.',
            ], // file#8:6
            [
                'company' => 'lucidya',
                'city' => 'الرياض',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'AI. بيئة عمل محترفة ومهام عملية ومفيدة وفرصة تعلّم تقنيات جديدة والتعامل مع فريق متعاون.',
            ], // file#8:7
            [
                'company' => 'jeddah_amanah',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 3, 'rating_learning' => 2, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'Web development. إشراف وتوجيه جيد والتعامل مع فريق متعاون.',
            ], // file#8:8
            [
                'company' => 'rukaya',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'باك ايند. بيئة عمل محترفة ومهام عملية ومفيدة وإشراف وتوجيه جيد وفرصة تعلّم تقنيات جديدة.',
            ], // file#8:9
            [
                'company' => 'knowledgex',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'تفاعل الإنسان مع الحاسب',
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'Front end. التدريب يعتمد على التعلم الذاتي بشكل كبير جدا.',
            ], // file#8:10
            [
                'company' => 'rukaya',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'DevOps. مهام عملية ومفيدة وإشراف وتوجيه جيد وبيئة عمل محترفة. رح للقطاع الخاص الحكومي اغلبه تساهل وماراح تستفيد.',
            ], // file#8:11
            [
                'company' => 'nmc_weather',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'الذكاء الاصطناعي',
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'ذكاء اصطناعي. اعطونا 3 مشاريع تقريبا كل مشروع نشتغل عليه لفترة معينة وكل مرة نسوي برزنتيشن نوريهم الشغل ويعطونا فيد باك عليه.',
            ], // file#8:12
            [
                'company' => 'elm',
                'role_title' => 'متدرب - ادارة المشاريع التقنية',
                'department' => 'ادارة المشاريع التقنية - نسك',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'تجربة جدا جميلة ومثرية في شركة علم. البيئة متعاونه. دخلت بمسمى متدرب. الشركة كانت ماسكه مركز معلومات الحج والعمرة بالتعاون مع وزارة الحج فكانت ماسكه تطبيق نسك والموقع. في البداية علموني تقسيم الادارة والمشروع وكانوا مهتمين في المقابلة بخصوص ادارة المشاريع التقنية.',
            ], // file#8:13
            [
                'company' => 'qanoniah',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'هندسة البرمجيات',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'فل ستاك ويب. مهام عملية ومفيدة وفرصة تعلّم تقنيات جديدة والتعامل مع فريق متعاون.',
            ], // file#8:15
            [
                'company' => 'isolution',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'API. المهام اننا نسوي سياسات في ال API مافي شي تاني الى الان.',
            ], // file#8:16
            [
                'company' => 'site',
                'city' => 'الرياض',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => true, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'Cloud operations. ممتازه حتى الآن. بيئة عمل محترفة ومهام عملية وإشراف وتوجيه جيد.',
            ], // file#8:17
            [
                'company' => 'cars_syndicate',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'هندسة البرمجيات',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تطوير واجهات باستخدام framework Angular. مهام عملية ومفيدة وإشراف وتوجيه جيد والتعامل مع فريق متعاون وبيئة عمل محترفة.',
            ], // file#8:18
            [
                'company' => 'hajj',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 2, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 2,
                'review_text' => 'طبيعه المهام بيانات عن الحج والعمره واسوي لها تصوير بياني واعرض مقارنات بين الأعوام الماضيه. من العيوب المشرف الاكاديمي لازم اروح اكلمه عشان يعطيني مهام ومرات مايحضر ولا يبلغني. والعيب الثاني مستحيل يوجهني او يدلني على الشيء صحيح.',
            ], // file#8:19
            [
                'company' => 'education_admin_makkah',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'الاداره. مهام عملية ومفيدة وفرصة تعلّم تقنيات جديدة والتعامل مع فريق متعاون.',
            ], // file#8:23
            [
                'company' => 'laith_hospital',
                'department' => 'IT',
                'city' => 'الليث',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'تحليل بيانات. أغلبه كان اكسل شيت وتشارتس بس البيئة بصراحة فرقت معايا متعاونة ولطيفين.',
            ], // file#8:24
            [
                'company' => 'nmc_weather',
                'department' => 'Data Processing',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'التدريب مقسم على عدة مراحل اما تكون في قسم واحد أو في عدة أقسام. الخطة بتكون مقسمة على 3-4 مشاريع مرتبطة بالمركز بتشتغل عليها من الصفر وهم يتابعون معاك بشكل دوري.',
            ], // file#8:25
            [
                'company' => 'first_city',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'AI and data visualization. بيئة عمل محترفة وفرصة تعلّم تقنيات جديدة ومهام عملية ومفيدة.',
            ], // file#8:28
            [
                'company' => 'hajj',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 3, 'rating_learning' => 4, 'rating_real_work' => 3, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تحليل بيانات. المهام اللي اخذها نفس اللي درسته بس ببيانات اكبر ومعقدة اكثر ولازم تكون مفهومة. الشغل مو معقد حلو ومفيد واتعلمت على اشياء الجامعة تسحب عليها.',
            ], // file#8:29
            [
                'company' => 'haramain_authority',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'تدريب عام بدون مجال محدد. كل شي تمام مهمات بسيطة وعمل مريح متعاونين جدا معانا. مافي مكاتب مخصصة لنا بس نقعد بغرفة الاجتماعات اغلب الوقت.',
            ], // file#8:30
            [
                'company' => 'hajj',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'تحليل البيانات. المهام نسبة كبيرة منها Power BI وتحليلات. العيوب فيه بعض المهام ماتكون مجهزه لها ملف اكسل فأجمع بيانات واسوي ملف اكسل وبعدها احلل بPower BI. من اول يوم ابدا اطلبهم يعطونك مهام.',
            ], // file#8:31
            [
                'company' => 'techwin',
                'role_title' => 'Frontend Developer',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 5, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'Frontend تطوير واجهات أمامية. بيئة عمل متعاونه جدًا ورائعة. قدرت أتعلم اشياء كثيره في غصون شهر. النظام في تكوين يعتمد على التعليم ذاتي وطبعًا عندكم مشرف يشرف عليكم ويعطيكم سشنات وتاسكات. كل يوم عندنا ستاند اب صباحي ومسائي. الآن دخلنا على مشاريع تكوين. جدًا مبسوطة بأختياري لأني جالسه اتعلم كل يوم شيء جديد.',
            ], // file#8:32
            [
                'company' => 'hajj',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'review_text' => 'تحليل البيانات. تجربة جديدة وجميلة لكن شغلنا كله يقتصر حول الاكسل و POWER BI فقط وجمع وتنظيف البيانات. لاحظت ان اغلب الجهات الحكومية الكبيرة لازالت نوعا ما متأخرة من ناحية الحلول التقنية.',
            ], // file#8:41
            [
                'company' => 'wadi_makkah',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 5, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 5, 'rating_organization' => 5,
                'review_text' => 'تدريب عام لكل شي يخص مجال الحاسب. تجربة ممتازه وانصح فيها.',
            ], // file#8:42
            [
                'company' => 'holiday_inn_makkah',
                'department' => 'IT',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 5, 'rating_real_work' => 4, 'rating_team_environment' => 4, 'rating_organization' => 4,
                'review_text' => 'IT / تقنية معلومات. عمل جماعي ممتاز وتعامل الانظمه والبرامج المستخدمه وطريقة عملها والمشاكل التي تظهر وحلها. طبيعه المهام برمجه أجهزه جديده للعمل على النظام وبرمجه أقفال الأبواب والتأكد من عمل السوتشات بطريقة صحيحه.',
            ], // file#8:45
            [
                'company' => 'hira_hospital',
                'department' => 'IT - دعم فني',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 2, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 2, 'rating_organization' => 2,
                'review_text' => 'تجربة سيئة للامانة بسبب انه شغل وكرف ولكن تحس بدون فائدة بسبب انه دعم فني. تعرف طابعات وتشبك كمبيوترات للممرضين والدكاتره وتفصل سلك وترجعه. مشاكل بسيطة تنحل بثواني ما تفيد تخصصك للأسف.',
            ], // file#8:48
            [
                'company' => 'hajj',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علم البيانات',
                'rating_mentorship' => 1, 'rating_learning' => 1, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 1,
                'review_text' => 'تحليل بيانات وذكاء أعمال. ما كانت مفيدة. إذا ما طلبت شغل بنفسك ماراح يعطونك لو تقعد فاضي طول مدة التدريب. المهام اللي أعطوني إياها كانت بسيطة مرة مو بمستواي. البيئة جميلة لكن الإستفادة قليلة ولا تكاد تذكر.',
            ], // file#8:49
            [
                'company' => 'jeddah_tc',
                'department' => 'IT',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 4, 'rating_learning' => 3, 'rating_real_work' => 3, 'rating_team_environment' => 4, 'rating_organization' => 3,
                'pros' => 'مهندسين متعاونين, تجربة مفيدة',
                'cons' => 'الموقع بعيد جداً عن أحياء شمال ووسط جدة',
                'review_text' => 'تدريب عام. التدريب في قسم الIT بالكليه. تجي طلبات صيانة معامل وحل مشاكل وتثبيت ايميج وإدخال الاجهزه للدومين. طبعا تعاون المهندسين ويحاولون يعطونكم تجربه ممتازه ومفيده. السلبيه الوحيده مكان الكليه بعيد جدا عن احياء شمال ووسط جدة.',
            ], // file#8:54
            [
                'company' => 'kaah_makkah',
                'department' => 'دعم فني',
                'city' => 'مكة المكرمة',
                'duration_months' => 6, 'modality' => 'onsite',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'دعم فني. مهام عملية ومفيدة وإشراف وتوجيه جيد والتعامل مع فريق متعاون وفرصة تعلّم تقنيات جديدة وبيئة عمل محترفة.',
            ], // file#8:55
            [
                'company' => 'jeddah_amanah',
                'city' => 'جدة',
                'duration_months' => 6, 'modality' => 'hybrid',
                'stipend_sar' => null, 'job_offer' => false, 'mixed_env' => null,
                'reviewer_major' => 'علوم حاسب',
                'rating_mentorship' => 3, 'rating_learning' => 3, 'rating_real_work' => 2, 'rating_team_environment' => 3, 'rating_organization' => 3,
                'review_text' => 'تدريب عام. التدريب كويس. تقدر تقول اغلب الايام اونلاين ايام الدوام الحضوري قليلة. يعطون مهام تدريبية وورش العمل جدا مفيدة.',
            ], // file#8:56
        ];
    }
}
