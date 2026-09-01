<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmDemoDataSeeder extends Seeder
{
    public const DEMO_EMAIL_DOMAIN = 'demo-crm.local';
    public const DEMO_NOTES_MARKER = '[DEMO-DATA-CRM-V2]';
    public const TARGET_LEADS_COUNT = 4500;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('==================================================');
        $this->command?->info('Starting CRM v2 Large Demo Dataset Generation');
        $this->command?->info('Target: ' . self::TARGET_LEADS_COUNT . ' fake Leads / Customers');
        $this->command?->info('==================================================');

        // Check for existing demo leads
        $existingDemoIds = Lead::query()
            ->where('email', 'like', '%@' . self::DEMO_EMAIL_DOMAIN)
            ->orWhere('notes', 'like', '%' . self::DEMO_NOTES_MARKER . '%')
            ->pluck('id')
            ->all();

        $existingDemoCount = count($existingDemoIds);

        if ($existingDemoCount > 0) {
            $this->command?->warn("Found {$existingDemoCount} existing demo leads with @" . self::DEMO_EMAIL_DOMAIN);
            $this->command?->info("Performing clean replacement of existing demo records (preserving real leads)...");

            // Cleanly remove only demo-linked records in reverse FK order
            DB::transaction(function () use ($existingDemoIds): void {
                $chunks = array_chunk($existingDemoIds, 1000);
                foreach ($chunks as $chunk) {
                    DB::table('lead_status_histories')->whereIn('lead_id', $chunk)->delete();
                    DB::table('lead_followups')->whereIn('lead_id', $chunk)->delete();
                    DB::table('campaign_lead')->whereIn('lead_id', $chunk)->delete();
                    DB::table('leads')->whereIn('id', $chunk)->delete();
                }
            });

            $this->command?->info("Cleaned previous demo records successfully.");
        }

        $totalLeadsBefore = Lead::count();
        $this->command?->info("Existing Real / Non-Demo Leads in database: {$totalLeadsBefore}");

        // Load active pipeline stages and their statuses dynamically
        $activeStages = PipelineStage::activeOrdered()->load('statuses');
        if ($activeStages->isEmpty()) {
            $this->command?->error('No active PipelineStages found in the database. Please seed pipeline stages first.');
            return;
        }

        $this->command?->info("Loaded {$activeStages->count()} active PipelineStages:");
        foreach ($activeStages as $stage) {
            $statusCodes = $stage->statuses->pluck('code')->implode(', ');
            $this->command?->info("  - Stage #{$stage->id} [{$stage->code} / {$stage->name_ar}]: statuses [{$statusCodes}]");
        }

        // Load existing active campaigns
        $campaigns = Campaign::all();
        $this->command?->info("Loaded {$campaigns->count()} existing Campaigns.");

        // Load existing active users
        $users = User::where('is_active', true)->get();
        if ($users->isEmpty()) {
            $users = User::all();
        }
        $this->command?->info("Loaded {$users->count()} eligible users for assignment.");

        // Categorize users
        $userCategories = $this->categorizeUsers($users);

        // Perform dataset generation inside a transaction
        $stats = DB::transaction(function () use ($activeStages, $campaigns, $userCategories): array {
            return $this->generateDataset($activeStages, $campaigns, $userCategories);
        });

        $totalLeadsAfter = Lead::count();

        $this->command?->info('==================================================');
        $this->command?->info('DEMO DATASET GENERATION COMPLETED SUCCESSFULLY');
        $this->command?->info("Total Leads Before: {$totalLeadsBefore}");
        $this->command?->info("Demo Leads Generated: {$stats['leads_count']}");
        $this->command?->info("Total Leads After: {$totalLeadsAfter}");
        $this->command?->info("Status Histories Generated: {$stats['histories_count']}");
        $this->command?->info("Follow-ups Generated: {$stats['followups_count']}");
        $this->command?->info("Campaign-Lead Links Generated: {$stats['campaign_links_count']}");
        $this->command?->info('==================================================');
    }

    /**
     * Categorize users for realistic sales distribution.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, User> $users
     * @return array{
     *     agents: array<int, User>,
     *     leaders: array<int, User>,
     *     managers: array<int, User>,
     *     admins: array<int, User>,
     *     all: array<int, User>
     * }
     */
    private function categorizeUsers($users): array
    {
        $agents = [];
        $leaders = [];
        $managers = [];
        $admins = [];
        $all = [];

        foreach ($users as $user) {
            $all[] = $user;
            $username = strtolower((string) $user->username);
            $email = strtolower((string) $user->email);

            if (str_contains($username, 'agent') || str_contains($email, 'agent')) {
                $agents[] = $user;
            } elseif (str_contains($username, 'leader') || str_contains($email, 'teamleader')) {
                $leaders[] = $user;
            } elseif (str_contains($username, 'manager') || str_contains($email, 'manager')) {
                $managers[] = $user;
            } else {
                $admins[] = $user;
            }
        }

        // Fallbacks if roles cannot be inferred from usernames/emails
        if (empty($agents)) {
            $agents = $all;
        }
        if (empty($leaders)) {
            $leaders = $agents;
        }
        if (empty($managers)) {
            $managers = $leaders;
        }
        if (empty($admins)) {
            $admins = $all;
        }

        return [
            'agents' => array_values($agents),
            'leaders' => array_values($leaders),
            'managers' => array_values($managers),
            'admins' => array_values($admins),
            'all' => array_values($all),
        ];
    }

    /**
     * Generate the complete demo dataset.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, PipelineStage> $activeStages
     * @param \Illuminate\Database\Eloquent\Collection<int, Campaign> $campaigns
     * @param array{
     *     agents: array<int, User>,
     *     leaders: array<int, User>,
     *     managers: array<int, User>,
     *     admins: array<int, User>,
     *     all: array<int, User>
     * } $userCategories
     * @return array<string, int>
     */
    private function generateDataset($activeStages, $campaigns, array $userCategories): array
    {
        $targetCount = self::TARGET_LEADS_COUNT;
        $now = Carbon::now();

        // 1. Prepare dynamic pipeline stages and statuses weights
        $stageStatusPlan = $this->buildStageStatusPlan($activeStages, $targetCount);

        // 2. Prepare time distribution across the last 12 months
        // Month -11 to Month 0 (current month)
        $monthlyDistribution = [
            -11 => 220,
            -10 => 260,
            -9 => 300,
            -8 => 330,
            -7 => 350,
            -6 => 370,
            -5 => 390,
            -4 => 420,
            -3 => 440,
            -2 => 470,
            -1 => 480,
            0 => 470,
        ];

        // Flatten dates for all 4,500 leads
        $leadDates = $this->generateDateTimeline($monthlyDistribution, $now);

        // 3. Realistic vocabulary arrays
        $firstNames = [
            'أحمد', 'محمد', 'محمود', 'مصطفى', 'إبراهيم', 'علي', 'السيد', 'عمر', 'طارق', 'كريم',
            'سامح', 'حازم', 'خالد', 'شريف', 'وائل', 'رامي', 'يوسف', 'حسن', 'حسين', 'عمرو',
            'سارة', 'مريم', 'منى', 'رانيا', 'ياسمين', 'نورهان', 'أمل', 'ريم', 'داليا', 'هبة',
            'فاطمة', 'شيرين', 'آية', 'نهى', 'دينا', 'مي', 'أسماء', 'هناء', 'رباب', 'غادة',
            'أشرف', 'أيمن', 'تامر', 'وليد', 'إيهاب', 'عادل', 'ماهر', 'مجدي', 'سامي', 'مدحت',
            'علاء', 'حسام', 'هيثم', 'ناصر', 'زياد', 'بلال', 'حمزة', 'باسم', 'ممدوح', 'عصام',
            'يسرا', 'أميرة', 'سلمى', 'هاجر', 'خلود', 'يمنى', 'رضوى', 'شيماء', 'إيمان', 'جهاد',
        ];

        $lastNames = [
            'الشرافي', 'المنسي', 'عبد الرحمن', 'حسن', 'محمود', 'عبد العزيز', 'مصطفى', 'سليمان', 'بدوي', 'القاضي',
            'فؤاد', 'سالم', 'الصاوي', 'الخضري', 'زكي', 'توفيق', 'عبد الله', 'زهران', 'صبري', 'شرف',
            'العوضي', 'الشاعر', 'النارسي', 'فاروق', 'عثمان', 'رضوان', 'فهمي', 'الجزار', 'منصور', 'الباز',
            'العدوي', 'غانم', 'حماد', 'بركات', 'خليل', 'أبو النصر', 'سعيد', 'راشد', 'مبارك', 'الشافعي',
            'خطاب', 'عيسى', 'النجار', 'الديب', 'حجازي', 'غريب', 'متولي', 'الجندي', 'وهبة', 'قنديل',
            'دسوقي', 'جمعة', 'عاشور', 'مهران', 'الفيومي', 'الطنطاوي', 'الهواري', 'عطية', 'سعد', 'ياسين',
        ];

        $companyPrefixes = [
            'شركة', 'مجموعة', 'مؤسسة', 'مركز', 'الشركة المصرية لـ', 'شركة النيل لـ', 'القمة لـ', 'أفق لـ',
            'رواد لـ', 'الرواد لـ', 'المستقبل لـ', 'الإتحاد لـ', 'الدولية لـ', 'العالمية لـ', 'الصفوة لـ',
        ];

        $companyNouns = [
            'الحلول البرمجية', 'التوريدات العامة', 'الاستثمار العقاري', 'الصناعات الغذائية', 'الخدمات اللوجستية',
            'التطوير التكنولوجي', 'المقاولات العامة', 'الحلول المتكاملة', 'الإعلام والتسويق', 'الاستشارات الإدارية',
            'الاتصالات وتقنية المعلومات', 'الهندسة الحديثة', 'الاستيراد والتصدير', 'التوزيع والخدمات', 'الشحن الدولي',
            'إدارة المنشآت', 'التصنيع الدقيق', 'التسويق الرقمي', 'حلول الطاقة', 'الرعاية الصحية',
            'الأدوية والمستلزمات', 'التصميم والديكور', 'إدارة المشروعات', 'الأمن والحراسة', 'الطباعة والنشر',
        ];

        $governorates = [
            'القاهرة', 'الجيزة', 'الإسكندرية', 'الشرقية', 'الدقهلية', 'القليوبية', 'المنوفية', 'الغربية',
            'بورسعيد', 'السويس', 'الإسماعيلية', 'أسيوط', 'سوهاج', 'المنيا', 'قنا', 'البحر الأحمر',
            'كفر الشيخ', 'دمياط', 'البحيرة', 'بني سويف', 'الفيوم', 'الأقصر', 'أسوان',
        ];

        $activities = [
            'تكنولوجيا المعلومات', 'المقاولات والإنشاءات', 'التجارة والتوزيع', 'الاستثمار العقاري', 'الصناعات الغذائية',
            'الخدمات اللوجستية', 'الاستشارات الهندسية', 'التسويق الرقمي', 'الاستيراد والتصدير', 'الرعاية الصحية والطبية',
            'التعليم والتدريب', 'السياحة والفنادق', 'تصنيع الأثاث', 'الطاقة والكهرباء', 'الملابس والمنسوجات',
        ];

        $sources = [
            'موقع إلكتروني', 'توصية عميل', 'حملة فيسبوك', 'إعلان جوجل', 'معرض تجاري',
            'تواصل مباشر', 'لينكد إن', 'انستغرام', 'تيك توك', 'واتساب بزنس',
        ];

        $solutionTypes = [
            'CRM سحابي',
            'إدارة مبيعات',
            'نظام ERP',
            'كول سنتر',
            'فواتير إلكترونية',
            'إدارة مخازن',
            'شات بوت ذكي',
            'إدارة عملاء',
        ];

        $jobTitles = [
            'مدير عام', 'مدير المشتريات', 'مدير تكنولوجيا المعلومات', 'مدير المبيعات', 'الرئيس التنفيذي',
            'صاحب الشركة', 'مدير العمليات', 'مدير التخطيط', 'مدير الحسابات', 'نائب رئيس مجلس الإدارة',
        ];

        $disinterestReasons = [
            'ارتفاع السعر مقارنة بالميزانية المخصصة',
            'عدم الحاجة للنظام في الوقت الحالي',
            'التعاقد مع شركة منافسة مؤخراً',
            'تأجيل المشروع للربع السنوي القادم',
            'تغيير نشاط الشركة الداخلي',
            'عدم توافق بعض الخصائص الفنية المطلوبة',
            'تفضيل استمرار العمل بالنظام القديم حالياً',
        ];

        $creatorUser = !empty($userCategories['admins']) ? $userCategories['admins'][0] : $userCategories['all'][0];

        // 4. Build lead records
        $leadsToInsert = [];
        $leadMeta = []; // To generate history, followups, campaign relations

        for ($i = 0; $i < $targetCount; $i++) {
            $seqNumber = $i + 1;
            $paddedSeq = sprintf('%04d', $seqNumber);

            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[($i * 7 + 3) % count($lastNames)];
            $fullName = "{$firstName} {$lastName}";

            $companyPrefix = $companyPrefixes[($i * 3) % count($companyPrefixes)];
            $companyNoun = $companyNouns[($i * 11 + 2) % count($companyNouns)];
            $companyName = "{$companyPrefix} {$companyNoun}";

            $email = "lead.demo.{$paddedSeq}@" . self::DEMO_EMAIL_DOMAIN;

            // Dedicated phone demo pattern: 0109999xxxx, 0119999xxxx, etc.
            $phonePrefixes = ['010', '011', '012', '015'];
            $phonePrefix = $phonePrefixes[$i % count($phonePrefixes)];
            $phoneSuffix = sprintf('%05d', 10000 + ($i * 17) % 89999);
            $phone = "{$phonePrefix}999{$phoneSuffix}";

            $governorate = $governorates[($i * 5 + 1) % count($governorates)];
            $activity = $activities[($i * 3 + 2) % count($activities)];
            $source = $sources[($i * 2) % count($sources)];
            $jobTitle = $jobTitles[($i * 4) % count($jobTitles)];
            $solutionType = $solutionTypes[($i * 3) % count($solutionTypes)];

            // Status and stage assignment from planned distribution
            $statusItem = $stageStatusPlan[$i];
            $statusId = $statusItem['status_id'];
            $statusCode = $statusItem['status_code'];
            $stagePosition = $statusItem['stage_position'];
            $isTerminal = $statusItem['is_terminal'];

            // Assignee distribution:
            // ~65% Sales Agents, ~20% Team Leaders, ~10% Managers, ~5% Admins
            $assigneeRoll = $i % 100;
            if ($assigneeRoll < 65) {
                $assignedUser = $userCategories['agents'][$i % count($userCategories['agents'])];
            } elseif ($assigneeRoll < 85) {
                $assignedUser = $userCategories['leaders'][$i % count($userCategories['leaders'])];
            } elseif ($assigneeRoll < 95) {
                $assignedUser = $userCategories['managers'][$i % count($userCategories['managers'])];
            } else {
                $assignedUser = $userCategories['admins'][$i % count($userCategories['admins'])];
            }

            // Timestamps
            $createdAt = $leadDates[$i];
            $updatedAt = (clone $createdAt)->addHours($i % 36)->addMinutes(($i * 13) % 60);
            if ($updatedAt->isFuture() || $updatedAt->greaterThan($now)) {
                $updatedAt = $now->copy()->subMinutes(($i % 30) + 1);
                if ($updatedAt->lessThan($createdAt)) {
                    $updatedAt = $createdAt->copy();
                }
            }

            // Disinterest reason
            $disinterestReason = null;
            if ($statusCode === 'not_interested') {
                $disinterestReason = $disinterestReasons[$i % count($disinterestReasons)];
            }

            // Quotation sent flag
            $quotationSent = in_array($statusCode, ['quotation', 'discussion', 'contract_closed', 'execution'], true)
                || ($stagePosition >= 3 && ($i % 3 !== 0));

            // Next follow-up timestamp for the lead:
            // - ~100 leads today
            // - ~250 leads overdue (past date)
            // - ~400 leads upcoming (future date 1-14 days)
            // - remainder null or based on status
            $nextFollowUpAt = null;
            if ($statusCode !== 'not_interested' && $statusCode !== 'execution') {
                if ($i < 100) {
                    // Today follow-ups
                    $nextFollowUpAt = $now->copy()->startOfDay()->addHours(9 + ($i % 9))->addMinutes(($i * 7) % 60);
                } elseif ($i < 350) {
                    // Overdue follow-ups (past date within last 2-30 days)
                    $daysBack = ($i % 25) + 2;
                    $nextFollowUpAt = $now->copy()->subDays($daysBack)->setHour(10 + ($i % 6))->setMinute(($i * 11) % 60);
                } elseif ($i < 750) {
                    // Upcoming follow-ups (future date 1-14 days)
                    $daysForward = ($i % 14) + 1;
                    $nextFollowUpAt = $now->copy()->addDays($daysForward)->setHour(10 + ($i % 6))->setMinute(($i * 11) % 60);
                } elseif ($createdAt->diffInDays($now) < 60 && ($i % 4 === 0)) {
                    $nextFollowUpAt = (clone $createdAt)->addDays(($i % 10) + 2)->setHour(11);
                    if ($nextFollowUpAt->isFuture() && $nextFollowUpAt->diffInDays($now) > 30) {
                        $nextFollowUpAt = $now->copy()->addDays(($i % 7) + 1);
                    }
                }
            }

            $notes = self::DEMO_NOTES_MARKER . " عميل تجريبي ({$fullName}) - قطاع {$activity} - المحافظة: {$governorate}";

            $leadsToInsert[] = [
                'lead_status_id' => $statusId,
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company_name' => $companyName,
                'activity' => $activity,
                'governorate' => $governorate,
                'address' => "شارع رقم " . (($i % 80) + 1) . "، المنطقة الصناعية / التجارية، {$governorate}",
                'users_count' => ($i % 25) + 3,
                'branches_count' => ($i % 6) + 1,
                'job_title' => $jobTitle,
                'disinterest_reason' => $disinterestReason,
                'solution_type' => $solutionType,
                'lines_count' => ($i % 12) + 1,
                'extensions' => 'داخلي ' . (($i % 40) + 101),
                'departments' => 'الإدارة العامة والمبيعات والحسابات',
                'quotation_file_path' => null,
                'phone' => $phone,
                'email' => $email,
                'source' => $source,
                'quotation_sent' => $quotationSent,
                'assigned_employee' => $assignedUser->name,
                'assigned_user_id' => $assignedUser->id,
                'created_by' => $creatorUser->name,
                'created_by_user_id' => $creatorUser->id,
                'notes' => $notes,
                'next_follow_up_at' => $nextFollowUpAt?->format('Y-m-d H:i:s'),
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            ];

            $leadMeta[$i] = [
                'email' => $email,
                'status_id' => $statusId,
                'status_code' => $statusCode,
                'stage_position' => $stagePosition,
                'assigned_user' => $assignedUser,
                'creator_user' => $creatorUser,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'next_follow_up_at' => $nextFollowUpAt,
            ];
        }

        // 5. Bulk insert leads in chunks
        $chunks = array_chunk($leadsToInsert, 500);
        foreach ($chunks as $chunk) {
            DB::table('leads')->insert($chunk);
        }

        // 6. Map inserted IDs back to meta
        $insertedLeads = DB::table('leads')
            ->where('email', 'like', '%@' . self::DEMO_EMAIL_DOMAIN)
            ->select(['id', 'email', 'created_at'])
            ->get()
            ->keyBy('email');

        // 7. Generate Campaign-Lead relationships (~70% campaign-associated)
        $campaignLeadRows = [];
        $campaignList = $campaigns->values()->all();
        $campaignCount = count($campaignList);

        if ($campaignCount > 0) {
            for ($i = 0; $i < $targetCount; $i++) {
                // ~70% assigned to a campaign, ~30% organic
                if ($i % 10 < 7) {
                    $email = $leadMeta[$i]['email'];
                    $leadRecord = $insertedLeads->get($email);
                    if ($leadRecord) {
                        $campaign = $campaignList[$i % $campaignCount];
                        $createdAtStr = $leadMeta[$i]['created_at']->format('Y-m-d H:i:s');
                        $campaignLeadRows[] = [
                            'campaign_id' => $campaign->id,
                            'lead_id' => $leadRecord->id,
                            'created_at' => $createdAtStr,
                            'updated_at' => $createdAtStr,
                        ];
                    }
                }
            }

            if (!empty($campaignLeadRows)) {
                $campaignChunks = array_chunk($campaignLeadRows, 500);
                foreach ($campaignChunks as $chunk) {
                    DB::table('campaign_lead')->insert($chunk);
                }
            }
        }

        // 8. Generate Status Histories (~70% of leads have status history, ~30% multiple transitions)
        $statusHistories = $this->buildStatusHistories($activeStages, $leadMeta, $insertedLeads, $now);
        if (!empty($statusHistories)) {
            $historyChunks = array_chunk($statusHistories, 500);
            foreach ($historyChunks as $chunk) {
                DB::table('lead_status_histories')->insert($chunk);
            }
        }

        // 9. Generate Lead Followups (~45% of leads have follow-ups)
        $followups = $this->buildLeadFollowups($activeStages, $leadMeta, $insertedLeads, $now);
        if (!empty($followups)) {
            $followupChunks = array_chunk($followups, 500);
            foreach ($followupChunks as $chunk) {
                DB::table('lead_followups')->insert($chunk);
            }
        }

        return [
            'leads_count' => count($leadsToInsert),
            'histories_count' => count($statusHistories),
            'followups_count' => count($followups),
            'campaign_links_count' => count($campaignLeadRows),
        ];
    }

    /**
     * Build dynamic stage/status distribution plan.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, PipelineStage> $activeStages
     * @param int $totalCount
     * @return array<int, array{status_id: int, status_code: string, stage_id: int, stage_position: int, is_terminal: bool}>
     */
    private function buildStageStatusPlan($activeStages, int $totalCount): array
    {
        $orderedStages = $activeStages->sortBy('position')->values();
        $stageCount = $orderedStages->count();

        // Target proportions:
        // Early stages (Start, Interest): 45% (e.g. 25% Start, 20% Interest)
        // Middle stages (Negotiation, etc.): 37% (e.g. 30% Negotiation, 7% other mid/postponed)
        // Closing / Final stage: 18% (e.g. 18% contract_closed & execution)
        //
        // Detect Closing Stage by presence of contract_closed / execution or highest terminal position
        $closingStageIndex = null;
        foreach ($orderedStages as $idx => $stg) {
            if ($stg->statuses->contains('code', 'contract_closed') || $stg->statuses->contains('code', 'execution') || $stg->code === 'closing_execution') {
                $closingStageIndex = $idx;
                break;
            }
        }

        if ($closingStageIndex === null) {
            $closingStageIndex = $stageCount - 1;
        }

        $weights = [];
        if ($stageCount === 1) {
            $weights[0] = 1.0;
        } elseif ($stageCount === 2) {
            $weights[0] = 0.70;
            $weights[1] = 0.30;
        } else {
            // Assign specific weights
            $weights[0] = 0.25; // Stage 1 (Start)
            $weights[1] = 0.20; // Stage 2 (Interest)

            // Closing stage gets 18%
            $weights[$closingStageIndex] = 0.18;

            // Remaining stages share the remaining 37%
            $remainingIndices = [];
            for ($k = 2; $k < $stageCount; $k++) {
                if ($k !== $closingStageIndex) {
                    $remainingIndices[] = $k;
                }
            }

            if (!empty($remainingIndices)) {
                if (count($remainingIndices) === 1) {
                    $weights[$remainingIndices[0]] = 0.37;
                } else {
                    $weights[$remainingIndices[0]] = 0.30; // Primary negotiation stage
                    $extraShare = 0.07 / (count($remainingIndices) - 1);
                    for ($m = 1; $m < count($remainingIndices); $m++) {
                        $weights[$remainingIndices[$m]] = $extraShare;
                    }
                }
            }
        }

        // Build flat array of status candidates for each stage
        $assignedItems = [];

        foreach ($orderedStages as $idx => $stage) {
            $stageTarget = (int) round(($weights[$idx] ?? (1.0 / $stageCount)) * $totalCount);
            $statuses = $stage->statuses->values();
            $statusCount = $statuses->count();

            if ($statusCount === 0) {
                // Orphan stage fallback
                $fallbackStatus = $stage->ensureDefaultStatus();
                $statuses = collect([$fallbackStatus]);
                $statusCount = 1;
            }

            for ($k = 0; $k < $stageTarget; $k++) {
                $status = $statuses[$k % $statusCount];
                $assignedItems[] = [
                    'status_id' => $status->id,
                    'status_code' => (string) $status->code,
                    'stage_id' => $stage->id,
                    'stage_position' => (int) $stage->position,
                    'is_terminal' => (bool) $status->is_terminal,
                ];
            }
        }

        // Adjust length to exact totalCount
        while (count($assignedItems) < $totalCount) {
            $stage = $orderedStages[0];
            $status = $stage->statuses->first() ?? $stage->ensureDefaultStatus();
            $assignedItems[] = [
                'status_id' => $status->id,
                'status_code' => (string) $status->code,
                'stage_id' => $stage->id,
                'stage_position' => (int) $stage->position,
                'is_terminal' => (bool) $status->is_terminal,
            ];
        }

        if (count($assignedItems) > $totalCount) {
            $assignedItems = array_slice($assignedItems, 0, $totalCount);
        }

        // Shuffle pseudo-randomly to disperse across time while maintaining proportions
        mt_srand(42); // Deterministic seed for reproducible distribution
        shuffle($assignedItems);

        return $assignedItems;
    }

    /**
     * Generate timeline array across last 12 months with randomized dates.
     *
     * @param array<int, int> $monthlyDistribution
     * @param Carbon $now
     * @return array<int, Carbon>
     */
    private function generateDateTimeline(array $monthlyDistribution, Carbon $now): array
    {
        $dates = [];

        foreach ($monthlyDistribution as $monthOffset => $monthLeadCount) {
            // Use startOfMonth and subMonthsNoOverflow to prevent leap/short month skips
            $baseMonth = $now->copy()->startOfMonth()->addMonthsNoOverflow($monthOffset);
            $daysInMonth = $monthOffset === 0
                ? max(1, (int) $now->format('j'))
                : (int) $baseMonth->daysInMonth;

            for ($i = 0; $i < $monthLeadCount; $i++) {
                // Distribute across days of the month
                $day = ($i % $daysInMonth) + 1;

                // Business hours: 08:30 to 19:30
                $hour = 8 + (($i * 3) % 11);
                $minute = ($i * 7) % 60;
                $second = ($i * 13) % 60;

                $date = $baseMonth->copy()->setDay($day)->setTime($hour, $minute, $second);

                // Ensure it does not exceed current timestamp
                if ($date->isFuture() || $date->greaterThan($now)) {
                    $date = $now->copy()->subMinutes(($i % 120) + 5);
                }

                $dates[] = $date;
            }
        }

        // Sort chronologically
        usort($dates, static fn (Carbon $a, Carbon $b) => $a->getTimestamp() <=> $b->getTimestamp());

        return $dates;
    }

    /**
     * Build status transitions history for leads.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, PipelineStage> $activeStages
     * @param array<int, array<string, mixed>> $leadMeta
     * @param \Illuminate\Support\Collection<string, object> $insertedLeads
     * @param Carbon $now
     * @return array<int, array<string, mixed>>
     */
    private function buildStatusHistories($activeStages, array $leadMeta, $insertedLeads, Carbon $now): array
    {
        // Find key status IDs by code or position
        $allStatuses = $activeStages->flatMap->statuses->keyBy('code');
        $newStatus = $allStatuses->get('new') ?? $activeStages->first()?->statuses->first();
        $interestedStatus = $allStatuses->get('interested') ?? $allStatuses->get('interest');
        $meetingStatus = $allStatuses->get('meeting');
        $quotationStatus = $allStatuses->get('quotation');
        $contractStatus = $allStatuses->get('contract_closed');
        $executionStatus = $allStatuses->get('execution');

        $histories = [];
        $leadCount = count($leadMeta);

        $notesProgression = [
            'initial' => 'تسجيل العميل في النظام وبدء مرحلة التواصل الأولية',
            'no_answer' => 'محاولة اتصال هاتفية - لم يتم الرد، تم إرسال رسالة واتساب للتعريف',
            'interested' => 'تم التواصل مع العميل وإبداء اهتمامه بالحلول البرمجية وطلب مزيد من التفاصيل',
            'meeting' => 'تحديد موعد جلسة عمل وعرض توضيحي للنظام (Demo Meeting)',
            'quotation' => 'إعداد وإرسال عرض السعر الفني والمالي بناءً على متطلبات العميل',
            'discussion' => 'مناقشة بنود عرض السعر والمواصفات الفنية وجدولة مراحل التطبيق',
            'contract' => 'موافقة العميل على العرض وتوقيع العقد الرسمي',
            'execution' => 'بدء مرحلة التنفيذ والتركيب وتدريب الموظفين',
            'postponed' => 'العميل طلب تأجيل المتابعة لظروف الميزانية الحالية',
            'not_interested' => 'العميل أفاد بعدم الرغبة في التعاقد في الوقت الحالي',
        ];

        for ($i = 0; $i < $leadCount; $i++) {
            // ~70% of leads have status history
            if ($i % 10 >= 7) {
                continue;
            }

            $meta = $leadMeta[$i];
            $email = $meta['email'];
            $leadRecord = $insertedLeads->get($email);
            if (!$leadRecord) {
                continue;
            }

            $leadId = $leadRecord->id;
            $currentStatusId = $meta['status_id'];
            $currentStatusCode = $meta['status_code'];
            $createdAt = $meta['created_at'];
            $assignedUser = $meta['assigned_user'];
            $creatorUser = $meta['creator_user'];

            $transitions = [];

            // Define realistic progression chain based on current status
            if ($currentStatusCode === 'no_answer') {
                if ($newStatus && $newStatus->id !== $currentStatusId) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $currentStatusId,
                        'days' => 1,
                        'note' => $notesProgression['no_answer'],
                    ];
                }
            } elseif ($currentStatusCode === 'interested') {
                if ($newStatus && $newStatus->id !== $currentStatusId) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $currentStatusId,
                        'days' => 2,
                        'note' => $notesProgression['interested'],
                    ];
                }
            } elseif ($currentStatusCode === 'meeting') {
                if ($newStatus && $interestedStatus) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $interestedStatus->id,
                        'days' => 2,
                        'note' => $notesProgression['interested'],
                    ];
                    $transitions[] = [
                        'from' => $interestedStatus->id,
                        'to' => $currentStatusId,
                        'days' => 5,
                        'note' => $notesProgression['meeting'],
                    ];
                }
            } elseif ($currentStatusCode === 'quotation' || $currentStatusCode === 'discussion') {
                if ($newStatus && $interestedStatus) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $interestedStatus->id,
                        'days' => 2,
                        'note' => $notesProgression['interested'],
                    ];
                    if ($meetingStatus) {
                        $transitions[] = [
                            'from' => $interestedStatus->id,
                            'to' => $meetingStatus->id,
                            'days' => 5,
                            'note' => $notesProgression['meeting'],
                        ];
                        $transitions[] = [
                            'from' => $meetingStatus->id,
                            'to' => $currentStatusId,
                            'days' => 9,
                            'note' => $notesProgression['quotation'],
                        ];
                    }
                }
            } elseif ($currentStatusCode === 'contract_closed') {
                if ($newStatus && $interestedStatus && $quotationStatus) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $interestedStatus->id,
                        'days' => 2,
                        'note' => $notesProgression['interested'],
                    ];
                    $transitions[] = [
                        'from' => $interestedStatus->id,
                        'to' => $quotationStatus->id,
                        'days' => 7,
                        'note' => $notesProgression['quotation'],
                    ];
                    $transitions[] = [
                        'from' => $quotationStatus->id,
                        'to' => $currentStatusId,
                        'days' => 15,
                        'note' => $notesProgression['contract'],
                    ];
                }
            } elseif ($currentStatusCode === 'execution') {
                if ($newStatus && $quotationStatus && $contractStatus) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $quotationStatus->id,
                        'days' => 4,
                        'note' => $notesProgression['quotation'],
                    ];
                    $transitions[] = [
                        'from' => $quotationStatus->id,
                        'to' => $contractStatus->id,
                        'days' => 12,
                        'note' => $notesProgression['contract'],
                    ];
                    $transitions[] = [
                        'from' => $contractStatus->id,
                        'to' => $currentStatusId,
                        'days' => 20,
                        'note' => $notesProgression['execution'],
                    ];
                }
            } elseif ($currentStatusCode === 'not_interested') {
                if ($newStatus && $newStatus->id !== $currentStatusId) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $currentStatusId,
                        'days' => 3,
                        'note' => $notesProgression['not_interested'],
                    ];
                }
            } else {
                // Generic transition for any other status
                if ($newStatus && $newStatus->id !== $currentStatusId) {
                    $transitions[] = [
                        'from' => $newStatus->id,
                        'to' => $currentStatusId,
                        'days' => 3,
                        'note' => 'تحديث حالة العميل ومتابعة المتطلبات',
                    ];
                }
            }

            foreach ($transitions as $t) {
                $changedAt = $createdAt->copy()->addDays($t['days'])->addHours($i % 8);
                if ($changedAt->isFuture() || $changedAt->greaterThan($now)) {
                    $diffMinutes = max(1, (int) $createdAt->diffInMinutes($now));
                    $changedAt = $createdAt->copy()->addMinutes(min($diffMinutes, (($i * 3) % 180) + 10));
                }

                // Invariant: changed_at must be >= created_at and <= now
                if ($changedAt->lessThan($createdAt)) {
                    $changedAt = $createdAt->copy();
                }
                if ($changedAt->greaterThan($now)) {
                    $changedAt = $now->copy();
                }

                $changedAtStr = $changedAt->format('Y-m-d H:i:s');

                $histories[] = [
                    'lead_id' => $leadId,
                    'from_status_id' => $t['from'],
                    'to_status_id' => $t['to'],
                    'changed_by' => $assignedUser->name,
                    'changed_by_user_id' => $assignedUser->id,
                    'note' => $t['note'],
                    'changed_at' => $changedAtStr,
                    'created_at' => $changedAtStr,
                    'updated_at' => $changedAtStr,
                ];
            }
        }

        return $histories;
    }

    /**
     * Build followups for leads.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, PipelineStage> $activeStages
     * @param array<int, array<string, mixed>> $leadMeta
     * @param \Illuminate\Support\Collection<string, object> $insertedLeads
     * @param Carbon $now
     * @return array<int, array<string, mixed>>
     */
    private function buildLeadFollowups($activeStages, array $leadMeta, $insertedLeads, Carbon $now): array
    {
        $allStatuses = $activeStages->flatMap->statuses->keyBy('code');
        $newStatus = $allStatuses->get('new') ?? $activeStages->first()?->statuses->first();
        $interestedStatus = $allStatuses->get('interested') ?? $allStatuses->get('interest');
        $meetingStatus = $allStatuses->get('meeting');

        $communicationTypes = ['phone', 'whatsapp', 'meeting', 'email'];
        $outcomes = [
            'phone' => [
                'تم الاتصال الهاتفي وشرح باقات النظام والمميزات المتوافقة مع نشاط العميل',
                'تم الاتصال ولم يرد العميل، سيتم إعادة المحاولة في موعد لاحق',
                'مكالمة هاتفية ناجحة والعميل طلب إرسال عرض سعر مفصل عبر الإيميل',
                'متابعة هاتفية والعميل أفاد بمناقشة العرض مع الإدارة المالية',
            ],
            'whatsapp' => [
                'تم إرسال بروفايل الشركة والروابط التوضيحية عبر الواتساب واطلع عليها العميل',
                'محادثة واتساب سريعة والعميل حدد موعداً للمكالمة القادمة',
                'تمت مشاركة عرض السعر بصيغة PDF وتأكيد استلامه من قبل العميل',
            ],
            'meeting' => [
                'عقد اجتماع تجريبي ناجح تم خلاله عرض الشاشات الرئيسية وإجابة استفسارات الفريق',
                'جلسة مناقشة فنية مع مسؤول تكنولوجيا المعلومات حول متطلبات الربط',
            ],
            'email' => [
                'تم إرسال العرض المالي والفني المفصل عبر البريد الإلكتروني الرسمي',
                'مراسلة العميل بالرد على الاستفسارات الخاصة بشروط الدعم الفني والضمان',
            ],
        ];

        $followups = [];
        $leadCount = count($leadMeta);

        for ($i = 0; $i < $leadCount; $i++) {
            // ~45% of leads have follow-up records
            if ($i % 100 >= 45) {
                continue;
            }

            $meta = $leadMeta[$i];
            $email = $meta['email'];
            $leadRecord = $insertedLeads->get($email);
            if (!$leadRecord) {
                continue;
            }

            $leadId = $leadRecord->id;
            $currentStatusId = $meta['status_id'];
            $createdAt = $meta['created_at'];
            $assignedUser = $meta['assigned_user'];
            $nextFollowUpAt = $meta['next_follow_up_at'];

            $commType = $communicationTypes[$i % count($communicationTypes)];
            $outcomeList = $outcomes[$commType];
            $outcomeText = $outcomeList[($i * 3) % count($outcomeList)];

            // Followup execution time: between created_at and now
            $daysOffset = ($i % 10) + 1;
            $followedUpAt = $createdAt->copy()->addDays($daysOffset)->addHours(10 + ($i % 6));
            if ($followedUpAt->isFuture() || $followedUpAt->greaterThan($now)) {
                $followedUpAt = $now->copy()->subHours(($i % 48) + 1);
            }
            if ($followedUpAt->lessThan($createdAt)) {
                $followedUpAt = $createdAt->copy();
            }

            $fromStatusId = $newStatus ? $newStatus->id : $currentStatusId;
            $toStatusId = $currentStatusId;

            $followedUpAtStr = $followedUpAt->format('Y-m-d H:i:s');
            $nextFollowUpAtStr = $nextFollowUpAt?->format('Y-m-d H:i:s');

            $followups[] = [
                'lead_id' => $leadId,
                'from_status_id' => $fromStatusId,
                'to_status_id' => $toStatusId,
                'employee_name' => $assignedUser->name,
                'user_id' => $assignedUser->id,
                'communication_type' => $commType,
                'outcome' => $outcomeText,
                'field_changes' => json_encode([
                    'status_transition' => [
                        'from' => $fromStatusId,
                        'to' => $toStatusId,
                    ],
                    'note' => 'تم تسجيل المتابعة بنجاح',
                ], JSON_UNESCAPED_UNICODE),
                'next_follow_up_at' => $nextFollowUpAtStr,
                'followed_up_at' => $followedUpAtStr,
                'created_at' => $followedUpAtStr,
                'updated_at' => $followedUpAtStr,
            ];
        }

        return $followups;
    }
}
