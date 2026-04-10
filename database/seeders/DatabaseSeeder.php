<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::factory()->create([
            'name' => 'مدير النظام',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Approved companies with ratings
        $companies = [
            [
                'name' => 'شركة أرامكو السعودية',
                'website' => 'https://www.aramco.com',
                'description' => 'شركة طاقة عالمية رائدة في مجال النفط والغاز',
                'status' => 'approved',
            ],
            [
                'name' => 'شركة سابك',
                'website' => 'https://www.sabic.com',
                'description' => 'شركة رائدة في صناعة الكيماويات والبتروكيماويات',
                'status' => 'approved',
            ],
            [
                'name' => 'شركة الاتصالات السعودية',
                'website' => 'https://www.stc.com.sa',
                'description' => 'مزود خدمات اتصالات وتقنية معلومات',
                'status' => 'approved',
            ],
            [
                'name' => 'بنك الراجحي',
                'website' => 'https://www.alrajhibank.com.sa',
                'description' => 'أكبر بنك إسلامي في العالم',
                'status' => 'approved',
            ],
            [
                'name' => 'شركة تقنية ناشئة',
                'website' => null,
                'description' => 'شركة ناشئة في مجال التقنية',
                'status' => 'pending',
            ],
            [
                'name' => 'مؤسسة استشارية',
                'website' => null,
                'description' => null,
                'status' => 'pending',
            ],
        ];

        foreach ($companies as $data) {
            Company::create($data);
        }

        // Sample ratings for approved companies
        $ratings = [
            [
                'company_id' => 1,
                'role_title' => 'مهندس برمجيات',
                'department' => 'تقنية المعلومات',
                'city' => 'الظهران',
                'duration_months' => 3,
                'sector' => 'private',
                'modality' => 'onsite',
                'stipend_sar' => 3000,
                'had_supervisor' => true,
                'mixed_env' => true,
                'job_offer' => true,
                'rating_mentorship' => 5,
                'rating_learning' => 5,
                'rating_culture' => 5,
                'rating_compensation' => 4,
                'overall_rating' => 5,
                'recommendation' => 'yes',
                'review_text' => 'تجربة تدريب ممتازة. المرشد كان داعماً جداً وتعلمت الكثير عن تطوير البرمجيات في بيئة مؤسسية كبيرة.',
                'pros' => 'بيئة مهنية، مرشد داعم، مكافأة جيدة',
                'cons' => 'الإجراءات الإدارية قد تكون بطيئة',
                'reviewer_name' => 'أحمد',
                'reviewer_major' => 'هندسة البرمجيات',
            ],
            [
                'company_id' => 1,
                'role_title' => 'محلل بيانات',
                'department' => 'تحليل البيانات',
                'city' => 'الظهران',
                'duration_months' => 6,
                'sector' => 'private',
                'modality' => 'hybrid',
                'stipend_sar' => 2500,
                'had_supervisor' => true,
                'mixed_env' => true,
                'job_offer' => false,
                'rating_mentorship' => 4,
                'rating_learning' => 5,
                'rating_culture' => 4,
                'rating_compensation' => 5,
                'overall_rating' => 4,
                'recommendation' => 'yes',
                'review_text' => 'فرصة رائعة للعمل مع بيانات حقيقية وأدوات تحليل متقدمة. التعويض المادي ممتاز.',
                'pros' => 'بيانات حقيقية، أدوات متقدمة',
                'cons' => null,
                'reviewer_name' => null,
                'reviewer_major' => 'علوم الحاسب',
            ],
            [
                'company_id' => 2,
                'role_title' => 'مهندس كيميائي',
                'department' => 'الإنتاج',
                'city' => 'الجبيل',
                'duration_months' => 4,
                'sector' => 'private',
                'modality' => 'onsite',
                'stipend_sar' => 2000,
                'had_supervisor' => false,
                'mixed_env' => false,
                'job_offer' => false,
                'rating_mentorship' => 2,
                'rating_learning' => 4,
                'rating_culture' => 3,
                'rating_compensation' => 3,
                'overall_rating' => 4,
                'recommendation' => 'maybe',
                'review_text' => 'بيئة عمل مهنية وتعلمت الكثير عن العمليات الصناعية. الإرشاد كان يمكن أن يكون أفضل.',
                'pros' => 'عمليات صناعية حقيقية',
                'cons' => 'غياب مرشد مخصص',
                'reviewer_name' => 'سارة',
                'reviewer_major' => 'الهندسة الكيميائية',
            ],
            [
                'company_id' => 3,
                'role_title' => 'مطور تطبيقات',
                'department' => 'التطوير الرقمي',
                'city' => 'الرياض',
                'duration_months' => 3,
                'sector' => 'private',
                'modality' => 'remote',
                'stipend_sar' => 1800,
                'had_supervisor' => true,
                'mixed_env' => true,
                'job_offer' => false,
                'rating_mentorship' => 4,
                'rating_learning' => 4,
                'rating_culture' => 5,
                'rating_compensation' => 3,
                'overall_rating' => 4,
                'recommendation' => 'yes',
                'review_text' => 'بيئة عمل ممتعة وفريق متعاون. تعلمت تطوير التطبيقات بتقنيات حديثة.',
                'pros' => 'فريق متعاون، عمل عن بُعد',
                'cons' => null,
                'reviewer_name' => 'محمد',
                'reviewer_major' => 'نظم المعلومات',
            ],
            [
                'company_id' => 4,
                'role_title' => 'محلل مالي',
                'department' => 'المالية',
                'city' => 'الرياض',
                'duration_months' => 2,
                'sector' => 'private',
                'modality' => 'onsite',
                'stipend_sar' => null,
                'had_supervisor' => true,
                'mixed_env' => false,
                'job_offer' => true,
                'rating_mentorship' => 5,
                'rating_learning' => 4,
                'rating_culture' => 4,
                'rating_compensation' => 1,
                'overall_rating' => 4,
                'recommendation' => 'yes',
                'review_text' => 'تدريب منظم جداً مع برنامج إرشاد قوي. اكتسبت خبرة عملية في التحليل المالي.',
                'pros' => 'برنامج إرشاد قوي، انتهى بعرض عمل',
                'cons' => 'بدون مكافأة مالية',
                'reviewer_name' => 'نورة',
                'reviewer_major' => 'المالية',
            ],
        ];

        foreach ($ratings as $data) {
            Rating::create($data);
        }
    }
}
