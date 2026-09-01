<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use JsonException;
use Throwable;

class TailscaleStatusService
{
    private const CACHE_KEY = 'technical-support.tailscale-status';

    /** @return array<string, mixed> */
    public function snapshot(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(10),
            fn (): array => $this->readStatus(),
        );
    }

    /** @return array<string, mixed> */
    private function readStatus(): array
    {
        $updatedAt = now()->toIso8601String();
        $binary = (string) config(
            'services.tailscale.binary',
            '/usr/bin/tailscale',
        );

        try {
            $result = Process::timeout(5)->run([
                $binary,
                'status',
                '--json',
            ]);

            if (! $result->successful()) {
                Log::warning('Unable to read Tailscale status.', [
                    'exit_code' => $result->exitCode(),
                ]);

                return $this->unavailableSnapshot(
                    'command_failed',
                    $updatedAt,
                );
            }

            /** @var array<string, mixed> $status */
            $status = json_decode(
                $result->output(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            Log::warning('Tailscale returned invalid JSON.', [
                'exception' => $exception::class,
            ]);

            return $this->unavailableSnapshot(
                'invalid_response',
                $updatedAt,
            );
        } catch (Throwable $exception) {
            Log::warning('Tailscale status is unavailable.', [
                'exception' => $exception::class,
            ]);

            return $this->unavailableSnapshot(
                'unavailable',
                $updatedAt,
            );
        }

        $backendState = (string) ($status['BackendState'] ?? 'Unknown');
        $devices = [];

        if (is_array($status['Self'] ?? null)) {
            $devices[] = $this->mapDevice($status['Self'], true);
        }

        $peers = is_array($status['Peer'] ?? null)
            ? array_values($status['Peer'])
            : [];

        foreach ($peers as $peer) {
            if (is_array($peer)) {
                $devices[] = $this->mapDevice($peer, false);
            }
        }

        usort($devices, static function (array $left, array $right): int {
            if ($left['self'] !== $right['self']) {
                return $left['self'] ? -1 : 1;
            }

            if ($left['online'] !== $right['online']) {
                return $left['online'] ? -1 : 1;
            }

            return strnatcasecmp($left['name'], $right['name']);
        });

        $onlineCount = count(array_filter(
            $devices,
            static fn (array $device): bool => $device['online'],
        ));

        return [
            'available' => $backendState === 'Running',
            'error' => $backendState === 'Running'
                ? null
                : 'not_running',
            'backendState' => $backendState,
            'tailnet' => $this->nullableString(
                data_get($status, 'CurrentTailnet.Name'),
            ),
            'updatedAt' => $updatedAt,
            'devices' => $devices,
            'onlineCount' => $onlineCount,
            'offlineCount' => count($devices) - $onlineCount,
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function mapDevice(array $node, bool $self): array
    {
        $ips = array_values(array_filter(
            is_array($node['TailscaleIPs'] ?? null)
                ? $node['TailscaleIPs']
                : [],
            static fn (mixed $ip): bool => is_string($ip) && $ip !== '',
        ));

        $online = (bool) ($node['Online'] ?? $self);
        $dnsName = $this->nullableString($node['DNSName'] ?? null);

        return [
            'id' => (string) ($node['ID'] ?? $node['PublicKey'] ?? ''),
            'name' => (string) (
                $node['HostName']
                ?? ($dnsName !== null ? strtok($dnsName, '.') : null)
                ?? 'Unknown device'
            ),
            'dnsName' => $dnsName !== null
                ? rtrim($dnsName, '.')
                : null,
            'os' => (string) ($node['OS'] ?? 'unknown'),
            'ips' => $ips,
            'online' => $online,
            'active' => (bool) ($node['Active'] ?? false),
            'self' => $self,
            'lastActivity' => $self && $online
                ? now()->toIso8601String()
                : $this->latestActivity($node),
        ];
    }

    /** @param array<string, mixed> $node */
    private function latestActivity(array $node): ?string
    {
        foreach (['LastSeen', 'LastHandshake', 'LastWrite'] as $key) {
            $value = $this->nullableString($node[$key] ?? null);

            if ($value !== null && ! str_starts_with($value, '0001-')) {
                return $value;
            }
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : null;
    }

    /** @return array<string, mixed> */
    private function unavailableSnapshot(
        string $error,
        string $updatedAt,
    ): array {
        return [
            'available' => false,
            'error' => $error,
            'backendState' => 'Unavailable',
            'tailnet' => null,
            'updatedAt' => $updatedAt,
            'devices' => [],
            'onlineCount' => 0,
            'offlineCount' => 0,
        ];
    }
}
