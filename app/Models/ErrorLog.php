<?php

namespace App\Models;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'level',
        'message',
        'exception_class',
        'file',
        'line',
        'trace',
        'context',
        'user_id',
        'org_id',
        'ip_address',
        'user_agent',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('status', 'unresolved');
    }

    public function scopeSource($query, ?string $source)
    {
        if ($source && in_array($source, ['client', 'server'])) {
            return $query->where('source', $source);
        }

        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        $term = '%'.trim($search).'%';

        return $query->where(function ($q) use ($term) {
            $q->where('message', 'like', $term)
                ->orWhere('exception_class', 'like', $term)
                ->orWhere('file', 'like', $term);
        });
    }

    /**
     * Record a server exception automatically into the database.
     */
    public static function recordServerException(Throwable $e): ?self
    {
        try {
            // Ignore common user-flow exceptions that are not application bugs
            if (
                $e instanceof ValidationException ||
                $e instanceof AuthenticationException ||
                $e instanceof NotFoundHttpException ||
                $e instanceof ModelNotFoundException ||
                $e instanceof AccessDeniedHttpException ||
                $e instanceof ThrottleRequestsException
            ) {
                return null;
            }

            $request = request();
            $user = auth()->user();

            $sanitizedInput = [];
            if ($request instanceof Request) {
                $inputs = $request->except(['password', 'password_confirmation', 'token', 'access_token', 'code']);
                // Avoid logging enormous payloads (limit string length)
                foreach ($inputs as $key => $val) {
                    if (is_string($val) && strlen($val) > 1000) {
                        $sanitizedInput[$key] = substr($val, 0, 1000).'...[truncated]';
                    } else {
                        $sanitizedInput[$key] = $val;
                    }
                }
            }

            $context = [
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'route' => $request?->route()?->getName() ?: $request?->path(),
                'input' => $sanitizedInput,
                'ip' => $request?->ip(),
                'server_php' => PHP_VERSION,
            ];

            return self::create([
                'source' => 'server',
                'level' => 'critical',
                'message' => $e->getMessage() ?: get_class($e),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 12000),
                'context' => $context,
                'user_id' => $user?->id,
                'org_id' => $user?->org_id,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'status' => 'unresolved',
            ]);
        } catch (Throwable) {
            // Prevent recursive crashes if DB fails
            return null;
        }
    }
}
