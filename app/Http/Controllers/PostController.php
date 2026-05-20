<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Posts/Index', [
            'posts'          => Post::with('author:id,name')->latest()->paginate(10),
            'authUserId'     => auth()->id(),
            'isSuperadmin'   => auth()->user()->hasRole('superadmin'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Posts/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(! $request->user()->can('create posts'), 403);

        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $request->user()->posts()->create($validated);

        return redirect()->route('posts.index')->with('message', 'Post created.');
    }

    public function show(Post $post): Response
    {
        return Inertia::render('Posts/Show', ['post' => $post->load('author:id,name')]);
    }

    public function edit(Post $post): Response
    {
        $user = auth()->user();
        abort_if(! $user->can('edit posts'), 403);
        // Editors may only edit their own posts; superadmin can edit any
        abort_if($user->hasRole('editor') && $post->user_id !== $user->id, 403);

        return Inertia::render('Posts/Edit', ['post' => $post]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->can('edit posts'), 403);
        abort_if($user->hasRole('editor') && $post->user_id !== $user->id, 403);

        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post->update($validated);

        return redirect()->route('posts.index')->with('message', 'Post updated.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->can('delete posts'), 403);
        // Editors don't have delete permission, but guard superadmin-only delete for safety
        abort_if($user->hasRole('editor') && $post->user_id !== $user->id, 403);

        $post->delete();

        return redirect()->route('posts.index')->with('message', 'Post deleted.');
    }
}
