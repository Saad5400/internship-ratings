<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Support\Arabic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the curated COOP training-providers directory.
 *
 * Source: "تدريب تعاوني - COOP - جهات تدريب تعاوني ـ COOP.csv" (119 raw rows).
 * Category header rows (الهيئات، المؤسسات، الوزارات، المراكز، الصناديق، بنوك، أخرى)
 * were stripped at import time — only real organizations are inlined below.
 *
 * Idempotent: matches existing records by Arabic-normalized name so that
 * re-runs and overlap with other seeders do not produce duplicates.
 */
class CoopDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'الهيئة السعودية للبيانات والذكاء الاصطناعي',
                'website' => 'https://sdaia.gov.sa/',
                'description' => "الإيميل: info@sdaia.gov.sa\nLinkedIn: https://www.linkedin.com/company/sdaia-ksa/",
            ],
            [
                'name' => 'الهيئة السعودية للمراجعين والمحاسبين',
                'website' => 'https://socpa.org.sa/Socpa/Contact-Us/Submit-Request.aspx',
                'description' => 'الإيميل: info@socpa.org.sa',
            ],
            [
                'name' => 'الهيئة العليا للآمن الصناعي',
                'website' => 'https://hcis.gov.sa/',
                'description' => 'الإيميل: info@hcis.gov.sa',
            ],
            [
                'name' => 'الهيئة العامة لعقارات الدولة',
                'website' => 'https://spga.gov.sa/',
                'description' => 'الإيميل: CS@SPGA.GOV.SA',
            ],
            [
                'name' => 'هيئة حقوق الإنسان السعودية',
                'website' => 'https://www.hrc.gov.sa/ar-sa/Pages/home.aspx',
                'description' => "الإيميل: info@hrc.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-human-rights-commission/",
            ],
            [
                'name' => 'هيئة تنمية الصادرات السعودية',
                'website' => 'https://www.saudiexports.sa/ar/pages/default.aspx',
                'description' => "الإيميل: info@saudiexports.sa ; CC@saudiexports.sa\nLinkedIn: https://www.linkedin.com/company/saudi-exports/",
            ],
            [
                'name' => 'هيئة تقويم التعليم والتدريب',
                'website' => 'https://www.etec.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: Faq@etec.gov.sa\nLinkedIn: https://www.linkedin.com/company/etecksa/",
            ],
            [
                'name' => 'الهيئة الملكية لمدينة الرياض',
                'website' => 'https://www.rcrc.gov.sa/',
                'description' => "الإيميل: info@rcrc.gov.sa\nLinkedIn: https://www.linkedin.com/company/the-royal-commission-for-riyadh-city/",
            ],
            [
                'name' => 'الهيئة السعودية للمواصفات والمقاييس والجودة',
                'website' => 'https://www.saso.gov.sa/ar/pages/default.aspx',
                'description' => "الإيميل: info@saso.gov.sa\nLinkedIn: https://www.linkedin.com/company/sasogov/",
            ],
            [
                'name' => 'هيئة المدن الاقتصادية السعودية',
                'website' => 'http://www.ecza.gov.sa/360/pages/ar/home',
                'description' => "الإيميل: info@ecza.gov.sa\nLinkedIn: https://www.linkedin.com/company/ecza-gov/?originalSubdomain=sa",
            ],
            [
                'name' => 'هيئة المحتوى المحلي
والمشتريات الحكومية السعودية',
                'website' => 'https://lcgpa.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: info@lcgpa.gov.sa\nLinkedIn: https://www.linkedin.com/company/local-content-and-government-procurement-authority/",
            ],
            [
                'name' => 'هيئة السوق المالية',
                'website' => 'https://cma.org.sa/Pages/default.aspx',
                'description' => "الإيميل: info@cma.org.sa\nLinkedIn: https://www.linkedin.com/company/capital-market-authority/",
            ],
            [
                'name' => 'هيئة الاتصالات وتقنية المعلومات',
                'website' => 'https://www.citc.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: info@citc.gov.sa\nLinkedIn: https://www.linkedin.com/company/citc_sa/life/e38a9f82-39a3-4cab-bb3e-ea72ba4a9f62/",
            ],
            [
                'name' => 'الهيئة الوطنية لمكافحة الفساد',
                'website' => 'https://www.nazaha.gov.sa/ar-sa/pages/default.aspx',
                'description' => "الإيميل: info@nazaha.gov.sa\nLinkedIn: https://www.linkedin.com/company/national-anti-corruption-commission-nazaha",
            ],
            [
                'name' => 'الهيئة الملكية لمحافظة العلا',
                'website' => 'https://www.rcu.gov.sa/',
                'description' => "الإيميل: rcuinfo@rcu.gov.sa\nLinkedIn: https://www.linkedin.com/company/the-royal-commission-for-alula/",
            ],
            [
                'name' => 'الهيئة الملكية للجبيل وينبع',
                'website' => 'https://www.rcjy.gov.sa/ar-SA/Pages/default.aspx',
                'description' => 'LinkedIn: https://www.linkedin.com/company/royal-commission-for-jubail-and-yanbu/',
            ],
            [
                'name' => 'الهيئة العامة للمنافسة',
                'website' => 'https://gac.gov.sa/',
                'description' => "الإيميل: info@gac.gov.sa\nLinkedIn: https://www.linkedin.com/company/general-authority-for-competition/",
            ],
            [
                'name' => 'الهيئة العامة للغذاء والدواء',
                'website' => 'https://old.sfda.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: webmaster@sfda.gov.sa\nLinkedIn: https://www.linkedin.com/company/sfda/",
            ],
            [
                'name' => 'الهيئة العامة للعقار',
                'website' => 'https://www.rega.gov.sa/',
                'description' => "الإيميل: info@rega.gov.sa\nLinkedIn: https://www.linkedin.com/company/rega-sa/",
            ],
            [
                'name' => 'الهيئة العامة للطيران المدني',
                'website' => 'https://gaca.gov.sa/web/ar-sa/page/home',
                'description' => "الإيميل: HR-Support@gaca.gov.sa\nLinkedIn: https://www.linkedin.com/company/civil-aviation-authority_2/",
            ],
            [
                'name' => 'الهيئة العامة للترفيه',
                'website' => 'https://www.gea.gov.sa/ar',
                'description' => "الإيميل: info@gea.gov.sa\nLinkedIn: https://www.linkedin.com/company/general-entertainment-authority",
            ],
            [
                'name' => 'الهيئة العامة للإعلام المرئي والمسموع',
                'website' => 'https://www.gcam.gov.sa',
                'description' => "الإيميل: info@gcam.gov.sa\nLinkedIn: https://www.linkedin.com/company/general-commission-of-audiovisual-media",
            ],
            [
                'name' => 'الهيئة العامة للإحصاء',
                'website' => 'https://www.stats.gov.sa/ar',
                'description' => "الإيميل: info@stats.gov.sa\nLinkedIn: https://www.linkedin.com/company/general-authority-for-statistics-gastat",
            ],
            [
                'name' => 'الهيئة السعودية للملكية الفكرية',
                'website' => 'https://www.saip.gov.sa',
                'description' => "الإيميل: Recruitment@saip.gov.sa\nLinkedIn: https://www.linkedin.com/company/saipksa",
            ],
            [
                'name' => 'الهيئة السعودية للمدن الصناعية ومناطق التقنية',
                'website' => null,
                'description' => "الإيميل: info@modon.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-authority-for-industrial-cities-and-technology-zones",
            ],
            [
                'name' => 'الهيئة السعودية للفضاء',
                'website' => 'https://saudispace.gov.sa/',
                'description' => "الإيميل: Info@ssc.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-space-commission",
            ],
            [
                'name' => 'الهلال الأحمر السعودي',
                'website' => 'https://www.srca.org.sa/',
                'description' => "الإيميل: info@srca.org.sa\nLinkedIn: https://www.linkedin.com/company/red-crescent-saudi",
            ],
            [
                'name' => 'الهيئة السعودية للتخصصات الصحية',
                'website' => null,
                'description' => 'LinkedIn: https://www.linkedin.com/company/saudi-commission-for-health-specialties-scfhs-',
            ],
            [
                'name' => 'الهيئة الوطنية للأمن السيبراني',
                'website' => 'https://nca.gov.sa/',
                'description' => "الإيميل: Info@nca.gov.sa\nLinkedIn: https://www.linkedin.com/company/national-cybersecurity-authority",
            ],
            [
                'name' => 'الهيئة السعودية للسياحة',
                'website' => 'https://sta.gov.sa/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/saudi-tourism-authority',
            ],
            [
                'name' => 'الهيئة العامة للمنشآت الصغيرة والمتوسطة (السعودية)',
                'website' => 'https://www.monshaat.gov.sa/',
                'description' => "الإيميل: Info@monshaat.gov.sa\nLinkedIn: https://www.linkedin.com/company/monshaatsa",
            ],
            [
                'name' => 'الهيئة العامة للأوقاف (السعودية)',
                'website' => 'https://www.awqaf.gov.sa/ar',
                'description' => "الإيميل: info@awqaf.gov.sa\nLinkedIn: https://www.linkedin.com/company/general-authority-for-awqaf",
            ],
            [
                'name' => 'هيئة الزكاة والضريبة والجمارك',
                'website' => 'https://gazt.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: info@zatca.gov.sa\nLinkedIn: https://www.linkedin.com/company/general-authority-of-zakat-&-tax",
            ],
            [
                'name' => 'البنك المركزي السعودي',
                'website' => 'https://www.sama.gov.sa/ar-sa/Pages/default.aspx',
                'description' => "الإيميل: info@sama.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-central-bank-sama/?originalSubdomain=sa",
            ],
            [
                'name' => 'المؤسسة العامة لتحلية المياه المالحة',
                'website' => 'https://www.swcc.gov.sa/Arabic/Pages/Home.aspx',
                'description' => "الإيميل: Ialhassan@swcc.gov.sa TCPR@swcc.gov.sa\nLinkedIn: https://www.linkedin.com/company/swdtcksa/?originalSubdomain=sa",
            ],
            [
                'name' => 'المؤسسة العامة للتدريب التقني والمهني',
                'website' => 'https://www.tvtc.gov.sa/',
                'description' => "الإيميل: cso@tvtc.gov.sa\nLinkedIn: https://www.linkedin.com/school/tvtc/?originalSubdomain=sa",
            ],
            [
                'name' => 'المؤسسة العامة للتقاعد',
                'website' => 'https://www.pension.gov.sa/',
                'description' => "الإيميل: governor@pension,gov.sa\nLinkedIn: https://www.linkedin.com/company/public-pension-agency/?originalSubdomain=sa",
            ],
            [
                'name' => 'مؤسسة الملك عبد العزيز ورجاله للموهبة والإبداع',
                'website' => 'https://www.mawhiba.org/Ar/About/who/Pages/Brief.aspx',
                'description' => "الإيميل: Info@mawhiba.org.sa\nLinkedIn: https://www.linkedin.com/company/mawhiba/",
            ],
            [
                'name' => 'وزارة الخارجية',
                'website' => 'https://www.mofa.gov.sa/',
                'description' => 'الإيميل: info@mofa.gov.sa',
            ],
            [
                'name' => 'وزارة المالية',
                'website' => 'https://www.mof.gov.sa/Pages/default.aspx',
                'description' => "الإيميل: ccc@mof.gov.sa\nLinkedIn: https://www.linkedin.com/company/mofksa",
            ],
            [
                'name' => 'وزارة العدل',
                'website' => 'https://www.moj.gov.sa/ar/pages/default.aspx',
                'description' => "الإيميل: 1950@moj.gov.sa\nLinkedIn: https://www.linkedin.com/company/وزارة-العدل",
            ],
            [
                'name' => 'وزارة الموارد البشرية والتنمية الاجتماعية',
                'website' => 'https://hrsd.gov.sa/',
                'description' => "الإيميل: info@hrsd.gov.sa\nLinkedIn: https://www.linkedin.com/company/ministry-of-human-resources-and-social-development-ksa",
            ],
            [
                'name' => 'وزارة التعليم',
                'website' => 'https://www.moe.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: contact@mohe.gov.sa - info@mohe.gov.sa\nLinkedIn: https://sa.linkedin.com/company/ministry-of-education-saudi-arabia",
            ],
            [
                'name' => 'وزارة الاقتصاد والتخطيط',
                'website' => 'https://www.mep.gov.sa/ar',
                'description' => "الإيميل: info@mep.gov.sa\nLinkedIn: https://sa.linkedin.com/company/mepsaudi",
            ],
            [
                'name' => 'وزارة الاستثمار',
                'website' => 'https://misa.gov.sa/ar/',
                'description' => "الإيميل: InvestorCare@misa.gov.sa\nLinkedIn: https://www.linkedin.com/company/ministry-of-investment-ksa",
            ],
            [
                'name' => 'وزارة الطاقة',
                'website' => 'http://www.moenergy.gov.sa/arabic/Pages/default.aspx',
                'description' => 'LinkedIn: https://sa.linkedin.com/company/saudi-ministry-of-energy',
            ],
            [
                'name' => 'وزارة الثقافة',
                'website' => 'https://www.moc.gov.sa/ar',
                'description' => "الإيميل: info@moc.gov.sa\nLinkedIn: https://www.linkedin.com/company/mocsaudi/",
            ],
            [
                'name' => 'وزارة الاسكان',
                'website' => 'https://www.housing.gov.sa/',
                'description' => "الإيميل: info@housing.gov.sa\nLinkedIn: https://www.linkedin.com/company/ministry-of-housing-saudi-arabia/",
            ],
            [
                'name' => 'وزارة الاعلام',
                'website' => 'https://media.gov.sa/',
                'description' => "الإيميل: info@media.gov.sa\nLinkedIn: https://www.linkedin.com/company/moci_ksa/",
            ],
            [
                'name' => 'وزارة الاتصالات وتقنية المعلومات',
                'website' => 'https://www.mcit.gov.sa/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/mcitgovsa/',
            ],
            [
                'name' => 'وزارة البيئة والمياه والزراعة',
                'website' => 'https://www.mewa.gov.sa/ar/Pages/default.aspx',
                'description' => 'LinkedIn: https://www.linkedin.com/company/ministry-of-environment-water-and-agriculture/',
            ],
            [
                'name' => 'وزارة التجارة',
                'website' => 'https://mc.gov.sa/ar/pages/default.aspx',
                'description' => "الإيميل: CS@mc.gov.sa\nLinkedIn: https://www.linkedin.com/company/ministry-of-commerce-saudiarabia/",
            ],
            [
                'name' => 'وزارة الصحة',
                'website' => 'https://www.moh.gov.sa/Pages/Default.aspx',
                'description' => "الإيميل: irecsupport@moh.gov.sa\nLinkedIn: https://www.linkedin.com/company/ministry-of-health-saudi-arabia/",
            ],
            [
                'name' => 'وزارة الصناعة والثروه المعدنية',
                'website' => 'https://mim.gov.sa/',
                'description' => "الإيميل: info@mim.gov.sa\nLinkedIn: https://www.linkedin.com/company/mimgov/",
            ],
            [
                'name' => 'وزارة النقل',
                'website' => 'https://www.mot.gov.sa/ar/Pages/default.aspx',
                'description' => 'LinkedIn: https://www.linkedin.com/company/ministry-of-transport-saudi-arabia/',
            ],
            [
                'name' => 'المركز السعودي لكفاءة الطاقة',
                'website' => 'https://www.seec.gov.sa/',
                'description' => "الإيميل: info@seec.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-energy-efficiency-program/",
            ],
            [
                'name' => 'المركز الوطني للتصديق الرقمي',
                'website' => 'https://www.ncdc.gov.sa/',
                'description' => "الإيميل: info@ncdc.gov.sa\nLinkedIn: https://www.linkedin.com/company/national-center-for-digital-certification-ministry-of-communications-and-it-saudi-arabia-/",
            ],
            [
                'name' => 'المركز الوطني للتنافسية',
                'website' => 'https://www.ncc.gov.sa/ar/Pages/default.aspx',
                'description' => "الإيميل: info@ncc.gov.sa\nLinkedIn: https://www.linkedin.com/company/ksancc/",
            ],
            [
                'name' => 'المركزالوطني للذكاء الاصطناعي',
                'website' => 'https://sdaia.gov.sa/ncai/',
                'description' => "الإيميل: info@sdaia.gov.sa\nLinkedIn: https://www.linkedin.com/company/sdaia-ksa/",
            ],
            [
                'name' => 'المركز الوطني للوثائق والمحفوظات',
                'website' => 'https://ncar.gov.sa/',
                'description' => 'الإيميل: info@ncar.gov.sa',
            ],
            [
                'name' => 'مركز الملك عبدالله للدراسات والبحوث',
                'website' => 'https://www.kapsarc.org/ar/about/',
                'description' => "الإيميل: jobs@kapsarc.org\nLinkedIn: https://www.linkedin.com/company/kapsarc/",
            ],
            [
                'name' => 'الصندوق السعودي للتنمية',
                'website' => 'https://www.sfd.gov.sa/',
                'description' => "الإيميل: info@sfd.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-fund-for-development/",
            ],
            [
                'name' => 'صندوق الاستثمارات العامة',
                'website' => 'https://www.pif.gov.sa/ar/Pages/default.aspx#1',
                'description' => 'LinkedIn: https://www.linkedin.com/company/pifsaudi/',
            ],
            [
                'name' => 'صندوق التنمية الزراعية',
                'website' => 'https://adf.gov.sa/ar/Pages/default.aspx',
                'description' => 'LinkedIn: https://www.linkedin.com/company/adfgovsa/',
            ],
            [
                'name' => 'صندوق التنمية الصناعية السعودي',
                'website' => 'https://www.sidf.gov.sa/ar/Pages/Home.aspx',
                'description' => "الإيميل: info@sidf.gov.sa\nLinkedIn: https://www.linkedin.com/company/saudi-industrial-development-fund/",
            ],
            [
                'name' => 'صندوق التنمية العقارية',
                'website' => 'https://www.housing.gov.sa/ar/related-links/140',
                'description' => "الإيميل: info@housing.gov.sa\nLinkedIn: https://www.linkedin.com/company/real-estate-development-fund/",
            ],
            [
                'name' => 'صندوق التنمية الوطني',
                'website' => 'https://www.ndf.gov.sa/',
                'description' => "الإيميل: info@ndf.gov.sa\nLinkedIn: https://www.linkedin.com/company/ndfsaudi/",
            ],
            [
                'name' => 'صندوق تنمية الموارد البشرية',
                'website' => 'https://www.hrdf.org.sa/Home',
                'description' => 'LinkedIn: https://www.linkedin.com/company/hrdf/',
            ],
            [
                'name' => 'البنك الأهلي التجاري',
                'website' => 'https://www.alahli.com/ar-sa/Pages/RB-NCB-Home-New.aspx',
                'description' => "الإيميل: contactus@alahli.com\nLinkedIn: https://www.linkedin.com/company/ncb/",
            ],
            [
                'name' => 'البنك السعودي للإستثمار',
                'website' => 'https://www.saib.com.sa/ar',
                'description' => 'LinkedIn: https://www.linkedin.com/company/saudi-investment-bank/',
            ],
            [
                'name' => 'مصرف الانماء',
                'website' => 'https://www.alinma.com/Career/coopAr.html',
                'description' => "الإيميل: info@alinma.com\nLinkedIn: https://www.linkedin.com/company/alinma-bank/",
            ],
            [
                'name' => 'البنك السعودي الفرنسي',
                'website' => 'https://www.alfransi.com.sa/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/banque-saudi-fransi/',
            ],
            [
                'name' => 'بنك الرياض',
                'website' => 'https://www.riyadbank.com/ar/personal-banking',
                'description' => 'LinkedIn: https://www.linkedin.com/company/riyad-bank/',
            ],
            [
                'name' => 'مجموعة سامبا المالية',
                'website' => 'https://m.samba.com/ar/home/index.aspx',
                'description' => "الإيميل: customerCare@samba.com\nLinkedIn: https://www.linkedin.com/company/samba-financial-group/",
            ],
            [
                'name' => 'مصرف الراجحي',
                'website' => 'https://www.alrajhibank.com.sa/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/alrajhibank/',
            ],
            [
                'name' => 'البنك العربي الوطني',
                'website' => 'http://www.anb.com.sa/Default.aspx?tabid=634&language=ar-SA',
                'description' => 'LinkedIn: https://www.linkedin.com/company/arab-national-bank/',
            ],
            [
                'name' => 'بنك البلاد',
                'website' => 'https://www.bankalbilad.com/ar/about/Pages/Training.aspx',
                'description' => 'LinkedIn: https://www.linkedin.com/company/bankalbilad/',
            ],
            [
                'name' => 'بنك الجزيرة',
                'website' => 'https://www.bankaljazira.com/ar-sa/',
                'description' => "الإيميل: Call_Center_Supervisor@BAJ.Com.SA\nLinkedIn: https://www.linkedin.com/company/bank-al-jazira/",
            ],
            [
                'name' => 'stc',
                'website' => 'https://careers.stc.com.sa/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/stc/',
            ],
            [
                'name' => 'Mobily',
                'website' => 'https://careers.mobily.com.sa/en/saudi-arabia/jobs/coop-program-1100006246/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/mobily/',
            ],
            [
                'name' => 'البحري',
                'website' => 'https://careers.bahri.sa/job/Riyadh-COOP-Program-2020/571006001/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/bahri/',
            ],
            [
                'name' => 'سابك',
                'website' => 'https://www.sabic.com/ar/careers/middle-east-africa/students-and-fresh-graduates',
                'description' => 'LinkedIn: https://www.linkedin.com/company/sabic/',
            ],
            [
                'name' => 'Aramco',
                'website' => 'https://www.aramco.com/ar/website-information/contactus',
                'description' => "الإيميل: saempsa_webmaster@aramco.com\nLinkedIn: https://www.linkedin.com/company/aramco/",
            ],
            [
                'name' => 'Saudi Railway Company',
                'website' => 'https://www.sar.com.sa/Home-aspx-lang-ar-sa.aspx',
                'description' => "الإيميل: Training@sar.com.sa\nLinkedIn: https://www.linkedin.com/company/sarsaudirailway/",
            ],
            [
                'name' => 'KPMG',
                'website' => 'https://home.kpmg/xx/en/home.html',
                'description' => 'LinkedIn: https://www.linkedin.com/company/kpmg/',
            ],
            [
                'name' => 'EY',
                'website' => 'https://www.ey.com/en_sa',
                'description' => 'LinkedIn: https://www.linkedin.com/company/ernstandyoung/',
            ],
            [
                'name' => 'PWC',
                'website' => 'https://www.pwc.com/m1/en/media-centre/2017/pwc-saudi-arabia-welcomes-70-new-graduates-ar.html',
                'description' => 'LinkedIn: https://www.linkedin.com/company/pwc/',
            ],
            [
                'name' => 'DELOITTE',
                'website' => 'https://www2.deloitte.com/sa/en/legal/about-deloitte-saudi-arabia.html',
                'description' => "الإيميل: emakhdoum@deloitte.com\nLinkedIn: https://www.linkedin.com/company/deloitte/",
            ],
            [
                'name' => 'McKinsey & Company',
                'website' => 'https://www.mckinsey.com/middle-east/ar/riyadh',
                'description' => 'LinkedIn: https://www.linkedin.com/company/mckinsey/',
            ],
            [
                'name' => 'Sanabil Investments',
                'website' => 'http://www.sanabil.sa/ar/pages/default.aspx',
                'description' => 'LinkedIn: https://www.linkedin.com/company/sanabil-investments/',
            ],
            [
                'name' => 'التعاونية',
                'website' => 'https://www.tawuniya.com.sa/about-us/contact-us',
                'description' => "الإيميل: info@tawuniya.com.sa\nLinkedIn: https://www.linkedin.com/company/tawuniya/",
            ],
            [
                'name' => 'شركة ابو حيمد وال الشيخ والحقباني بالتعاون مع Clifford Chance',
                'website' => 'https://www.ashlawksa.com/en/home.html',
                'description' => "الإيميل: Info.ASH@ashlawksa.com\nLinkedIn: https://www.linkedin.com/company/abuhimed-alsheikh-alhagbani-law-firm-as-h-in-co-operation-with-clifford-chance/",
            ],
            [
                'name' => 'شركة الخشيم وشركاه',
                'website' => 'https://www.khoshaim.com/intern/',
                'description' => "الإيميل: info@khoshaim.com\nLinkedIn: https://www.linkedin.com/company/khoshaim",
            ],
            [
                'name' => 'شركة الضبعان وشركاه بالتعاون مع Evershed Sutherland',
                'website' => 'https://www.eversheds-sutherland.com/global/en/where/middle-east/saudi-arabia/overview/careers/index.page',
                'description' => "الإيميل: HRRiyadh@aldhabaan-es.com\nLinkedIn: https://www.linkedin.com/company/aldhabaan-and-partners",
            ],
            [
                'name' => 'EKP Legal in association AlEnezee Legal Counsel',
                'website' => 'https://www.ekplegal.com/index.php',
                'description' => "الإيميل: info@ekplegal.com\nLinkedIn: https://www.linkedin.com/company/ekplegal",
            ],
            [
                'name' => 'شركة الصعيب وشركاه للمحاماة',
                'website' => 'http://soaiblaw.com',
                'description' => "الإيميل: soaib@soaiblaw.com\nLinkedIn: https://www.linkedin.com/in/soaiblaw-firm-0a238592",
            ],
            [
                'name' => 'جدوى للاستثمار',
                'website' => 'http://www.jadwa.com/en',
                'description' => "الإيميل: info@jadwa.com\nLinkedIn: https://www.linkedin.com/company/jadwa-investment",
            ],
            [
                'name' => 'تداول',
                'website' => 'https://www.tadawul.com.sa/wps/portal/tadawul/markets/equities/market-watch?locale=ar',
                'description' => "الإيميل: Co.op.training@tadawul.com.sa\nLinkedIn: https://www.linkedin.com/company/tadawul",
            ],
            [
                'name' => 'تقنية',
                'website' => 'https://taqnia.com/en/',
                'description' => 'LinkedIn: https://www.linkedin.com/company/taqnia-sa',
            ],
            [
                'name' => 'أديم المالية',
                'website' => 'http://adeemcapital.com/ar-sa/',
                'description' => "الإيميل: hr@adeemcapital.com\nLinkedIn: https://www.linkedin.com/company/adeem-capital",
            ],
            [
                'name' => 'دراية المالية',
                'website' => 'http://www.derayah.com/web/ar/index.html',
                'description' => "الإيميل: HR@derayah.com\nLinkedIn: https://www.linkedin.com/company/derayah",
            ],
            [
                'name' => 'فالكم المالية',
                'website' => 'http://www.falcom.com.sa/',
                'description' => "الإيميل: hr@falcom.com.sa\nLinkedIn: https://www.linkedin.com/company/falcom-financial-services",
            ],
            [
                'name' => 'ثقة',
                'website' => 'https://thiqah.sa/',
                'description' => "الإيميل: coop@thiqah.sa\nLinkedIn: https://www.linkedin.com/company/thiqah-business-services-company",
            ],
            [
                'name' => 'شركة يونيليفر',
                'website' => 'https://www.unilever.com/',
                'description' => "الإيميل: nadia.malaika@unilever.com nada.al-hashimi@unilever.com\nLinkedIn: https://www.linkedin.com/company/unilever",
            ],
            [
                'name' => 'شركة معادن',
                'website' => 'https://www.maaden.com.sa/ar/careers/working',
                'description' => "الإيميل: GARNIS@MAADEN.COM.SA info@maaden.com.sa\nLinkedIn: https://www.linkedin.com/company/maaden",
            ],
            [
                'name' => 'شركة كسب المالية',
                'website' => 'http://www.kasbcapital.sa/index.php/ar/',
                'description' => "الإيميل: ialalwan@ksb.com.sa\nLinkedIn: https://www.linkedin.com/company/ksb-capital-group",
            ],
            [
                'name' => 'سمة Saudi Credit Bureau (SIMAH)',
                'website' => 'https://www.simah.com/en/personal/pages/default.aspx',
                'description' => "الإيميل: kalmzaini@simah.com\nLinkedIn: https://www.linkedin.com/company/saudi-credit-bureau---simah",
            ],
            [
                'name' => 'تكامل',
                'website' => 'https://join.takamol.com.sa/?page_id=4',
                'description' => 'LinkedIn: https://www.linkedin.com/company/takamolholding',
            ],
            [
                'name' => 'tetco',
                'website' => 'https://tetco.sa/ar/default.aspx',
                'description' => "الإيميل: career@tetco.sa\nLinkedIn: https://www.linkedin.com/company/tetco-group",
            ],
            [
                'name' => 'وساطه المالية',
                'website' => 'http://www.wasatah.com.sa/index.php/ar/',
                'description' => "الإيميل: info@wasatah.com.sa\nLinkedIn: https://www.linkedin.com/company/al-wasatah-al-maliah-co.-wasatah-capital-",
            ],
        ];

        $created = 0;
        $reused = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, &$created, &$reused, &$skipped) {
            foreach ($rows as $row) {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                $normalized = Arabic::normalize($name);

                $company = Company::firstOrCreate(
                    ['name_normalized' => $normalized],
                    [
                        'name' => $name,
                        'website' => $row['website'] ?? null,
                        'description' => $row['description'] ?? null,
                        'status' => 'approved',
                    ]
                );

                if ($company->wasRecentlyCreated) {
                    $created++;
                } else {
                    $reused++;
                }
            }
        });

        $this->command?->info(sprintf(
            'CoopDirectorySeeder: %d created, %d reused, %d skipped (of %d rows).',
            $created,
            $reused,
            $skipped,
            count($rows)
        ));
    }
}
