<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A book added to a reading list from the book-search API.
 *
 * @property int $id
 * @property int $reading_list_id
 * @property string $identity
 * @property string $title
 * @property string $authors
 * @property int|null $first_publish_year
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['identity', 'title', 'authors', 'first_publish_year'])]
class Book extends Model
{
    /**
     * @return BelongsTo<ReadingList, $this>
     */
    public function readingList(): BelongsTo
    {
        return $this->belongsTo(ReadingList::class);
    }
}
