<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;

class PostController extends Controller

{    
    public function index()
    {
        $posts = Post::all();
        return view("post.index", ["posts"=> $posts]);
    } 

        
    public function destroy(Post $post)
    {
        // Use authorization to check if the user can delete this post (optional)
        $this->authorize('delete', $post); 

        $post->delete(); // Delete the post from the database

        return redirect('/posts/index'); // Redirect back to the posts index page
    }
    
    public function show(Post $post)
    {
        // Use the policy to check if the user can view this post
        $this->authorize('view', $post); // This will call the 'view' method in PostPolicy

        // If authorized, continue with the logic to show the post
        return view("post.show", ["posts" => [$post]]);
    }

    public function destroy_(Post $post)
    {
       $post->delete();
       return redirect("/posts/index");
    }

    
    public function create()
    {
        
        if (Gate::allows('isAuthor')) {
            return Post::create([
                'title' => request('title'),
                'content' => request('content'),
                'user_id' => auth()->id(),
            ]);
        } else
            dd('You are not an Author');
        
    }
    public function edit()
    {  
        if (Gate::allows('isAuthor')) {
            dd('Author allowed');
        } else
            dd('You are not an Author');
    }
    
    public function delete()
    {
        
        if (Gate::allows('isAdmin')) {
            dd('Admin allowed');
        } else
            dd('You are not Admin');
            
    }


}