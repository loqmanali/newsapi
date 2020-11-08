<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Post;
use App\Http\Resources\PostsResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\CommentsResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::with(['comments' , 'author' , 'category'])->paginate( env('POSTS_PER_PAGE') );
        return new PostsResource( $posts );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content'   => 'required',
            'category_id'   => 'required'
        ]);

        $user = $request->user();

        $post = new Post();

        $post->title = $request->get( 'title' );
        $post->content = $request->get( 'content' );
        if( intval( $request->get( 'category_id' ) ) != 0 ){
            $post->category_id = intval( $request->get( 'category_id' ) );
        }
        $post->user_id = $user->id;

        $post->votes_up = 0;
        $post->votes_down = 0;

        $post->date_written = Carbon::now()->format('Y-m-d H:i:s');


        // TODO: Handle 404 error
        if( $request->hasFile('featured_image') ){
            $featuredImage = $request->file( 'featured_image' );
            $filename = time().$featuredImage->getClientOriginalName();
            Storage::disk('images')->putFileAs(
                $filename,
                $featuredImage,
                $filename
            );
            $post->featured_image = url('/') . '/images/' .$filename;
        }

        $post->save();

        return new PostResource( $post );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = Post::with( ['comments' , 'author' , 'category'] )->where( 'id' , $id )->get();
        return new PostResource( $post );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::find( $id );

        if( $request->has('title') ){
            $post->title = $request->get( 'title' );
        }

        if( $request->has( 'content' ) ){
            $post->content = $request->get( 'content' );
        }

        if( $request->has('category_id') ){
            if( intval( $request->get( 'category_id' ) ) != 0 ){
                $post->category_id = intval( $request->get( 'category_id' ) );
            }
        }
        // TODO: Handle 404 error
        if( $request->hasFile('featured_image') ){
            $featuredImage = $request->file( 'featured_image' );
            $filename = time().$featuredImage->getClientOriginalName();
            Storage::disk('images')->putFileAs(
                $filename,
                $featuredImage,
                $filename
            );
            $post->featured_image = url('/') . '/images/' .$filename;
        }

        $post->save();

        return new PostResource( $post );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::find( $id );
        $post->delete();
        return new PostResource( $post );
    }




        /**
     * @param $id
     * @return CommentsResource
     */
    public function comments( $id ){
        $post = Post::find( $id );
        $comments = $post->comments()->paginate( env( 'COMMENTS_PER_PAGE' ) );
        return new CommentsResource( $comments );
    }

}
