<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\models\Posts;

class PostController extends Controller
{
    public function getBlogNews(Request $request, $limit = 5){

        $model = new \App\models\Posts();
        $post = $model->getBlogNews($limit);

        if ($post) {
            $response = array(
                'status' => "success",
                'data' => array("post" => $post)
            );
        } else {
            $response = array(
                "status" => "fail",
                "data" => array("title" => "ID không tồn tại hoặc bài viết này chưa được publish")
            );
        }

        return response()->json($response)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getTechnicalNews(Request $request, $type = 'technical_new', $limit = 5){

        $model = new \App\models\Posts();
        $post = $model->getTechnicalNews($type, $limit);

        if ($post) {
            $response = array(
                'status' => "success",
                'data' => array("post" => $post)
            );
        } else {
            $response = array(
                "status" => "fail",
                "data" => array("title" => "ID không tồn tại hoặc bài viết này chưa được publish")
            );
        }

        return response()->json($response)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getLibrary(Request $request, $type = 'library_group'){

        $model = new \App\models\Posts();
        $post = $model->getLibrary($type);

        if ($post) {
            $response = array(
                'status' => "success",
                'data' => array("post" => $post)
            );
        } else {
            $response = array(
                "status" => "fail",
                "data" => array("title" => "ID không tồn tại hoặc bài viết này chưa được publish")
            );
        }

        return response()->json($response)->header('Content-Type', 'application/json; charset=utf-8');
    }
}
