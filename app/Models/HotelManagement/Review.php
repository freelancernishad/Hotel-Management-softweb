<?php

namespace App\Models\HotelManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'rating',
        'comment'
    ];

    // Rating constants
    const RATING_POOR = 1;
    const RATING_FAIR = 2;
    const RATING_GOOD = 3;
    const RATING_VERY_GOOD = 4;
    const RATING_EXCELLENT = 5;

    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Room model
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Scope for reviews with minimum rating
    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    // Scope for reviews with maximum rating
    public function scopeMaxRating($query, $rating)
    {
        return $query->where('rating', '<=', $rating);
    }

    // Scope for reviews by specific user
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope for reviews for specific room
    public function scopeForRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    // Get rating in text format
    public function getRatingTextAttribute()
    {
        switch ($this->rating) {
            case self::RATING_POOR:
                return 'Poor';
            case self::RATING_FAIR:
                return 'Fair';
            case self::RATING_GOOD:
                return 'Good';
            case self::RATING_VERY_GOOD:
                return 'Very Good';
            case self::RATING_EXCELLENT:
                return 'Excellent';
            default:
                return 'Not Rated';
        }
    }

    // Check if review is by authenticated user
    public function isByAuthUser()
    {
        return $this->user_id === auth()->id();
    }

    // Calculate average rating for a room
    public static function averageRatingForRoom($roomId)
    {
        return static::where('room_id', $roomId)->avg('rating');
    }

    // Get rating distribution for a room
    public static function ratingDistributionForRoom($roomId)
    {
        return static::selectRaw('rating, COUNT(*) as count')
            ->where('room_id', $roomId)
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();
    }
}
