<?php

namespace App\Http\Controllers;

use App\Jobs\SendNewPostEmail;
use App\Models\Post;
use App\Mail\NewPostEmail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{
    //
    public function search($term) {
        $posts = Post::search($term)->get();
        $posts->load('user:id,username');
        return $posts;
    }

    public function actuallyUpdate(Post $post, Request $request) {
        $incomingcalls = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $incomingcalls['title'] = strip_tags($incomingcalls['title']);
        $incomingcalls['body'] = strip_tags($incomingcalls['body']);

        $post->update($incomingcalls);

    }
    public function showEditForm(Post $post) {
        return view('edit-post', ['post'=> $post]);
    }

    public function delete(Post $post) {
        if (auth()->user()->cannot('delete', $post)) {
            return 'You cannot do that';
        }
        $post->delete();

        return redirect('/profile/' . auth()->user()->username)->with('success', 'post sucessfully deleted');
    }

    public function  viewSinglePost(Post $post) {
        $post['body'] = Str::markdown($post->body);
        return view('single-post', ['post' => $post]);
    }

    public function storeNewPost(Request $request) {
        $incomingcalls = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $incomingcalls['title'] = strip_tags($incomingcalls['title']);
        $incomingcalls['body'] = strip_tags($incomingcalls['body']);
        $incomingcalls['user_id'] = auth()->id();

       $newPost = Post::create($incomingcalls);

       dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name' => auth()->user()->username, 'title' => $newPost->title]));

        return redirect("/post/{$newPost->id}")->with('success', 'new post successfully created');
    }

    public function storeNewPostApi(Request $request) {
        $incomingcalls = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $incomingcalls['title'] = strip_tags($incomingcalls['title']);
        $incomingcalls['body'] = strip_tags($incomingcalls['body']);
        $incomingcalls['user_id'] = auth()->id();

       $newPost = Post::create($incomingcalls);

       dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name' => auth()->user()->username, 'title' => $newPost->title]));

        return $newPost->id;
    }


    public function showCreateForm() {
        if (!auth()->check()) {
            return redirect('/');
        }
        return view('create-post');
    }
} 
