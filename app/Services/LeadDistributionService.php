<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use App\Security\LeadAssignment;
use Illuminate\Support\Collection;

class LeadDistributionService
{
    public const STRATEGY_EQUAL = 'equal';
    public const STRATEGY_LEAST_LOADED = 'least_loaded';
    public const STRATEGY_WEIGHTED = 'weighted';
    public const STRATEGY_SINGLE = 'single';
    public const STRATEGY_RANDOM = 'random';
    public const STRATEGY_FROM_FILE = 'from_file';

    /**
     * Get all supported distribution strategies with metadata.
     *
     * @return array<string, array{name: string, description: string, icon: string, badge: string}>
     */
    public static function strategies(): array
    {
        return [
            self::STRATEGY_EQUAL => [
                'name' => 'توزيع عادل بالتساوي (Round-Robin)',
                'description' => 'توزيع العملاء بالتساوي واحداً تلو الآخر بالتناوب على الموظفين المحددين.',
                'icon' => 'bi-arrow-repeat',
                'badge' => 'الأكثر استخداماً',
            ],
            self::STRATEGY_LEAST_LOADED => [
                'name' => 'موازنة عبء العمل (الأقل عملاء حالياً)',
                'description' => 'إسناد العملاء تلقائياً للموظفين أصحاب أقل عدد من العملاء النشطين في النظام لموازنة الضغط.',
                'icon' => 'bi-bar-chart-steps',
                'badge' => 'توزيع ذكي',
            ],
            self::STRATEGY_WEIGHTED => [
                'name' => 'توزيع بنسب وأوزان مئوية',
                'description' => 'تحديد نسبة أو وزن لكل موظف، ويتم توزيع العملاء بدقة رياضية وفقاً للنسب المحددة.',
                'icon' => 'bi-pie-chart',
                'badge' => 'مخصص',
            ],
            self::STRATEGY_SINGLE => [
                'name' => 'إسناد جماعي لموظف واحد',
                'description' => 'إسناد جميع العملاء الصالحين في هذا الملف إلى موظف مسؤول محدد دفعة واحدة.',
                'icon' => 'bi-person-check',
                'badge' => 'مباشر',
            ],
            self::STRATEGY_RANDOM => [
                'name' => 'توزيع عشوائي متوازن',
                'description' => 'توزيع عشوائي للعملاء بين الموظفين المحددين.',
                'icon' => 'bi-shuffle',
                'badge' => 'عشوائي',
            ],
            self::STRATEGY_FROM_FILE => [
                'name' => 'الاعتماد على الموظف من الملف',
                'description' => 'استخدام عمود الموظف المسؤول في ملف الإكسيل، مع تحديد موظف بديل في حال وجود خلايا فارغة.',
                'icon' => 'bi-file-earmark-person',
                'badge' => 'حسب الملف',
            ],
        ];
    }

    /**
     * Get all assignable users for the current actor, optionally scoped to campaign users.
     *
     * @return Collection<int, User>
     */
    public static function getAssignableUsers(User $actor, ?Campaign $campaign = null): Collection
    {
        $users = LeadAssignment::assignableUsers($actor);

        if ($campaign !== null) {
            $campaignUserIds = $campaign->users()
                ->pluck('users.id')
                ->push($actor->id)
                ->map(static fn ($id): int => (int) $id)
                ->unique();

            $users = $users->whereIn('id', $campaignUserIds)->values();
        }

        return $users;
    }

    /**
     * Get current active/total lead counts for the given user IDs.
     *
     * @param  Collection<int, User>|list<int>  $usersOrIds
     * @return array<int, int> [user_id => lead_count]
     */
    public static function getActiveLeadCounts(Collection|array $usersOrIds): array
    {
        $ids = $usersOrIds instanceof Collection
            ? $usersOrIds->pluck('id')->all()
            : $usersOrIds;

        if ($ids === []) {
            return [];
        }

        $counts = Lead::query()
            ->whereIn('assigned_user_id', $ids)
            ->selectRaw('assigned_user_id, count(*) as count')
            ->groupBy('assigned_user_id')
            ->pluck('count', 'assigned_user_id')
            ->all();

        $result = [];
        foreach ($ids as $id) {
            $idInt = (int) $id;
            $result[$idInt] = (int) ($counts[$idInt] ?? 0);
        }

        return $result;
    }

    /**
     * Distribute valid leads and preview rows according to the specified distribution configuration.
     *
     * @param  array<int, array{row_number: int, data: array<string, mixed>}>  $validPayloadRows
     * @param  array<int, array<string, mixed>>  $previewRows
     * @param  array<string, mixed>  $config
     * @param  Collection<int, User>  $assignableUsers
     * @param  User  $actor
     * @return array{
     *     valid_rows: array<int, array{row_number: int, data: array<string, mixed>}>,
     *     preview_rows: array<int, array<string, mixed>>,
     *     summary: array<string, mixed>
     * }
     */
    public function distribute(
        array $validPayloadRows,
        array $previewRows,
        array $config,
        Collection $assignableUsers,
        User $actor
    ): array {
        if ($validPayloadRows === []) {
            return [
                'valid_rows' => [],
                'preview_rows' => $previewRows,
                'summary' => [
                    'strategy' => self::STRATEGY_EQUAL,
                    'strategy_name' => self::strategies()[self::STRATEGY_EQUAL]['name'],
                    'strategy_icon' => self::strategies()[self::STRATEGY_EQUAL]['icon'],
                    'total_assigned' => 0,
                    'user_stats' => [],
                    'config' => $config,
                ],
            ];
        }

        $strategy = (string) ($config['strategy'] ?? self::STRATEGY_EQUAL);
        if (! array_key_exists($strategy, self::strategies())) {
            $strategy = self::STRATEGY_EQUAL;
        }

        $assignableMap = $assignableUsers->keyBy(static fn (User $u): int => (int) $u->id);

        // Normalize selected user IDs
        $selectedIds = [];
        if (! empty($config['user_ids']) && is_array($config['user_ids'])) {
            foreach ($config['user_ids'] as $id) {
                $idInt = (int) $id;
                if ($assignableMap->has($idInt)) {
                    $selectedIds[] = $idInt;
                }
            }
        }

        // If no valid users were selected, default to all assignable users (or actor)
        if ($selectedIds === []) {
            $selectedIds = $assignableUsers->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }
        if ($selectedIds === []) {
            $selectedIds = [(int) $actor->id];
            if (! $assignableMap->has((int) $actor->id)) {
                $assignableMap->put((int) $actor->id, $actor);
            }
        }

        $selectedUsers = $assignableUsers->whereIn('id', $selectedIds)->values();
        if ($selectedUsers->isEmpty()) {
            $selectedUsers = collect([$actor]);
        }

        // Target map for quick token lookup in from_file strategy
        $userTokenMap = [];
        foreach ($assignableUsers as $u) {
            foreach ([$u->name, $u->username] as $nameToken) {
                $token = $this->normalizeToken((string) $nameToken);
                if ($token !== '') {
                    $userTokenMap[$token] = $u;
                }
            }
        }

        // Fallback user resolution
        $fallbackUserId = isset($config['fallback_user_id']) ? (int) $config['fallback_user_id'] : null;
        $fallbackUser = ($fallbackUserId !== null && $assignableMap->has($fallbackUserId))
            ? $assignableMap->get($fallbackUserId)
            : ($selectedUsers->first() ?? $actor);

        // Single user resolution
        $singleUserId = isset($config['single_user_id']) ? (int) $config['single_user_id'] : null;
        $singleUser = ($singleUserId !== null && $assignableMap->has($singleUserId))
            ? $assignableMap->get($singleUserId)
            : ($selectedUsers->first() ?? $actor);

        $totalValid = count($validPayloadRows);
        $assignments = []; // index in $validPayloadRows => User

        switch ($strategy) {
            case self::STRATEGY_SINGLE:
                for ($i = 0; $i < $totalValid; $i++) {
                    $assignments[$i] = $singleUser;
                }
                break;

            case self::STRATEGY_RANDOM:
                $userList = $selectedUsers->all();
                $userCount = count($userList);
                for ($i = 0; $i < $totalValid; $i++) {
                    $assignments[$i] = $userList[random_int(0, $userCount - 1)];
                }
                break;

            case self::STRATEGY_LEAST_LOADED:
                $leadCounts = self::getActiveLeadCounts($selectedUsers);
                $simulatedCounts = $leadCounts;
                $userList = $selectedUsers->all();

                for ($i = 0; $i < $totalValid; $i++) {
                    // Pick user with minimal simulated lead count
                    $bestUser = $userList[0];
                    $minCount = $simulatedCounts[(int) $bestUser->id] ?? 0;

                    foreach ($userList as $candidate) {
                        $c = $simulatedCounts[(int) $candidate->id] ?? 0;
                        if ($c < $minCount) {
                            $minCount = $c;
                            $bestUser = $candidate;
                        }
                    }

                    $assignments[$i] = $bestUser;
                    $simulatedCounts[(int) $bestUser->id] = ($simulatedCounts[(int) $bestUser->id] ?? 0) + 1;
                }
                break;

            case self::STRATEGY_WEIGHTED:
                $rawWeights = is_array($config['weights'] ?? null) ? $config['weights'] : [];
                $quotas = $this->calculateWeightedQuotas($selectedUsers, $rawWeights, $totalValid);

                // Build assignment array according to calculated quotas
                $quotaSlots = [];
                foreach ($quotas as $userId => $quota) {
                    $u = $assignableMap->get($userId) ?? $selectedUsers->first();
                    for ($k = 0; $k < $quota; $k++) {
                        $quotaSlots[] = $u;
                    }
                }

                // Smoothly interleave so users are fairly distributed throughout the rows
                $assignments = $this->interleaveAssignments($quotaSlots, $totalValid);
                break;

            case self::STRATEGY_FROM_FILE:
                foreach ($validPayloadRows as $i => $row) {
                    $assignedText = trim((string) ($row['data']['assigned_employee'] ?? ''));
                    $matchedUser = null;
                    if ($assignedText !== '') {
                        $token = $this->normalizeToken($assignedText);
                        $matchedUser = $userTokenMap[$token] ?? null;
                    }
                    $assignments[$i] = $matchedUser ?? $fallbackUser;
                }
                break;

            case self::STRATEGY_EQUAL:
            default:
                $userList = $selectedUsers->all();
                $userCount = count($userList);
                for ($i = 0; $i < $totalValid; $i++) {
                    $assignments[$i] = $userList[$i % $userCount];
                }
                break;
        }

        // Apply assignments to $validPayloadRows and map to row_number
        $rowToUserMap = [];
        $userTally = [];

        foreach ($validPayloadRows as $i => &$row) {
            $assigned = $assignments[$i] ?? $fallbackUser;
            $row['data']['assigned_user_id'] = (int) $assigned->id;
            $row['data']['assigned_employee'] = (string) $assigned->name;

            $rowNum = (int) $row['row_number'];
            $rowToUserMap[$rowNum] = $assigned;

            $uid = (int) $assigned->id;
            if (! isset($userTally[$uid])) {
                $userTally[$uid] = [
                    'user_id' => $uid,
                    'name' => (string) $assigned->name,
                    'username' => (string) $assigned->username,
                    'count' => 0,
                    'percentage' => 0.0,
                ];
            }
            $userTally[$uid]['count']++;
        }
        unset($row);

        // Update preview rows with assigned employee name and ID
        foreach ($previewRows as &$pRow) {
            $rowNum = (int) ($pRow['row_number'] ?? 0);
            if (isset($rowToUserMap[$rowNum])) {
                $assigned = $rowToUserMap[$rowNum];
                $pRow['assigned_employee'] = (string) $assigned->name;
                $pRow['assigned_user_id'] = (int) $assigned->id;
            } elseif ($pRow['state'] === 'valid') {
                $pRow['assigned_employee'] = (string) $fallbackUser->name;
                $pRow['assigned_user_id'] = (int) $fallbackUser->id;
            } else {
                $pRow['assigned_employee'] = '----';
                $pRow['assigned_user_id'] = null;
            }
        }
        unset($pRow);

        // Calculate stats percentages
        $userStats = [];
        foreach ($userTally as $uid => $tally) {
            $pct = $totalValid > 0 ? round(($tally['count'] / $totalValid) * 100, 1) : 0.0;
            $tally['percentage'] = $pct;
            $userStats[] = $tally;
        }

        // Sort stats by count descending
        usort($userStats, static fn ($a, $b) => $b['count'] <=> $a['count']);

        $strategyMeta = self::strategies()[$strategy] ?? self::strategies()[self::STRATEGY_EQUAL];

        return [
            'valid_rows' => $validPayloadRows,
            'preview_rows' => $previewRows,
            'summary' => [
                'strategy' => $strategy,
                'strategy_name' => $strategyMeta['name'],
                'strategy_description' => $strategyMeta['description'],
                'strategy_icon' => $strategyMeta['icon'],
                'total_assigned' => $totalValid,
                'user_stats' => $userStats,
                'config' => [
                    'strategy' => $strategy,
                    'user_ids' => $selectedIds,
                    'single_user_id' => $singleUserId,
                    'weights' => $config['weights'] ?? [],
                    'fallback_user_id' => $fallbackUserId,
                ],
            ],
        ];
    }

    /**
     * Calculate exact integer quotas for weighted distribution using Hare-Niemeyer (Largest Remainder).
     *
     * @param  Collection<int, User>  $users
     * @param  array<int|string, mixed>  $rawWeights
     * @param  int  $totalValid
     * @return array<int, int> [user_id => quota]
     */
    private function calculateWeightedQuotas(Collection $users, array $rawWeights, int $totalValid): array
    {
        $weights = [];
        $totalWeight = 0;

        foreach ($users as $user) {
            $uid = (int) $user->id;
            $weightVal = (float) ($rawWeights[$uid] ?? $rawWeights[(string) $uid] ?? 1);
            if ($weightVal <= 0) {
                $weightVal = 1.0;
            }
            $weights[$uid] = $weightVal;
            $totalWeight += $weightVal;
        }

        if ($totalWeight <= 0) {
            $totalWeight = count($users);
            foreach ($users as $user) {
                $weights[(int) $user->id] = 1.0;
            }
        }

        $baseQuotas = [];
        $remainders = [];
        $allocated = 0;

        foreach ($weights as $uid => $w) {
            $exact = ($w / $totalWeight) * $totalValid;
            $base = (int) floor($exact);
            $baseQuotas[$uid] = $base;
            $remainders[$uid] = $exact - $base;
            $allocated += $base;
        }

        $remainingSlots = $totalValid - $allocated;
        if ($remainingSlots > 0) {
            arsort($remainders);
            foreach (array_keys($remainders) as $uid) {
                if ($remainingSlots <= 0) {
                    break;
                }
                $baseQuotas[$uid]++;
                $remainingSlots--;
            }
        }

        return $baseQuotas;
    }

    /**
     * Interleave quota items evenly across total slots.
     *
     * @param  list<User>  $slots
     * @param  int  $total
     * @return array<int, User>
     */
    private function interleaveAssignments(array $slots, int $total): array
    {
        if (count($slots) !== $total) {
            // Fallback: cycle or slice
            return array_slice($slots, 0, $total);
        }

        // Group slots by user
        $grouped = [];
        foreach ($slots as $user) {
            $grouped[(int) $user->id][] = $user;
        }

        // Sort groups by size descending
        usort($grouped, static fn ($a, $b) => count($b) <=> count($a));

        $result = array_fill(0, $total, null);
        $currentIndex = 0;

        foreach ($grouped as $userSlots) {
            foreach ($userSlots as $user) {
                while (isset($result[$currentIndex])) {
                    $currentIndex = ($currentIndex + 1) % $total;
                }
                $result[$currentIndex] = $user;
                $currentIndex = ($currentIndex + 2) % $total;
            }
        }

        // Fill any empty gaps if needed
        for ($i = 0; $i < $total; $i++) {
            if ($result[$i] === null) {
                $result[$i] = $slots[$i] ?? $slots[0];
            }
        }

        return $result;
    }

    /**
     * Normalize token for robust user matching.
     */
    private function normalizeToken(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(
            ['أ', 'إ', 'آ', 'ة', 'ى', 'ـ', '-', '_', '.', ' '],
            ['ا', 'ا', 'ا', 'ه', 'ي', '', '', '', '', ''],
            $value
        );

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }
}
