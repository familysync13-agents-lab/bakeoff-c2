<?php

namespace App\Policies;

use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Reading lists are private: only the owner may view, rename or delete one. Everyone else gets a 404, so the
 * existence of another user's list is not revealed.
 */
class ReadingListPolicy
{
    public function view(User $user, ReadingList $list): Response
    {
        return $this->owner($user, $list);
    }

    public function update(User $user, ReadingList $list): Response
    {
        return $this->owner($user, $list);
    }

    public function delete(User $user, ReadingList $list): Response
    {
        return $this->owner($user, $list);
    }

    private function owner(User $user, ReadingList $list): Response
    {
        return $list->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
