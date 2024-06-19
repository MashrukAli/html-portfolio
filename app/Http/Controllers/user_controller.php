<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Intervention\Image\Image;
use App\Events\OurExampleEvent;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class user_controller extends Controller
{   
    public function storeAvatar(Request $request) {
        $request->validate([
            'avatar' => 'required|image|max:8000'
        ]);

        $user = auth()->user();
        $filename = $user->id . '-' . uniqid() . 'jpg';

        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatar/' . $filename, $imgData);

        $user->avatar = $filename;
        $user->save();
    }   

    public function showAvatarForm() {
        return view('avatar-form');
    }


    private function getSharedData($user) {
        $currentlyFollowing = 0;

        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id],['followeduser', '=', $user->id]])->count();
        }
        View::share('sharedData', ['currentlyFollowing' => $currentlyFollowing, 'username'=> $user->username, 'postCount' => $user->posts()->count(), 'followerCount' => $user->followers()->count(), 'followingCount' => $user->followingTheseUsers()->count()]);
    }
    public function profile(User $user) {
        $this->getSharedData($user);
        return view('profile-posts', ['posts' => $user->posts()->latest()->get()]);
    }
    public function profileRaw(User $user) {
        return response()->json(['theHTML' => view('profile-posts-only', ['posts' => $user->posts()->latest()->get()])->render(), 'docTitle' => $user->username . "'s profile"]);
    }

    public function profileFollowers(User $user) {
        $this->getSharedData($user);
        return view('profile-followers', ['followers' => $user->followers()->latest()->get()]);
    }
    public function profileFollowersRaw(User $user) {
        return response()->json(['theHTML' => view('profile-followers-only', ['followers' => $user->followers()->latest()->get()])->render(), 'docTitle' => $user->username . "'s followers"]);
    }


    public function profileFollowing(User $user) {
        $this->getSharedData($user);
        return view('profile-following', ['following' => $user->followingTheseUsers()->latest()->get()]);
    }
    public function profileFollowingRaw(User $user) {
        return response()->json(['theHTML' => view('profile-following-only', ['following' => $user->followingTheseUsers()->latest()->get()])->render(), 'docTitle' => 'Who' . $user->username . "follows"]);
    }
     public function logout() {
        event(new OurExampleEvent (['username' => auth()->user()->username, 'action' => 'logout']));
        auth()->logout();
        return redirect('/')->with('Success', 'You are now logged out.');
    }

    public function showCorrectHomepage() {
        if (auth()->check()) {
            return view('homepage-feed', ['posts' => auth()->user()->feedPosts()->latest()->paginate(7)]);
        } else {
            $postCount = Cache::remember('postCount', 20, function () {
                sleep(2);
                return Post::count();
            });
            return view ('homepage', ['postCount' => $postCount]);
        }
    }


    public function loginApi(Request $request) {
        $incomingcalls = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (auth()->attempt($incomingcalls)) {
            $user = User::where('username', $incomingcalls['username'])->first();
            $token = $user->createToken('ourapptoken')->plainTextToken;
            return $token;
        }
        return 'sorry';
    }

    public function login(Request $request) {
        $incomingcalls = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if (auth()->attempt(['username' => $incomingcalls['loginusername'],'password' => $incomingcalls['loginpassword']])) {
            $request->session()->regenerate(); //tells the browser to send the value to the server so that it can be saved as cookies. Inspect > application > laravek_session = Cookie
            event(new OurExampleEvent (['username' => auth()->user()->username, 'action' => 'logout']));
            return redirect('/')->with('Success', 'You have successfully logged in.');
        } else {
            return redirect('/')->with('Failure', 'Invalid login');
        }
    }
    public function register(Request $request) {
        $incomingcalls = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:6', 'confirmed']
        ]);
        $user = User::create($incomingcalls);
        auth()->login($user); //logging them in before redirecting
        return redirect('/')->with('Success', 'Thank you for registering');
    }
}