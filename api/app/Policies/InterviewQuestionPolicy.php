<?php

namespace App\Policies;

use App\Models\InterviewQuestion;
use App\Models\User;

class InterviewQuestionPolicy
{
    public function update(User $user, InterviewQuestion $question): bool
    {
        return $user->id === $question->user_id;
    }

    public function delete(User $user, InterviewQuestion $question): bool
    {
        return $user->id === $question->user_id;
    }
}
