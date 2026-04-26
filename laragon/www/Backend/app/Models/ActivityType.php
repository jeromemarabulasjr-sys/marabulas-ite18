<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    protected $fillable = [
        'type_key',
        'display_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all activity logs of this type.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get display name for a given type key.
     */
    public static function getDisplayName(string $typeKey): string
    {
        $type = self::where('type_key', $typeKey)->first();
        return $type ? $type->display_name : $typeKey;
    }

    /**
     * Get all types as a key-value map.
     */
    public static function getTypeMap(): array
    {
        return self::pluck('display_name', 'type_key')->toArray();
    }
}
