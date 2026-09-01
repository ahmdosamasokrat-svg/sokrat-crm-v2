<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TechnicalSupportDevice;
use App\Models\TechnicalSupportIp;
use App\Models\TechnicalSupportTicket;
use App\Services\TailscaleStatusService;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TechnicalSupportController extends Controller
{
    public function index(
        Request $request,
        TailscaleStatusService $tailscale,
    ): View {
        CrmDatabaseGuard::ensureConnected();
        $user = $request->user();

        $snapshot = $tailscale->snapshot(
            $request->boolean('refresh'),
        );

        $tailscaleDevices = $snapshot['devices'] ?? [];
        $tailscaleIpsList = [];
        $onlineTailscaleIpsMap = [];

        foreach ($tailscaleDevices as $device) {
            $isOnline = (bool) ($device['online'] ?? false);
            $deviceName = (string) ($device['name'] ?? 'Unknown');
            foreach ($device['ips'] ?? [] as $ip) {
                $ipStr = (string) $ip;
                if ($isOnline) {
                    $onlineTailscaleIpsMap[$ipStr] = true;
                }
                if (filter_var($ipStr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $tailscaleIpsList[] = [
                        'ip' => $ipStr,
                        'deviceName' => $deviceName,
                        'online' => $isOnline,
                    ];
                }
            }
        }

        // Main Support Cards grid strictly displays Support Cards from DB
        $customDevices = TechnicalSupportDevice::query()
            ->accessibleTo($user)
            ->where('is_hidden', false)
            ->with(['ips', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $supportCards = [];

        foreach ($customDevices as $customDev) {
            $deviceKey = $customDev->device_key;
            $allIps = $customDev->ips->map(static fn (TechnicalSupportIp $ip): array => [
                'id' => $ip->id,
                'ip' => $ip->ip_address,
                'label' => $ip->label,
                'is_custom' => true,
            ])->all();
            $cardOnline = $this->calculateIpOnlineStatus($allIps, (bool) $customDev->is_online, $onlineTailscaleIpsMap);

            $cardName = $customDev->name;

            // Determine health status: online, warning (e.g. no IP configured), or offline
            $healthStatus = 'offline';
            if (empty($allIps)) {
                $healthStatus = 'warning';
            } elseif ($cardOnline) {
                $healthStatus = 'online';
            } else {
                $healthStatus = 'offline';
            }

            $supportCards[] = [
                'id' => $deviceKey,
                'db_id' => $customDev->id,
                'name' => $cardName,
                'company_name' => $customDev->company_name,
                'image_path' => $customDev->image_path ? asset($customDev->image_path) : null,
                'dnsName' => $customDev->dns_name,
                'os' => $customDev->os,
                'employees_count' => (int) $customDev->employees_count,
                'lines_count' => (int) $customDev->lines_count,
                'ips' => array_column($allIps, 'ip'),
                'all_ips' => $allIps,
                'online' => $cardOnline,
                'health_status' => $healthStatus,
                'active' => false,
                'self' => false,
                'is_custom_device' => true,
                'notes' => $customDev->notes,
                'lastActivity' => $customDev->updated_at?->toIso8601String(),
            ];
        }

        usort($supportCards, static function (array $left, array $right): int {
            if (($left['online'] ?? false) !== ($right['online'] ?? false)) {
                return ($left['online'] ?? false) ? -1 : 1;
            }

            return strnatcasecmp((string) $left['name'], (string) $right['name']);
        });

        $onlineCount = count(array_filter(
            $supportCards,
            static fn (array $d): bool => ($d['health_status'] ?? '') === 'online',
        ));

        $warningCount = count(array_filter(
            $supportCards,
            static fn (array $d): bool => ($d['health_status'] ?? '') === 'warning',
        ));

        $offlineCount = count(array_filter(
            $supportCards,
            static fn (array $d): bool => ($d['health_status'] ?? '') === 'offline',
        ));

        $snapshot['devices'] = $supportCards;
        $snapshot['rawTailscaleDevices'] = $user->isSuperAdmin()
            ? $tailscaleDevices
            : [];
        $snapshot['onlineCount'] = $onlineCount;
        $snapshot['warningCount'] = $warningCount;
        $snapshot['offlineCount'] = $offlineCount;
        $snapshot['customDevicesCount'] = count($supportCards);
        $snapshot['tailscaleIps'] = $user->isSuperAdmin()
            ? $tailscaleIpsList
            : [];

        return view('technical-support.index', [
            'snapshot' => $snapshot,
        ]);
    }

    public function show(
        string $deviceKey,
        Request $request,
        TailscaleStatusService $tailscale,
    ): View|RedirectResponse {
        CrmDatabaseGuard::ensureConnected();
        $user = $request->user();

        $snapshot = $tailscale->snapshot($request->boolean('refresh'));
        $onlineTailscaleIpsMap = [];
        $tailscaleIpsList = [];
        $foundDevice = null;

        foreach ($snapshot['devices'] ?? [] as $device) {
            $isOnline = (bool) ($device['online'] ?? false);
            $deviceName = (string) ($device['name'] ?? 'Unknown');
            foreach ($device['ips'] ?? [] as $ip) {
                $ipStr = (string) $ip;
                if ($isOnline) {
                    $onlineTailscaleIpsMap[$ipStr] = true;
                }
                if (filter_var($ipStr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $tailscaleIpsList[] = [
                        'ip' => $ipStr,
                        'deviceName' => $deviceName,
                        'online' => $isOnline,
                    ];
                }
            }

            if (
                $user->isSuperAdmin()
                && (string) ($device['id'] ?? '') === $deviceKey
            ) {
                $foundDevice = $device;
            }
        }

        $customDevice = TechnicalSupportDevice::query()
            ->accessibleTo($user)
            ->where('is_hidden', false)
            ->where(static function ($query) use ($deviceKey): void {
                $query->where('device_key', $deviceKey)
                    ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
            })
            ->first();

        if (! $foundDevice && ! $customDevice) {
            return redirect()->route('v2.technical-support.index')
                ->withErrors(['device' => __('crm.no_devices_found')]);
        }

        $allCustomIps = TechnicalSupportIp::query()
            ->where('device_key', $customDevice ? $customDevice->device_key : $deviceKey)
            ->get();

        $allIps = [];
        if ($foundDevice) {
            foreach ($foundDevice['ips'] ?? [] as $ip) {
                $allIps[] = [
                    'id' => null,
                    'ip' => (string) $ip,
                    'label' => null,
                    'is_custom' => false,
                    'online' => ! empty($onlineTailscaleIpsMap[(string) $ip]),
                ];
            }
        }

        foreach ($allCustomIps as $customIp) {
            if (! in_array($customIp->ip_address, array_column($allIps, 'ip'), true)) {
                $allIps[] = [
                    'id' => $customIp->id,
                    'ip' => $customIp->ip_address,
                    'label' => $customIp->label,
                    'is_custom' => true,
                    'online' => ! empty($onlineTailscaleIpsMap[$customIp->ip_address]),
                ];
            }
        }

        $fallbackOnline = $foundDevice ? (bool) ($foundDevice['online'] ?? false) : (bool) ($customDevice->is_online ?? false);
        $cardOnline = $this->calculateIpOnlineStatus($allIps, $fallbackOnline, $onlineTailscaleIpsMap);

        $cardName = $customDevice ? $customDevice->name : ($foundDevice['name'] ?? 'Device');

        $deviceData = [
            'id' => $customDevice ? $customDevice->device_key : ($foundDevice['id'] ?? $deviceKey),
            'db_id' => $customDevice?->id,
            'name' => $cardName,
            'company_name' => $customDevice?->company_name,
            'image_path' => $customDevice?->image_path ? asset($customDevice->image_path) : null,
            'employees_count' => (int) ($customDevice?->employees_count ?? 0),
            'lines_count' => (int) ($customDevice?->lines_count ?? 0),
            'dnsName' => $customDevice ? $customDevice->dns_name : ($foundDevice['dnsName'] ?? null),
            'os' => $customDevice ? $customDevice->os : ($foundDevice['os'] ?? 'linux'),
            'online' => $cardOnline,
            'active' => (bool) ($foundDevice['active'] ?? false),
            'self' => (bool) ($foundDevice['self'] ?? false),
            'is_custom_device' => (bool) $customDevice,
            'notes' => $customDevice?->notes,
            'all_ips' => $allIps,
            'lastActivity' => $foundDevice['lastActivity'] ?? $customDevice?->updated_at?->toIso8601String(),
        ];

        $ticketDeviceKey = $customDevice?->device_key ?? $deviceKey;
        $tickets = TechnicalSupportTicket::query()
            ->accessibleTo($user)
            ->where('device_key', $ticketDeviceKey)
            ->with(['openedBy:id,name', 'closedBy:id,name'])
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('opened_at')
            ->get();

        return view('technical-support.show', [
            'device' => $deviceData,
            'snapshot' => $snapshot,
            'tailscaleIpsList' => $user->isSuperAdmin()
                ? $tailscaleIpsList
                : [],
            'tickets' => $tickets,
        ]);
    }

    public function storeTicket(
        string $deviceKey,
        Request $request,
        TailscaleStatusService $tailscale,
    ): RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
        ]);

        $existingDevice = TechnicalSupportDevice::query()
            ->accessibleTo($request->user())
            ->where(static function ($query) use ($deviceKey): void {
                $query->where('device_key', $deviceKey)
                    ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
            })
            ->first();

        abort_if($existingDevice?->is_hidden, 404);

        $tailscaleDevice = null;
        if (! $existingDevice) {
            abort_unless($request->user()->isSuperAdmin(), 404);

            $tailscaleDevice = collect($tailscale->snapshot()['devices'] ?? [])
                ->first(static fn (array $item): bool => (string) ($item['id'] ?? '') === $deviceKey);

            abort_unless(is_array($tailscaleDevice), 404);
        }

        DB::transaction(function () use ($deviceKey, $request, $tailscaleDevice, $validated): void {
            $device = TechnicalSupportDevice::query()
                ->accessibleTo($request->user())
                ->where(static function ($query) use ($deviceKey): void {
                    $query->where('device_key', $deviceKey)
                        ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
                })
                ->lockForUpdate()
                ->first();

            if (! $device) {
                $device = TechnicalSupportDevice::firstOrCreate([
                    'device_key' => $deviceKey,
                ], [
                    'name' => (string) ($tailscaleDevice['name'] ?? $deviceKey),
                    'dns_name' => $tailscaleDevice['dnsName'] ?? null,
                    'os' => (string) ($tailscaleDevice['os'] ?? 'other'),
                    'is_online' => (bool) ($tailscaleDevice['online'] ?? false),
                    'is_custom' => false,
                    'is_hidden' => false,
                    'created_by_user_id' => $request->user()?->id,
                ]);

                foreach ($tailscaleDevice['ips'] ?? [] as $ip) {
                    if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                        TechnicalSupportIp::firstOrCreate([
                            'device_key' => $device->device_key,
                            'ip_address' => $ip,
                        ], [
                            'label' => 'Tailscale',
                            'created_by_user_id' => $request->user()?->id,
                        ]);
                    }
                }
            }

            abort_if($device->is_hidden, 404);

            TechnicalSupportTicket::create([
                'device_key' => $device->device_key,
                'subject' => trim($validated['subject']),
                'description' => null,
                'status' => TechnicalSupportTicket::STATUS_OPEN,
                'opened_by_user_id' => $request->user()?->id,
                'opened_at' => now(),
            ]);
        });

        return back()->with('success', __('crm.support_ticket_opened_successfully'));
    }

    public function closeTicket(
        string $deviceKey,
        TechnicalSupportTicket $ticket,
        Request $request,
    ): RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $device = TechnicalSupportDevice::query()
            ->accessibleTo($request->user())
            ->where('is_hidden', false)
            ->where(static function ($query) use ($deviceKey): void {
                $query->where('device_key', $deviceKey)
                    ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
            })
            ->firstOrFail();

        abort_unless($ticket->device_key === $device->device_key, 404);

        $validated = $request->validate([
            'resolution' => ['required', 'string', 'max:5000'],
        ]);

        $updated = TechnicalSupportTicket::query()
            ->accessibleTo($request->user())
            ->whereKey($ticket->id)
            ->where('status', TechnicalSupportTicket::STATUS_OPEN)
            ->update([
                'status' => TechnicalSupportTicket::STATUS_CLOSED,
                'resolution' => trim($validated['resolution']),
                'closed_by_user_id' => $request->user()?->id,
                'closed_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return back()->withErrors([
                'ticket' => __('crm.support_ticket_already_closed'),
            ]);
        }

        return back()->with('success', __('crm.support_ticket_closed_successfully'));
    }

    public function update(
        string $deviceKey,
        Request $request,
    ): JsonResponse|RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'lines_count' => ['nullable', 'integer', 'min:0'],
            'dns_name' => ['nullable', 'string', 'max:255'],
            'os' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:4096'],
            'selected_tailscale_ip' => ['nullable', 'string', 'max:120'],
            'new_ip_address' => ['nullable', 'string', 'max:120'],
            'new_ip_label' => ['nullable', 'string', 'max:100'],
        ]);

        $device = TechnicalSupportDevice::query()
            ->accessibleTo($request->user())
            ->where(static function ($query) use ($deviceKey): void {
                $query->where('device_key', $deviceKey)
                    ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
            })
            ->first();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('support-cards', 'public');
            $imagePath = 'storage/'.$path;
        }

        if (! $device) {
            abort_unless($request->user()->isSuperAdmin(), 404);
        }

        if (! $device) {
            $device = TechnicalSupportDevice::create([
                'device_key' => $deviceKey,
                'name' => trim($validated['name']),
                'company_name' => ! empty($validated['company_name']) ? trim($validated['company_name']) : null,
                'image_path' => $imagePath,
                'employees_count' => (int) ($validated['employees_count'] ?? 0),
                'lines_count' => (int) ($validated['lines_count'] ?? 0),
                'dns_name' => ! empty($validated['dns_name']) ? trim($validated['dns_name']) : null,
                'os' => strtolower(trim($validated['os'])),
                'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
                'is_custom' => true,
                'is_hidden' => false,
                'created_by_user_id' => $request->user()?->id,
            ]);
        } else {
            $updateData = [
                'name' => trim($validated['name']),
                'company_name' => ! empty($validated['company_name']) ? trim($validated['company_name']) : null,
                'employees_count' => (int) ($validated['employees_count'] ?? 0),
                'lines_count' => (int) ($validated['lines_count'] ?? 0),
                'dns_name' => ! empty($validated['dns_name']) ? trim($validated['dns_name']) : null,
                'os' => strtolower(trim($validated['os'])),
                'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
                'is_hidden' => false,
            ];

            if ($imagePath !== null) {
                $updateData['image_path'] = $imagePath;
            }

            $device->update($updateData);
        }

        $targetKey = $device->device_key;

        // Attach selected Tailscale IP if provided in edit form
        if (! empty($validated['selected_tailscale_ip'])) {
            $tsIp = trim($validated['selected_tailscale_ip']);
            if (filter_var($tsIp, FILTER_VALIDATE_IP) || preg_match('/^[a-zA-Z0-9\.\:\-\/]+$/', $tsIp)) {
                TechnicalSupportIp::firstOrCreate([
                    'device_key' => $targetKey,
                    'ip_address' => $tsIp,
                ], [
                    'label' => 'Tailscale',
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        }

        // Attach new custom IP if provided in edit form
        if (! empty($validated['new_ip_address'])) {
            $newIp = trim($validated['new_ip_address']);
            if (filter_var($newIp, FILTER_VALIDATE_IP) || preg_match('/^[a-zA-Z0-9\.\:\-\/]+$/', $newIp)) {
                TechnicalSupportIp::firstOrCreate([
                    'device_key' => $targetKey,
                    'ip_address' => $newIp,
                ], [
                    'label' => ! empty($validated['new_ip_label']) ? trim($validated['new_ip_label']) : null,
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.device_updated_successfully'),
                'device' => $device->fresh('ips'),
            ]);
        }

        return back()->with('success', __('crm.device_updated_successfully'));
    }

    public function storeIp(Request $request): JsonResponse|RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'device_key' => ['required', 'string', 'max:120'],
            'ip_address' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $ip = trim($validated['ip_address']);

        if (! filter_var($ip, FILTER_VALIDATE_IP) && ! preg_match('/^[a-zA-Z0-9\.\:\-\/]+$/', $ip)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('crm.invalid_ip_address'),
                ], 422);
            }

            return back()->withErrors(['ip_address' => __('crm.invalid_ip_address')]);
        }

        if (! $request->user()->isSuperAdmin()) {
            TechnicalSupportDevice::query()
                ->accessibleTo($request->user())
                ->where('device_key', $validated['device_key'])
                ->firstOrFail();
        }

        $exists = TechnicalSupportIp::query()
            ->where('device_key', $validated['device_key'])
            ->where('ip_address', $ip)
            ->exists();

        if ($exists) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('crm.ip_already_exists'),
                ], 422);
            }

            return back()->withErrors(['ip_address' => __('crm.ip_already_exists')]);
        }

        $record = TechnicalSupportIp::create([
            'device_key' => $validated['device_key'],
            'ip_address' => $ip,
            'label' => ! empty($validated['label']) ? trim($validated['label']) : null,
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.ip_added_successfully'),
                'ip' => [
                    'id' => $record->id,
                    'device_key' => $record->device_key,
                    'ip' => $record->ip_address,
                    'label' => $record->label,
                    'is_custom' => true,
                ],
            ]);
        }

        return back()->with('success', __('crm.ip_added_successfully'));
    }

    public function destroyIp(
        TechnicalSupportIp $ip,
        Request $request,
    ): JsonResponse|RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $ip = TechnicalSupportIp::query()
            ->accessibleTo($request->user())
            ->findOrFail($ip->getKey());

        $deletedId = $ip->id;
        $ip->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.ip_deleted_successfully'),
                'id' => $deletedId,
            ]);
        }

        return back()->with('success', __('crm.ip_deleted_successfully'));
    }

    public function storeDevice(Request $request): JsonResponse|RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'lines_count' => ['nullable', 'integer', 'min:0'],
            'dns_name' => ['nullable', 'string', 'max:255'],
            'os' => ['required', 'string', 'max:50'],
            'is_online' => ['nullable'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:4096'],
            'selected_tailscale_ip' => ['nullable', 'string', 'max:120'],
            'initial_ip' => ['nullable', 'string', 'max:120'],
            'initial_ip_label' => ['nullable', 'string', 'max:100'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('support-cards', 'public');
            $imagePath = 'storage/'.$path;
        }

        $deviceKey = 'custom_'.Str::lower(Str::random(12));

        $device = TechnicalSupportDevice::create([
            'device_key' => $deviceKey,
            'name' => trim($validated['name']),
            'company_name' => ! empty($validated['company_name']) ? trim($validated['company_name']) : null,
            'image_path' => $imagePath,
            'employees_count' => (int) ($validated['employees_count'] ?? 0),
            'lines_count' => (int) ($validated['lines_count'] ?? 0),
            'dns_name' => ! empty($validated['dns_name']) ? trim($validated['dns_name']) : null,
            'os' => strtolower(trim($validated['os'])),
            'is_online' => $request->boolean('is_online', true),
            'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'is_custom' => true,
            'is_hidden' => false,
            'created_by_user_id' => $request->user()?->id,
        ]);

        if (! empty($validated['selected_tailscale_ip'])) {
            $tsIp = trim($validated['selected_tailscale_ip']);
            if (filter_var($tsIp, FILTER_VALIDATE_IP) || preg_match('/^[a-zA-Z0-9\.\:\-\/]+$/', $tsIp)) {
                TechnicalSupportIp::create([
                    'device_key' => $deviceKey,
                    'ip_address' => $tsIp,
                    'label' => 'Tailscale',
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        }

        if (! empty($validated['initial_ip'])) {
            $initialIp = trim($validated['initial_ip']);
            if (filter_var($initialIp, FILTER_VALIDATE_IP) || preg_match('/^[a-zA-Z0-9\.\:\-\/]+$/', $initialIp)) {
                TechnicalSupportIp::create([
                    'device_key' => $deviceKey,
                    'ip_address' => $initialIp,
                    'label' => ! empty($validated['initial_ip_label']) ? trim($validated['initial_ip_label']) : null,
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.device_created_successfully'),
                'device' => $device->fresh('ips'),
            ]);
        }

        return back()->with('success', __('crm.device_created_successfully'));
    }

    public function destroyDevice(
        string $deviceKey,
        Request $request,
    ): JsonResponse|RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $deleteBlocked = false;

        DB::transaction(function () use ($deviceKey, $request, &$deleteBlocked): void {
            $device = TechnicalSupportDevice::query()
                ->accessibleTo($request->user())
                ->where(static function ($query) use ($deviceKey): void {
                    $query->where('device_key', $deviceKey)
                        ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
                })
                ->lockForUpdate()
                ->first();

            if ($device) {
                if ($device->tickets()->exists()) {
                    $deleteBlocked = true;

                    return;
                }

                $device->ips()->delete();
                $device->update(['is_hidden' => true]);

                return;
            }

            abort_unless($request->user()->isSuperAdmin(), 404);

            if (TechnicalSupportTicket::query()->where('device_key', $deviceKey)->exists()) {
                $deleteBlocked = true;

                return;
            }

            TechnicalSupportDevice::create([
                'device_key' => $deviceKey,
                'name' => $deviceKey,
                'os' => 'other',
                'is_custom' => false,
                'is_hidden' => true,
                'created_by_user_id' => $request->user()?->id,
            ]);

            TechnicalSupportIp::query()
                ->where('device_key', $deviceKey)
                ->delete();
        });

        if ($deleteBlocked) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('crm.cannot_delete_server_with_tickets'),
                ], 422);
            }

            return back()->withErrors([
                'device' => __('crm.cannot_delete_server_with_tickets'),
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.device_deleted_successfully'),
            ]);
        }

        return redirect()->route('v2.technical-support.index')
            ->with('success', __('crm.device_deleted_successfully'));
    }

    public function toggleDeviceStatus(
        string $deviceKey,
        Request $request,
    ): JsonResponse|RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $device = TechnicalSupportDevice::query()
            ->accessibleTo($request->user())
            ->where(static function ($query) use ($deviceKey): void {
                $query->where('device_key', $deviceKey)
                    ->orWhere('id', is_numeric($deviceKey) ? (int) $deviceKey : 0);
            })
            ->first();

        if (! $device) {
            abort_unless($request->user()->isSuperAdmin(), 404);
        }

        if ($device) {
            $device->update([
                'is_online' => ! $device->is_online,
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.status_updated_successfully'),
                'is_online' => $device ? $device->is_online : false,
            ]);
        }

        return back()->with('success', __('crm.status_updated_successfully'));
    }

    /**
     * Calculate card online status based on assigned IPs and Tailscale network map.
     * If ANY assigned IP is online in Tailscale, the card is online.
     *
     * @param  array<int, array{ip: string, ...}>  $allIps
     * @param  array<string, bool>  $onlineTailscaleIpsMap
     */
    private function calculateIpOnlineStatus(array $allIps, bool $defaultFallback, array $onlineTailscaleIpsMap): bool
    {
        if (empty($allIps)) {
            return $defaultFallback;
        }

        foreach ($allIps as $ipItem) {
            $ipStr = is_array($ipItem) ? ($ipItem['ip'] ?? '') : (string) $ipItem;
            if (! empty($onlineTailscaleIpsMap[$ipStr])) {
                return true;
            }
        }

        return false;
    }
}
