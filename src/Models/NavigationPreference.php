<?php

namespace CedricKassen\UserNavigation\Models;

use App\Models\User;
use CedricKassen\UserNavigation\Navigation\NavigationLayout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Only stores what the user changed compared to the default, so changes to the default still apply to everything else.
 *
 * @property int $id
 * @property int $user_id
 * @property string[]|null $order null if the user kept the default order
 * @property string[]|null $hidden_items items the user hid although the default shows them
 * @property string[]|null $shown items the user shows although the default hides them
 * @property User $user
 */
class NavigationPreference extends Model
{
    protected $table = 'user_navigation_preferences';

    protected $fillable = [
        'user_id',
        'order',
        'hidden_items',
        'shown',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'array',
            'hidden_items' => 'array',
            'shown' => 'array',
        ];
    }

    public static function forUser(?User $user): ?self
    {
        return $user ? self::where('user_id', $user->id)->first() : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Stores the differences between the given layout and the default. Stored items that aren't part of $keys
     * (e.g. pages only other servers have) keep their position and visibility.
     *
     * @param  string[]  $keys
     * @param  string[]  $hidden
     * @param  array{order: string[], hidden: string[]}  $default  the default layout of $keys
     */
    public function storeOverrides(array $keys, array $hidden, array $default): void
    {
        $this->order = $keys === $default['order'] ? null : NavigationLayout::mergeOrder($keys, $this->order ?? []);
        $this->hidden_items = array_values(array_unique([...array_diff($this->hidden_items ?? [], $keys), ...array_diff($hidden, $default['hidden'])]));
        $this->shown = array_values(array_unique([...array_diff($this->shown ?? [], $keys), ...array_intersect(array_diff($keys, $hidden), $default['hidden'])]));

        if ($this->order === null && empty($this->hidden_items) && empty($this->shown)) {
            if ($this->exists) {
                $this->delete();
            }

            return;
        }

        $this->save();
    }
}
