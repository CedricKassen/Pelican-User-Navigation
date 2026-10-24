<?php

namespace CedricKassen\UserNavigation\Models;

use CedricKassen\UserNavigation\Navigation\NavigationLayout;
use Illuminate\Database\Eloquent\Model;

/**
 * The navigation layout admins configured in the plugin settings. There is at most one row.
 *
 * @property int $id
 * @property string[]|null $order
 * @property string[]|null $hidden_items
 */
class NavigationDefault extends Model
{
    protected $table = 'user_navigation_defaults';

    protected $fillable = [
        'order',
        'hidden_items',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'array',
            'hidden_items' => 'array',
        ];
    }

    public static function current(): ?self
    {
        return self::query()->first();
    }

    /**
     * Stored items that aren't part of $keys keep their position and visibility.
     *
     * @param  string[]  $keys
     * @param  string[]  $hidden
     */
    public function storeLayout(array $keys, array $hidden): void
    {
        $this->order = NavigationLayout::mergeOrder($keys, $this->order ?? []);
        $this->hidden_items = array_values(array_unique([...array_diff($this->hidden_items ?? [], $keys), ...$hidden]));

        $this->save();
    }
}
