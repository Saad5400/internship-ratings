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
            $ids[$key] = Company::create($data)->id;
        }

        foreach ($this->ratings() as $row) {
            $key = $row['company'];
            unset($row['company']);
            $row['company_id'] = $ids[$key];
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
            'tetco' => [
                'name' => 'شركة تطوير للخدمات التعليمية (tetco)',
                'type' => CompanyType::Private,
                'website' => 'https://tetco.sa/',
                'description' => null,
                'status' => 'approved',
            ],
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
            'mob' => [
                'name' => 'شركة الحافلات الحديثة',
                'type' => CompanyType::Private,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
            'gsac' => [
                'name' => 'النقابة العامة للسيارات',
                'type' => CompanyType::NonProfit,
                'website' => null,
                'description' => null,
                'status' => 'approved',
            ],
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
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ratings(): array
    {
        return [
            // Populated per-file as each CSV is transcribed.
        ];
    }
}
