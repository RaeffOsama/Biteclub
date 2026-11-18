<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(): View
    {
        $reports = Report::with('user')
            ->latest()
            ->paginate(15);

        return view('admin.moderation.index', compact('reports'));
    }

    public function resolve(Report $report): RedirectResponse
    {
        $report->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Report marked as resolved.');
    }

    public function dismiss(Report $report): RedirectResponse
    {
        $report->update(['status' => 'dismissed']);
        return redirect()->back()->with('success', 'Report dismissed.');
    }

    public function deletePost(Post $post): RedirectResponse
    {
        $post->delete();
        return redirect()->back()->with('success', 'Reported post has been deleted.');
    }

    public function deleteComment(Comment $comment): RedirectResponse
    {
        $comment->delete();
        return redirect()->back()->with('success', 'Reported comment has been deleted.');
    }
}
