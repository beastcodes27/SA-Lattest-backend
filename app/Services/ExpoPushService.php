<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to one or multiple Expo Push tokens.
     *
     * @param string|array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @param int|null $badge
     * @return array|null
     */
    public static function sendPushNotification($tokens, string $title, string $body, array $data = [], ?int $badge = null): ?array
    {
        $tokenList = is_array($tokens) ? $tokens : [$tokens];
        $validTokens = array_values(array_filter($tokenList, function ($token) {
            return is_string($token) && (
                str_starts_with($token, 'ExponentPushToken[') ||
                str_starts_with($token, 'ExpoPushToken[') ||
                strlen($token) > 10
            );
        }));

        if (empty($validTokens)) {
            return null;
        }

        $messages = [];
        foreach ($validTokens as $token) {
            $msg = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'default',
                '_displayInForeground' => true,
                'data' => array_merge($data, [
                    'title' => $title,
                    'body' => $body,
                    'timestamp' => now()->toISOString(),
                ]),
            ];
            if ($badge !== null) {
                $msg['badge'] = $badge;
            }
            $messages[] = $msg;
        }

        // Expo allows max 100 messages per HTTP request
        $chunks = array_chunk($messages, 100);
        $results = [];

        foreach ($chunks as $chunk) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Content-Type' => 'application/json',
                    ])
                    ->post(self::EXPO_PUSH_URL, $chunk);

                if ($response->successful()) {
                    $results[] = $response->json();
                } else {
                    Log::warning('Expo Push API error: ' . $response->body());
                }
            } catch (\Throwable $e) {
                Log::error('Expo Push notification exception: ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Create in-app notification and send push notification to a single user.
     */
    public static function notifyUser(User $user, string $title, string $body, string $type = 'broadcast', array $data = [], ?User $sender = null): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'org_id' => $user->org_id,
            'branch_id' => $user->branch_id,
            'sender_id' => $sender?->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
            'is_read' => false,
        ]);

        if (!empty($user->expo_push_token)) {
            $unreadCount = AppNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            $payloadData = array_merge($data, [
                'notification_id' => $notification->id,
                'type' => $type,
                'category' => $type === 'security_alert' ? 'security' : 'general',
            ]);

            self::sendPushNotification(
                $user->expo_push_token,
                $title,
                $body,
                $payloadData,
                $unreadCount
            );
        }

        return $notification;
    }

    /**
     * Bulk create in-app notifications and send push notification to multiple users.
     */
    public static function notifyUsers($users, string $title, string $body, string $type = 'broadcast', array $data = [], ?User $sender = null): int
    {
        $count = 0;
        $tokens = [];
        $records = [];
        $now = now();

        foreach ($users as $user) {
            $count++;
            $records[] = [
                'user_id' => $user->id,
                'org_id' => $user->org_id,
                'branch_id' => $user->branch_id,
                'sender_id' => $sender?->id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => json_encode($data),
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (!empty($user->expo_push_token)) {
                $tokens[] = $user->expo_push_token;
            }
        }

        if (!empty($records)) {
            AppNotification::insert($records);
        }

        if (!empty($tokens)) {
            self::sendPushNotification(
                $tokens,
                $title,
                $body,
                array_merge($data, ['type' => $type])
            );
        }

        return $count;
    }
}

